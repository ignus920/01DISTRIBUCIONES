<?php

namespace App\Services\Factus;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FactusClient
{
    protected string $baseUrl;
    protected string $clientId;
    protected string $clientSecret;
    protected string $username;
    protected string $password;
    protected int $timeout;

    const TOKEN_CACHE_KEY   = 'factus_access_token';
    const REFRESH_CACHE_KEY = 'factus_refresh_token';

    public function __construct()
    {
        $this->baseUrl      = rtrim(config('services.factus.base_url'), '/');
        $this->clientId     = config('services.factus.client_id');
        $this->clientSecret = config('services.factus.client_secret');
        $this->username     = config('services.factus.username');
        $this->password     = config('services.factus.password');
        $this->timeout      = config('services.factus.timeout', 30);
    }

    // ─── Autenticación OAuth2 ────────────────────────────────────────────────

    /**
     * Devuelve el access token desde caché o autentica si no existe.
     */
    public function getAccessToken(): string
    {
        if (Cache::has(self::TOKEN_CACHE_KEY)) {
            return Cache::get(self::TOKEN_CACHE_KEY);
        }

        if (Cache::has(self::REFRESH_CACHE_KEY)) {
            try {
                return $this->refreshAccessToken(Cache::get(self::REFRESH_CACHE_KEY));
            } catch (\Exception $e) {
                Log::warning('Factus: refresh token inválido, re-autenticando', ['error' => $e->getMessage()]);
            }
        }

        return $this->authenticate();
    }

    /**
     * Autentica con grant_type=password y cachea los tokens.
     */
    protected function authenticate(): string
    {
        Log::info('Factus: autenticando con credenciales');

        $response = Http::asForm()
            ->timeout($this->timeout)
            ->withHeaders(['Accept' => 'application/json'])
            ->post($this->baseUrl . '/oauth/token', [
                'grant_type'    => 'password',
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'username'      => $this->username,
                'password'      => $this->password,
            ]);

        if (!$response->successful()) {
            throw new FactusApiException(
                'Factus Auth Error: ' . $response->body(),
                $response->status()
            );
        }

        $data = $response->json();
        $this->cacheTokens($data);

        Log::info('Factus: autenticación exitosa', ['expires_in' => $data['expires_in'] ?? null]);

        return $data['access_token'];
    }

    /**
     * Renueva el access token con el refresh token.
     */
    protected function refreshAccessToken(string $refreshToken): string
    {
        Log::info('Factus: renovando token con refresh_token');

        $response = Http::asForm()
            ->timeout($this->timeout)
            ->withHeaders(['Accept' => 'application/json'])
            ->post($this->baseUrl . '/oauth/token', [
                'grant_type'    => 'refresh_token',
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'refresh_token' => $refreshToken,
            ]);

        if (!$response->successful()) {
            Cache::forget(self::REFRESH_CACHE_KEY);
            throw new FactusApiException(
                'Factus Refresh Error: ' . $response->body(),
                $response->status()
            );
        }

        $data = $response->json();
        $this->cacheTokens($data);

        return $data['access_token'];
    }

    /**
     * Guarda access_token y refresh_token en caché.
     * Se resta 60 s al tiempo de expiración como margen de seguridad.
     */
    protected function cacheTokens(array $data): void
    {
        $expiresIn = ($data['expires_in'] ?? 600) - 60;

        Cache::put(self::TOKEN_CACHE_KEY, $data['access_token'], now()->addSeconds($expiresIn));

        if (!empty($data['refresh_token'])) {
            Cache::put(self::REFRESH_CACHE_KEY, $data['refresh_token'], now()->addHours(1));
        }
    }

    // ─── Peticiones HTTP ─────────────────────────────────────────────────────

