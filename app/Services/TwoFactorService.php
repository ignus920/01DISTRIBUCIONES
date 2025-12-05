<?php

namespace App\Services;

use App\Models\Auth\User;
use App\Models\Auth\TwoFactorCode;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Http;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    /**
     * Genera un código de 6 dígitos.
     */
    public function generateCode(): string
    {
        return str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Crea y envía un código de verificación 2FA.
     */
    public function sendCode(User $user, string $type = 'email'): TwoFactorCode
    {
        // Invalidar códigos anteriores
        $user->twoFactorCodes()->where('is_used', false)->update(['is_used' => true]);

        // Generar nuevo código
        $code = $this->generateCode();

        // Crear registro del código
        $twoFactorCode = $user->twoFactorCodes()->create([
            'code' => $code,
            'type' => $type,
            'expires_at' => Carbon::now()->addMinutes(5),
        ]);

        // Enviar código según el tipo
        match ($type) {
            'email' => $this->sendByEmail($user, $code),
            'whatsapp' => $this->sendByWhatsApp($user, $code),
            default => $this->sendByEmail($user, $code),
        };

        return $twoFactorCode;
    }

    /**
     * Envía el código por correo electrónico.
     */
    protected function sendByEmail(User $user, string $code): void
    {
        Mail::send('emails.two-factor-code', ['code' => $code, 'user' => $user], function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Código de verificación 2FA');
        });
    }

    /**
     * Envía el código por WhatsApp usando API.
     */
    protected function sendByWhatsApp(User $user, string $code): void
    {
        $apiUrl = config('services.whatsapp.api_url');
        $apiToken = config('services.whatsapp.api_token');

        if (!$apiUrl || !$apiToken) {
            throw new \Exception('WhatsApp API no configurada');
        }

        Http::withHeaders([
            'Authorization' => "Bearer {$apiToken}",
        ])->post($apiUrl, [
            'phone' => $user->phone,
            'message' => "Tu código de verificación es: {$code}. Válido por 5 minutos.",
        ]);
    }

    /**
     * Verifica un código de autenticación.
     */
    public function verifyCode(User $user, string $code, string $type = 'email'): bool
    {
        // Verificar si el usuario está bloqueado
        if ($user->isTwoFactorLocked()) {
            throw new \Exception('Usuario bloqueado temporalmente por intentos fallidos');
        }

        // Si es TOTP (Google Authenticator), validar de manera diferente
        if ($type === 'totp') {
            return $this->verifyTOTP($user, $code);
        }

        // Buscar código válido
        $twoFactorCode = $user->twoFactorCodes()
            ->where('code', $code)
            ->where('type', $type)
            ->valid()
            ->first();

        if (!$twoFactorCode) {
            $user->incrementTwoFactorAttempts();
            return false;
        }

        // Marcar código como usado
        $twoFactorCode->markAsUsed();
        $user->resetTwoFactorAttempts();

        return true;
    }

    /**
     * Verifica un código TOTP de Google Authenticator.
     */
    protected function verifyTOTP(User $user, string $code): bool
    {
        if (!$user->two_factor_secret) {
            throw new \Exception('Usuario no tiene Google Authenticator configurado');
        }

        $google2fa = new Google2FA();

        $valid = $google2fa->verifyKey($user->two_factor_secret, $code);

        if (!$valid) {
            $user->incrementTwoFactorAttempts();
            return false;
        }

        $user->resetTwoFactorAttempts();
        return true;
    }

    /**
     * Genera un secreto para Google Authenticator.
     */
    public function generateTOTPSecret(): string
    {
        $google2fa = new Google2FA();
        return $google2fa->generateSecretKey();
    }

    /**
     * Obtiene la URL del QR para Google Authenticator.
     */
    public function getTOTPQRCodeUrl(User $user, string $secret): string
    {
        $google2fa = new Google2FA();
        $companyName = config('app.name');

        return $google2fa->getQRCodeUrl(
            $companyName,
            $user->email,
            $secret
        );
    }

    /**
     * Habilita 2FA para un usuario.
     */
    public function enable(User $user, string $type = 'email', ?string $secret = null): void
    {
        $user->update([
            'two_factor_enabled' => true,
            'two_factor_type' => $type,
            'two_factor_secret' => $secret,
        ]);
    }

    /**
     * Deshabilita 2FA para un usuario.
     */
    public function disable(User $user): void
    {
        $user->update([
            'two_factor_enabled' => false,
            'two_factor_type' => 'email',
            'two_factor_secret' => null,
        ]);

        // Invalidar todos los códigos pendientes
        $user->twoFactorCodes()->where('is_used', false)->update(['is_used' => true]);
    }
}
