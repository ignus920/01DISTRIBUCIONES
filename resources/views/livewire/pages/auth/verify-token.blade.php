<?php

use App\Models\Auth\User;
use App\Services\WhatsApp\WhatsAppService;
use App\Mail\WhatsAppTokenMail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Carbon\Carbon;

new #[Layout('layouts.guest')] class extends Component
{
    public string $token = '';
    public string $errorMessage = '';
    public string $successMessage = '';
    public bool $isLoading = false;
    public bool $isResending = false;
    public ?string $lastResendAt = null;
    public int $cooldownSeconds = 0;

    /**
     * Verificar el token de WhatsApp ingresado
     */
    public function verifyWhatsAppToken(): void
    {
        $this->validate([
            'token' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ], [
            'token.required' => 'El código de verificación es obligatorio.',
            'token.size' => 'El código debe tener exactamente 6 dígitos.',
            'token.regex' => 'El código debe contener solo números.',
        ]);

        $this->isLoading = true;
        $this->errorMessage = '';
        $this->successMessage = '';

        try {
            Log::info('🔐 Verificando token de WhatsApp', ['token' => $this->token]);

            // Buscar usuario por token
            $user = User::where('whatsapp_token', $this->token)
                ->whereNotNull('whatsapp_token')
                ->whereNotNull('whatsapp_token_expires_at')
                ->first();

            if (!$user) {
                $this->errorMessage = 'Código incorrecto. Por favor verifica e intenta nuevamente.';
                $this->isLoading = false;
                return;
            }

            // Verificar si el token ha expirado
            if (Carbon::now()->greaterThan($user->whatsapp_token_expires_at)) {
                $this->errorMessage = 'El código ha expirado. Por favor solicita un nuevo código desde el registro.';
                $this->isLoading = false;
                return;
            }

            // Token válido - activar cuenta y limpiar token
            $user->update([
                'email_verified_at' => Carbon::now(),
                'whatsapp_token' => null,
                'whatsapp_token_expires_at' => null,
            ]);

            Log::info('✅ Token de WhatsApp verificado exitosamente', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);

            // Autenticar al usuario
            Auth::login($user);

            session()->flash('status', '¡Cuenta verificada exitosamente! Bienvenido a la plataforma.');

            // Redirigir a completar configuración de empresa
            $this->redirect(route('company.setup'), navigate: true);

        } catch (\Exception $e) {
            Log::error('❌ Error verificando token de WhatsApp', [
                'token' => $this->token,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->errorMessage = 'Error al verificar el código. Por favor intenta nuevamente.';
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Reenviar código (redirigir al registro para generar nuevo token)
     */
    public function resendCode(): void
    {
        session()->flash('info', 'Para solicitar un nuevo código, por favor vuelve al registro.');
        $this->redirect(route('register'), navigate: true);
    }
}; ?>

<div class="flex min-h-screen flex-col justify-center px-6 py-12 lg:px-8 lg:py-20">
    <!-- Header -->
    <div class="sm:mx-auto sm:w-full sm:max-w-sm">
        <div class="mx-auto h-10 w-10 flex items-center justify-center bg-green-600 rounded-lg">
            <svg class="h-6 w-6 text-white" fill="currentColor" viewBox="0 0 24 24">
                <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893A11.821 11.821 0 0020.885 3.488"/>
            </svg>
        </div>
        <h2 class="mt-10 text-center text-2xl/9 font-bold tracking-tight text-gray-900 dark:text-white">
            Verificar tu cuenta
        </h2>
        <p class="mt-4 text-center text-sm text-gray-600 dark:text-gray-400">
            Hemos enviado un código de verificación de 6 dígitos tanto a tu número de WhatsApp como a tu correo electrónico. Por favor, ingresa el código que recibiste para verificar tu cuenta.
        </p>
    </div>

    <!-- Form -->
    <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <!-- Mensajes de error y éxito -->
        @if($errorMessage)
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-red-800">{{ $errorMessage }}</p>
                    </div>
                </div>
            </div>
        @endif

        <form wire:submit="verifyWhatsAppToken" class="space-y-6">
            <!-- Código de Verificación -->
            <div>
                <label for="token" class="block text-sm/6 font-medium text-gray-900 dark:text-gray-100">
                    Código de verificación
                </label>
                <div class="mt-2">
                    <input
                        wire:model="token"
                        id="token"
                        type="text"
                        name="token"
                        required
                        autofocus
                        maxlength="6"
                        pattern="[0-9]{6}"
                        class="block w-full rounded-md bg-white dark:bg-white/5 px-3 py-1.5 text-base text-gray-900 dark:text-white outline-1 -outline-offset-1 outline-gray-300 dark:outline-white/10 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-green-600 sm:text-sm/6 text-center text-2xl tracking-wider font-mono"
                        placeholder="123456"
                    />
                    <x-input-error :messages="$errors->get('token')" class="mt-2" />
                </div>
                <p class="mt-2 text-xs text-gray-500 dark:text-gray-400 text-center">
                    Ingresa el código de 6 dígitos que recibiste por WhatsApp o correo electrónico
                </p>
            </div>

            <!-- Submit Button -->
            <div>
                <button
                    type="submit"
                    class="flex w-full justify-center items-center rounded-md bg-green-600 px-3 py-1.5 text-sm/6 font-semibold text-white shadow-sm hover:bg-green-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-green-600 disabled:opacity-50 min-h-[40px]"
                    wire:loading.attr="disabled"
                >
                    <div class="flex items-center justify-center">
                        <svg wire:loading class="animate-spin h-4 w-4 text-white mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove>Verificar código</span>
                        <span wire:loading>Verificando...</span>
                    </div>
                </button>
            </div>
        </form>

        <!-- Links -->
        <div class="mt-6 space-y-4">
            <!-- Reenviar código -->
            <div class="text-center">
                <button type="button" wire:click="resendCode" class="text-sm text-green-600 hover:text-green-500 dark:text-green-400 dark:hover:text-green-300 font-medium">
                    ¿No recibiste el código? Solicitar nuevo código
                </button>
            </div>

           
           
        </div>
    </div>
</div>