    protected function makeRequest(string $method, string $endpoint, array $data = []): Response
    {
        $url   = $this->baseUrl . '/' . ltrim($endpoint, '/');
        $token = $this->getAccessToken();

        $request = Http::withHeaders([
            'Authorization' => 'Bearer ' . $token,
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ])->timeout($this->timeout);

        Log::info('Factus API Request', [
            'method'  => strtoupper($method),
            'url'     => $url,
            'payload' => $data,
        ]);

        $response = ($method === 'get' || $method === 'delete')
            ? $request->$method($url)
            : $request->$method($url, $data);

        Log::info('Factus API Response', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        return $response;
    }

    protected function get(string $endpoint): Response
    {
        return $this->makeRequest('get', $endpoint);
    }

    protected function post(string $endpoint, array $data = []): Response
    {
        return $this->makeRequest('post', $endpoint, $data);
    }

    protected function delete(string $endpoint): Response
    {
        return $this->makeRequest('delete', $endpoint);
    }

    // ─── Facturas (Bills) ────────────────────────────────────────────────────

    public function validateBill(array $invoiceData): array
    {
        $response = $this->post('/v1/bills/validate', $invoiceData);
        return $this->handleResponse($response);
    }

    public function getBills(): array
    {
        $response = $this->get('/v1/bills');
        return $this->handleResponse($response);
    }

    public function getBill(string $id): array
    {
        $response = $this->get("/v1/bills/{$id}");
        return $this->handleResponse($response);
    }

    public function getBillPdf(string $number): Response
    {
        return $this->get("/v1/bills/download-pdf/{$number}");
    }

    public function getBillXml(string $number): Response
    {
        return $this->get("/v1/bills/{$number}/download-xml");
    }

    public function getCreditNotePdf(string $number): Response
    {
        return $this->get("/v1/credit-notes/download-pdf/{$number}");
    }

    public function sendBillEmail(string $id, array $emailData = []): array
    {
        $response = $this->post("/v1/bills/{$id}/send-email", $emailData);
        return $this->handleResponse($response);
    }

    // ─── Notas de crédito ────────────────────────────────────────────────────

    public function validateCreditNote(array $data): array
    {
        $response = $this->post('/v1/credit-notes/validate', $data);
        return $this->handleResponse($response);
    }

    /**
     * Devuelve el ID del rango de numeración activo para Notas Crédito.
     * Retorna null si no encuentra rango específico (el campo es opcional en Factus
     * cuando solo hay un rango activo).
     */
    public function getCreditNoteNumberingRangeId(): ?int
    {
        return Cache::remember('factus_credit_note_numbering_range_id', now()->addHour(), function () {
            $response = $this->get('/v1/numbering-ranges?filter[is_active]=1');
            $data     = $this->handleResponse($response);
            $ranges   = $data['data'] ?? $data;

            foreach ($ranges as $range) {
                $isActive  = ($range['is_active'] ?? 0) == 1;
                $isExpired = $range['is_expired'] ?? true;
                $document  = strtolower($range['document'] ?? '');

                if ($isActive && !$isExpired && (
                    str_contains($document, 'cr') ||
                    str_contains($document, 'nota') ||
                    str_contains($document, 'note')
                )) {
                    Log::info('Factus: rango de nota crédito encontrado', [
                        'id'       => $range['id'],
                        'document' => $range['document'] ?? '',
                    ]);
                    return (int) $range['id'];
                }
            }

            Log::info('Factus: no se encontró rango específico para nota crédito (campo opcional)');
            return null;
        });
    }

    public function getCreditNotes(): array
    {
        $response = $this->get('/v1/credit-notes');
        return $this->handleResponse($response);
    }

    public function getCreditNote(string $id): array
    {
        $response = $this->get("/v1/credit-notes/{$id}");
        return $this->handleResponse($response);
    }

    // ─── Catálogos ───────────────────────────────────────────────────────────

    public function getPaymentMethods(): array
    {
        $response = $this->get('/v1/payment-methods');
        return $this->handleResponse($response);
    }

    public function getDocumentTypes(): array
    {
        $response = $this->get('/v1/document-types');
        return $this->handleResponse($response);
    }

    public function getNumberingRanges(): array
    {
        $response = $this->get('/v1/numbering-ranges');
        return $this->handleResponse($response);
    }

    /**
     * Devuelve el ID del primer rango de numeración activo para Facturas de venta.
     * Resultado cacheado 1 hora para evitar llamadas repetidas.
     */
    public function getActiveNumberingRangeId(): int
    {
        return Cache::remember('factus_numbering_range_id', now()->addHour(), function () {
            $response = $this->get('/v1/numbering-ranges?filter[is_active]=1');
            $data     = $this->handleResponse($response);
            $ranges   = $data['data'] ?? $data;

            Log::info('Factus: rangos de numeración obtenidos', ['total' => count($ranges), 'ranges' => $ranges]);

            // Buscar rango activo, no vencido y de tipo Factura
            foreach ($ranges as $range) {
                $isActive  = ($range['is_active'] ?? 0) == 1;
                $isExpired = $range['is_expired'] ?? true;
                $document  = strtolower($range['document'] ?? '');

                if ($isActive && !$isExpired && str_contains($document, 'factura')) {
                    Log::info('Factus: rango de factura encontrado', [
                        'id'       => $range['id'],
                        'prefix'   => $range['prefix'] ?? '',
                        'document' => $range['document'] ?? '',
                    ]);
                    return (int) $range['id'];
                }
            }

            // Fallback: primer rango activo no vencido de cualquier tipo
            foreach ($ranges as $range) {
                if (($range['is_active'] ?? 0) == 1 && !($range['is_expired'] ?? true)) {
                    Log::warning('Factus: no hay rango de factura, usando primer activo', ['id' => $range['id']]);
                    return (int) $range['id'];
                }
            }

            Log::warning('Factus: ningún rango activo encontrado, usando valor de .env');
            return (int) config('services.factus.numbering_range_id', 4);
        });
    }

    /**
     * Busca el municipality_id de Factus por nombre de ciudad.
     * Resultado cacheado por nombre para evitar repetidas llamadas.
     */
    public function getMunicipalityId(string $cityName): ?int
    {
        $cacheKey = 'factus_municipality_' . md5(strtolower($cityName));

        return Cache::remember($cacheKey, now()->addDay(), function () use ($cityName) {
            $response = $this->get('/v1/municipalities?name=' . urlencode($cityName));
            $data     = $this->handleResponse($response);
            $list     = $data['data'] ?? $data;

            if (!empty($list)) {
                Log::info('Factus: municipio encontrado', ['name' => $cityName, 'id' => $list[0]['id']]);
                return (int) $list[0]['id'];
            }

            Log::warning('Factus: municipio no encontrado', ['name' => $cityName]);
            return null;
        });
    }

    // ─── Response handler ────────────────────────────────────────────────────

    protected function handleResponse(Response $response): array
    {
        if ($response->successful()) {
            return $response->json() ?: [];
        }

        // Token expirado → limpiar caché para forzar re-autenticación
        if ($response->status() === 401) {
            Cache::forget(self::TOKEN_CACHE_KEY);
            Cache::forget(self::REFRESH_CACHE_KEY);
        }

        throw new FactusApiException(
            'Factus API Error: ' . $response->body(),
            $response->status()
        );
    }
}
