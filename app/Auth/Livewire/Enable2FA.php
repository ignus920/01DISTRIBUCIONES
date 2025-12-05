<?php

namespace App\Auth\Livewire;

use Livewire\Component;
use App\Services\TwoFactorService;
use Illuminate\Support\Facades\Auth;

class Enable2FA extends Component
{
    public $twoFactorEnabled;
    public $twoFactorType = 'email';
    public $phone = '';
    public $qrCodeUrl = '';
    public $secret = '';
    public $showQrCode = false;
    public $verificationCode = '';
    public $successMessage = '';
    public $errorMessage = '';

    protected $rules = [
        'twoFactorType' => 'required|in:email,whatsapp,totp',
        'phone' => 'required_if:twoFactorType,whatsapp|nullable|string|max:20',
        'verificationCode' => 'required|string|size:6',
    ];

    public function mount()
    {
        $user = Auth::user();
        $this->twoFactorEnabled = $user->two_factor_enabled;
        $this->twoFactorType = $user->two_factor_type ?? 'email';
        $this->phone = $user->phone ?? '';
    }

    public function enableTwoFactor()
    {
        $this->validate([
            'twoFactorType' => 'required|in:email,whatsapp,totp',
            'phone' => 'required_if:twoFactorType,whatsapp|nullable|string|max:20',
        ]);

        $user = Auth::user();
        $twoFactorService = app(TwoFactorService::class);

        try {
            // Si es WhatsApp, actualizar teléfono
            if ($this->twoFactorType === 'whatsapp') {
                $user->update(['phone' => $this->phone]);
            }

            // Si es TOTP, generar secreto y QR
            if ($this->twoFactorType === 'totp') {
                $this->secret = $twoFactorService->generateTOTPSecret();
                $this->qrCodeUrl = $twoFactorService->getTOTPQRCodeUrl($user, $this->secret);
                $this->showQrCode = true;
                $this->successMessage = 'Escanee el código QR con Google Authenticator y luego ingrese el código de 6 dígitos para verificar.';
                return;
            }

            // Enviar código de verificación
            $twoFactorService->sendCode($user, $this->twoFactorType);
            $this->successMessage = 'Se ha enviado un código de verificación. Por favor ingréselo para activar 2FA.';
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al habilitar 2FA: ' . $e->getMessage();
        }
    }

    public function verifyAndEnable()
    {
        $this->validate(['verificationCode' => 'required|string|size:6']);

        $user = Auth::user();
        $twoFactorService = app(TwoFactorService::class);

        try {
            // Verificar código según el tipo
            if ($this->twoFactorType === 'totp') {
                // Para TOTP, crear un usuario temporal con el secret
                $tempUser = clone $user;
                $tempUser->two_factor_secret = $this->secret;
                $isValid = $twoFactorService->verifyCode($tempUser, $this->verificationCode, 'totp');
            } else {
                $isValid = $twoFactorService->verifyCode($user, $this->verificationCode, $this->twoFactorType);
            }

            if ($isValid) {
                // Habilitar 2FA
                $secret = $this->twoFactorType === 'totp' ? $this->secret : null;
                $twoFactorService->enable($user, $this->twoFactorType, $secret);

                $this->twoFactorEnabled = true;
                $this->successMessage = '¡Autenticación de dos factores habilitada exitosamente!';
                $this->errorMessage = '';
                $this->showQrCode = false;
                $this->verificationCode = '';
            } else {
                $this->errorMessage = 'Código inválido. Intente nuevamente.';
                $this->verificationCode = '';
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Error: ' . $e->getMessage();
        }
    }

    public function disableTwoFactor()
    {
        $user = Auth::user();
        $twoFactorService = app(TwoFactorService::class);

        try {
            $twoFactorService->disable($user);
            $this->twoFactorEnabled = false;
            $this->successMessage = 'Autenticación de dos factores deshabilitada.';
            $this->errorMessage = '';
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al deshabilitar 2FA: ' . $e->getMessage();
        }
    }

    public function render()
    {
        return view('livewire.auth.enable2-f-a');
    }
}
