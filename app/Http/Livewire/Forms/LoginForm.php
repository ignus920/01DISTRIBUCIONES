<?php

namespace App\Http\Livewire\Forms;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class LoginForm extends Form
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    #[Validate('boolean')]
    public bool $remember = false;

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        if (! Auth::attempt($this->only(['email', 'password']), $this->remember)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'form.email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        // Verificar si el usuario tiene 2FA habilitado
        $user = Auth::user();

        if ($user && $user->hasTwoFactorEnabled()) {
            // Cerrar la sesión temporal
            Auth::logout();

            // Guardar el ID del usuario en sesión para verificar 2FA
            session(['2fa_user_id' => $user->id]);

            // Solo enviar código si NO es TOTP (Google Authenticator)
            if ($user->two_factor_type !== 'totp') {
                $twoFactorService = app(\App\Services\TwoFactorService::class);
                $twoFactorService->sendCode($user, $user->two_factor_type);
            }

            // Mensaje según el tipo de autenticación
            $mensaje = match($user->two_factor_type) {
                'email' => 'Código de verificación enviado. Por favor revise su correo electrónico.',
                'whatsapp' => 'Código de verificación enviado. Por favor revise su WhatsApp.',
                'totp' => 'Por favor ingrese el código de su aplicación Google Authenticator.',
                default => 'Por favor ingrese el código de verificación.'
            };

            // Lanzar excepción para redirigir a verificación 2FA
            throw ValidationException::withMessages([
                'form.email' => $mensaje,
            ]);
        }
    }

    /**
     * Ensure the authentication request is not rate limited.
     */
    protected function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout(request()));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'form.email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the authentication rate limiting throttle key.
     */
    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }
}
