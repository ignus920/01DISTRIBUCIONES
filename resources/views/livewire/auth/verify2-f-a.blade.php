<div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="max-w-md w-full bg-white shadow-lg rounded-lg p-8">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-900">Verificación de Dos Factores</h2>
            <p class="mt-2 text-sm text-gray-600">
                Se ha enviado un código de verificación a su dispositivo.
            </p>
        </div>

        @if ($errorMessage)
            <div class="mb-4 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded">
                {{ $errorMessage }}
            </div>
        @endif

        @if ($resendMessage)
            <div class="mb-4 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded">
                {{ $resendMessage }}
            </div>
        @endif

        <form wire:submit="verify">
            <div class="mb-4">
                <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                    Código de Verificación
                </label>
                <input
                    type="text"
                    id="code"
                    wire:model="code"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    placeholder="000000"
                    class="w-full px-4 py-3 text-center text-2xl tracking-widest border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    autofocus
                    required
                />
                @error('code')
                    <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                @enderror
            </div>

            <button
                type="submit"
                class="w-full bg-indigo-600 text-white py-3 px-4 rounded-lg font-semibold hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition-colors"
            >
                Verificar Código
            </button>
        </form>

        <div class="mt-6 text-center">
            <button
                wire:click="resendCode"
                class="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
            >
                ¿No recibió el código? Reenviar
            </button>
        </div>

        <div class="mt-4 text-center">
            <a href="{{ route('login') }}" class="text-sm text-gray-600 hover:text-gray-800">
                ← Volver al inicio de sesión
            </a>
        </div>
    </div>
</div>
