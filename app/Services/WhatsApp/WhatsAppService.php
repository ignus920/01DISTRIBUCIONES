<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class WhatsAppService
{
    protected string $apiUrl;
    protected string $jwtSecret;
    protected string $defaultTemplate;
    protected string $defaultImage;
    protected string $empresaEmail;

    public function __construct()
    {
        $this->apiUrl = config('whatsapp.api_url', 'https://api.sersia.co:3010/api/dosil_code');
        $this->jwtSecret = config('whatsapp.jwt_secret', 'dosil_by_ticsia');
        $this->defaultTemplate = config('whatsapp.default_template', 'dosil_code1');
        $this->defaultImage = config('whatsapp.default_image', 'https://dosil.co/files/img/logop.png');
        $this->empresaEmail = config('whatsapp.empresa_email', 'clozano@ticsia.com');
    }

    /**
     * Enviar mensaje de código de verificación por WhatsApp
     *
     * @param string $telCliente Teléfono del cliente
     * @param string $cliente Nombre del cliente
     * @param string $codigo Código de verificación
     * @param string $telEmpresa Teléfono de la empresa
     * @param string $company Nombre de la empresa
     * @return array
     */
    public function enviarCodigoVerificacion(
        string $telCliente,
        string $cliente,
        string $codigo,
        string $telEmpresa,
        string $company
    ): array {
        try {
            // Validar que los datos no estén vacíos
            if (empty($telCliente) || empty($cliente) || empty($codigo) || empty($telEmpresa) || empty($company)) {
                Log::warning('📱 Datos incompletos para envío de WhatsApp', [
                    'telCliente' => $telCliente,
                    'cliente' => $cliente,
                    'codigo' => $codigo,
                    'telEmpresa' => $telEmpresa,
                    'company' => $company
                ]);
                return ['success' => false, 'error' => 'Datos incompletos'];
            }

            // Formatear número de teléfono (agregar código de país si no lo tiene)
            $telCliente = $this->formatearTelefono($telCliente);

            // Generar token JWT
            $token = $this->generarToken();

            Log::info('📱 Preparando envío de código por WhatsApp', [
                'telCliente' => $telCliente,
                'cliente' => $cliente,
                'codigo' => $codigo,
                'company' => $company
            ]);

            // Preparar datos para la API
            $postData = [
                'numero' => $telCliente,
                'nombre' => $cliente,
                'codigo' => $codigo,
                'company' => $company,
                'tel_empresa' => $telEmpresa,
                'plantilla' => $this->defaultTemplate,
                'imagen' => $this->defaultImage
            ];

            // Realizar solicitud HTTP (con manejo de SSL/certificados)
            $response = Http::timeout(30)
                ->withOptions([
                    'verify' => false, // Desactivar verificación SSL si hay problemas
                    'timeout' => 30,
                    'connect_timeout' => 10,
                ])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json'
                ])
                ->post($this->apiUrl, $postData);

            // Verificar si la solicitud fue exitosa
            if ($response->successful()) {
                Log::info('✅ Código de WhatsApp enviado exitosamente', [
                    'telCliente' => $telCliente,
                    'response' => $response->json()
                ]);

                return [
                    'success' => true,
                    'data' => $response->json(),
                    'message' => 'Código enviado exitosamente'
                ];
            } else {
                Log::error('❌ Error en respuesta de API de WhatsApp', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'telCliente' => $telCliente
                ]);

                return [
                    'success' => false,
                    'error' => 'Error en el servicio de WhatsApp',
                    'details' => $response->body()
                ];
            }

        } catch (\Exception $e) {
            Log::error('❌ Excepción al enviar código por WhatsApp', [
                'telCliente' => $telCliente,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Error interno al enviar mensaje',
                'details' => $e->getMessage()
            ];
        }
    }

    /**
     * Generar token JWT para autenticación
     *
     * @return string
     */
    protected function generarToken(): string
    {
        $payload = [
            'usuario' => $this->empresaEmail,
            'rol' => 'cliente',
            'exp' => time() + (60 * 60), // Expira en 1 hora
            'iat' => time() // Issued at
        ];

        return JWT::encode($payload, $this->jwtSecret, 'HS256');
    }

    /**
     * Formatear número de teléfono
     *
     * @param string $telefono
     * @return string
     */
    protected function formatearTelefono(string $telefono): string
    {
        // Remover espacios y caracteres especiales
        $telefono = preg_replace('/[^0-9]/', '', $telefono);

        // Si el número tiene 10 dígitos y empieza con 3 (Colombia)
        if (strlen($telefono) === 10 && substr($telefono, 0, 1) === '3') {
            return '+57' . $telefono; // Agregar código de país de Colombia
        }

        // Si ya tiene código de país, devolverlo como está
        if (strlen($telefono) > 10) {
            return '+' . $telefono;
        }

        // En otros casos, devolver tal como está
        return $telefono;
    }

    /**
     * Validar si un número de teléfono es válido para WhatsApp
     *
     * @param string $telefono
     * @return bool
     */
    public function validarTelefono(string $telefono): bool
    {
        $telefono = preg_replace('/[^0-9]/', '', $telefono);

        // Validar números colombianos (10 dígitos que empiecen con 3)
        if (strlen($telefono) === 10 && substr($telefono, 0, 1) === '3') {
            return true;
        }

        // Validar números internacionales (más de 10 dígitos)
        if (strlen($telefono) > 10 && strlen($telefono) <= 15) {
            return true;
        }

        return false;
    }

    /**
     * Obtener el número formateado para mostrar al usuario
     *
     * @param string $telefono
     * @return string
     */
    public function formatearParaMostrar(string $telefono): string
    {
        $telefono = preg_replace('/[^0-9]/', '', $telefono);

        if (strlen($telefono) <= 4) {
            return $telefono;
        }

        // Enmascarar número: mostrar primeros 2 y últimos 2 dígitos
        return substr($telefono, 0, 2) . str_repeat('*', strlen($telefono) - 4) . substr($telefono, -2);
    }
}