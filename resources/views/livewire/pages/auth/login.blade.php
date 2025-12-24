<?php

use App\Http\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        try {
            $this->form->authenticate();

            // Si llegamos aquí, no hay 2FA o ya fue validado
            Session::regenerate();

            // Mostrar notificación de bienvenida
            $userName = auth()->user()->name ?? 'Usuario';
            $this->dispatch('login-success', [
                'title' => '¡Bienvenido!',
                'message' => "Hola {$userName}, has iniciado sesión correctamente.",
                'icon' => 'success'
            ]);

            // Verificar si ya hay 2FA pendiente
            if (Session::has('2fa_user_id')) {
                $this->redirect(route('verify.2fa'), navigate: true);
                return;
            }

            // Verificar si el usuario es Super Administrador
            if (auth()->user()->isSuperAdmin()) {
                $this->redirect(route('super.admin.dashboard'), navigate: true);
                return;
            }

            // Redirigir a selección de tenant para usuarios normales
            $this->redirect(route('tenant.select'), navigate: true);
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Si hay 2FA habilitado, redirigir a verificación
            if (Session::has('2fa_user_id')) {
                $this->redirect(route('verify.2fa'), navigate: true);
                return;
            }

            // Capturar el mensaje de error y mostrar notificación SweetAlert
            $errors = $e->errors();
            if (isset($errors['form.email'][0])) {
                $message = $errors['form.email'][0];

                // Determinar el tipo de error y mostrar notificación personalizada
                if (str_contains($message, 'bloqueado temporalmente')) {
                    $this->dispatch('login-error', [
                        'title' => 'Demasiados intentos',
                        'message' => $message,
                        'icon' => 'warning'
                    ]);
                } else {
                    $this->dispatch('login-error', [
                        'title' => 'Credenciales incorrectas',
                        'message' => $message,
                        'icon' => 'error'
                    ]);
                }

                // Limpiar el mensaje de error para evitar mostrar el mensaje estándar
                $e->withMessages(['form.email' => '']);
            }

            throw $e;
        }
    }
}; ?>

