<?php

namespace App\Auth\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Services\TwoFactorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class Verify2FA extends Component
{
    public $code = '';
    public $errorMessage = '';
    public $resendMessage = '';
    public $attemptsRemaining = 3;

    protected $rules = [
        'code' => 'required|string|size:6',
    ];

    public function mount()
    {
        // Verificar que hay un usuario pendiente de 2FA en sesión
        if (!Session::has('2fa_user_id')) {
            return redirect()->route('login');
        }

        // Verificar si el usuario está bloqueado
        $user = \App\Models\User::find(Session::get('2fa_user_id'));
        if ($user && $user->isTwoFactorLocked()) {
            $this->errorMessage = 'Cuenta bloqueada temporalmente. Intente nuevamente en 15 minutos.';
        }
    }

    public function verify()
    {
        $this->validate();

        $userId = Session::get('2fa_user_id');
        $user = \App\Models\User::find($userId);

        if (!$user) {
            $this->errorMessage = 'Sesión expirada. Por favor inicie sesión nuevamente.';
            return redirect()->route('login');
        }

        // Verificar si está bloqueado
        if ($user->isTwoFactorLocked()) {
            $this->errorMessage = 'Cuenta bloqueada temporalmente. Intente nuevamente en 15 minutos.';
            return;
        }

        $twoFactorService = app(TwoFactorService::class);

        try {
            $isValid = $twoFactorService->verifyCode($user, $this->code, $user->two_factor_type);

            if ($isValid) {
                // Autenticar al usuario
                Auth::login($user);
                Session::forget('2fa_user_id');

                // Verificar si el usuario es Super Administrador
                if ($user->isSuperAdmin()) {
                    return redirect()->route('super.admin.dashboard');
                }

                // Verificar si el usuario es TAT (profile_id = 17)
                if ($user->profile_id == 17) {
                    return redirect()->route('petty-cash.petty-cash');
                }

                // Redirigir a selección de tenant para usuarios normales
                return redirect()->route('tenant.select');
            } else {
                $this->attemptsRemaining = 3 - $user->two_factor_failed_attempts;
                $this->errorMessage = "Código inválido o expirado. Intentos restantes: {$this->attemptsRemaining}";
                $this->code = '';
            }
        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function resendCode()
    {
        $userId = Session::get('2fa_user_id');
        $user = \App\Models\User::find($userId);

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->isTwoFactorLocked()) {
            $this->errorMessage = 'Cuenta bloqueada temporalmente.';
            return;
        }

        try {
            $twoFactorService = app(TwoFactorService::class);
            $twoFactorService->sendCode($user, $user->two_factor_type);
            $this->resendMessage = 'Código reenviado exitosamente.';
            $this->errorMessage = '';
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al reenviar el código.';
        }
    }

    #[Layout('layouts.guest')]
    public function render()
    {
        return view('livewire.auth.verify2-f-a');
    }
}