<div class="flex min-h-screen flex-col justify-center px-6 py-12 lg:px-8 lg:py-20" x-data="{
    init() {
        // Listener para errores de login
        Livewire.on('login-error', (data) => {
            Swal.fire({
                title: data[0].title,
                text: data[0].message,
                icon: data[0].icon,
                confirmButtonText: 'Entendido',
                confirmButtonColor: '#4F46E5',
                background: '#ffffff',
                color: '#111827',
                customClass: {
                    popup: 'swal-popup-light',
                    title: 'swal-title-light',
                    content: 'swal-content-light'
                }
            });
        });

        // Listener para login exitoso
        Livewire.on('login-success', (data) => {
            Swal.fire({
                title: data[0].title,
                text: data[0].message,
                icon: data[0].icon,
                timer: 2000,
                timerProgressBar: true,
                showConfirmButton: false,
                background: '#ffffff',
                color: '#111827',
                customClass: {
                    popup: 'swal-popup-light',
                    title: 'swal-title-light',
                    content: 'swal-content-light'
                }
            });
        });
    }
}">
    <!-- Header -->
    <div class="sm:mx-auto sm:w-full sm:max-w-sm">
        <div class="mx-auto h-10 w-10 flex items-center justify-center bg-indigo-600 rounded-lg">
            <svg class="h-6 w-6 text-white" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 21v-7.5a.75.75 0 01.75-.75h3a.75.75 0 01.75.75V21m-4.5 0H2.36m11.14 0H18m0 0h3.64m-1.39 0V9.349m-1.25 0V3.75a.75.75 0 00-.75-.75H14.25a.75.75 0 00-.75.75V4.5" />
            </svg>
        </div>
        <h2 class="mt-10 text-center text-2xl/9 font-bold tracking-tight text-gray-900 dark:text-white">
            Iniciar sesión en tu cuenta
        </h2>
    </div>

    <!-- Form -->
    <div class="mt-10 sm:mx-auto sm:w-full sm:max-w-sm">
        <!-- Session Status -->
        <x-auth-session-status class="mb-4" :status="session('status')" />

        <form wire:submit="login" class="space-y-6">
            <!-- Email Address -->
            <div>
                <label for="email" class="block text-sm/6 font-medium text-gray-900 dark:text-gray-100">
                    Correo electrónico
                </label>
                <div class="mt-2">
                    <input
                        wire:model="form.email"
                        id="email"
                        type="email"
                        name="email"
                        required
                        autofocus
                        autocomplete="email"
                        class="block w-full rounded-md bg-white dark:bg-white/5 px-3 py-1.5 text-base text-gray-900 dark:text-white outline-1 -outline-offset-1 outline-gray-300 dark:outline-white/10 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
                        placeholder="tu@email.com"
                    />
                    <x-input-error :messages="$errors->get('form.email')" class="mt-2" />
                </div>
            </div>

            <!-- Password -->
            <div>
                <div class="flex items-center justify-between">
                    <label for="password" class="block text-sm/6 font-medium text-gray-900 dark:text-gray-100">
                        Contraseña
                    </label>
                    @if (Route::has('password.request'))
                        <div class="text-sm">
                            <a href="{{ route('password.request') }}" wire:navigate class="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">
                                ¿Olvidaste tu contraseña?
                            </a>
                        </div>
                    @endif
                </div>
                <div class="mt-2 relative" x-data="{ showPassword: false }">
                    <input
                        wire:model="form.password"
                        id="password"
                        :type="showPassword ? 'text' : 'password'"
                        name="password"
                        required
                        autocomplete="current-password"
                        class="block w-full rounded-md bg-white dark:bg-white/5 px-3 py-1.5 pr-12 text-base text-gray-900 dark:text-white outline-1 -outline-offset-1 outline-gray-300 dark:outline-white/10 placeholder:text-gray-400 dark:placeholder:text-gray-500 focus:outline-2 focus:-outline-offset-2 focus:outline-indigo-600 sm:text-sm/6"
                        placeholder="••••••••"
                    />
                    <button
                        type="button"
                        @click="showPassword = !showPassword"
                        class="absolute inset-y-0 right-0 flex items-center pr-3 text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300 transition-colors"
                        :aria-label="showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                    >
                        <!-- Icono de ojo abierto (mostrar) -->
                        <svg x-show="!showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        <!-- Icono de ojo cerrado (ocultar) -->
                        <svg x-show="showPassword" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 11-4.243-4.243m4.242 4.242L9.88 9.88" />
                        </svg>
                    </button>
                    <x-input-error :messages="$errors->get('form.password')" class="mt-2" />
                </div>
            </div>

            <!-- Remember Me -->
            <div class="flex items-center">
                <input
                    wire:model="form.remember"
                    id="remember"
                    type="checkbox"
                    name="remember"
                    class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600 dark:border-gray-600 dark:bg-gray-700 dark:focus:ring-indigo-500"
                >
                <label for="remember" class="ml-3 block text-sm/6 text-gray-900 dark:text-gray-100">
                    Recordarme
                </label>
            </div>

            <!-- Submit Button -->
            <div>
                <button
                    type="submit"
                    class="flex w-full justify-center items-center rounded-md bg-indigo-600 px-3 py-1.5 text-sm/6 font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600 disabled:opacity-50 min-h-[40px]"
                    wire:loading.attr="disabled"
                >
                    <div class="flex items-center justify-center">
                        <svg wire:loading class="animate-spin h-4 w-4 text-white mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove>Iniciar sesión</span>
                        <span wire:loading>Iniciando sesión...</span>
                    </div>
                </button>
            </div>
        </form>

        <!-- Register Link -->
        @if (Route::has('register'))
            <p class="mt-10 text-center text-sm/6 text-gray-500 dark:text-gray-400">
                ¿No tienes una cuenta?
                <a href="{{ url('/') }}" wire:navigate class="font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300">
                    Ir a página principal
                </a>
            </p>
        @endif
    </div>

    <!-- Estilos CSS para SweetAlert2 - Siempre modo claro -->
    <style>
        /* Estilos personalizados para SweetAlert2 - Forzar modo claro */
        .swal-popup-light {
            background-color: white !important;
            border-radius: 8px !important;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04) !important;
        }

        .swal-title-light {
            color: #111827 !important;
            font-weight: 600 !important;
        }

        .swal-content-light {
            color: #374151 !important;
        }

        /* Forzar colores claros para todos los elementos internos */
        .swal2-popup {
            background-color: white !important;
            color: #111827 !important;
        }

        .swal2-title {
            color: #111827 !important;
        }

        .swal2-content {
            color: #374151 !important;
        }

        .swal2-confirm {
            background-color: #4F46E5 !important;
            color: white !important;
        }

        /* Estilos para el icono de éxito */
        .swal2-success-circular-line-left,
        .swal2-success-circular-line-right {
            background-color: #10B981 !important;
        }

        .swal2-success-fix {
            background-color: #10B981 !important;
        }

        .swal2-timer-progress-bar {
            background-color: #10B981 !important;
        }
    </style>
</div>
