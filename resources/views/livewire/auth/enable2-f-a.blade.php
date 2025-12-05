<div>
    <div>
        <div>
            <div>
                <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-1">Autenticación de Dos Factores</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Agrega una capa extra de seguridad a tu cuenta.</p>

                @if (!empty($successMessage))
                    <div class="mb-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-3 rounded-lg">
                        {{ $successMessage }}
                    </div>
                @endif

                @if (!empty($errorMessage))
                    <div class="mb-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-3 rounded-lg">
                        {{ $errorMessage }}
                    </div>
                @endif

                @if (!$twoFactorEnabled)
                    <!-- Habilitar 2FA -->
                    <div class="mb-6">

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Método de Autenticación
                                </label>

                                <div class="space-y-2">
                                    <label class="flex items-center p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <input type="radio" wire:model="twoFactorType" value="email" class="mr-3 text-indigo-600 dark:text-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400">
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-gray-100">Correo Electrónico</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">Recibir código por correo</div>
                                        </div>
                                    </label>

                                    <label class="flex items-center p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <input type="radio" wire:model="twoFactorType" value="whatsapp" class="mr-3 text-indigo-600 dark:text-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400">
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-gray-100">WhatsApp</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">Recibir código por WhatsApp</div>
                                        </div>
                                    </label>

                                    <label class="flex items-center p-3 border border-gray-200 dark:border-gray-600 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <input type="radio" wire:model="twoFactorType" value="totp" class="mr-3 text-indigo-600 dark:text-indigo-400 focus:ring-indigo-500 dark:focus:ring-indigo-400">
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-gray-100">Google Authenticator</div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">Usar aplicación de autenticación</div>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            @if ($twoFactorType === 'whatsapp')
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Número de Teléfono
                                    </label>
                                    <input
                                        type="text"
                                        id="phone"
                                        wire:model="phone"
                                        placeholder="+57 300 123 4567"
                                        class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400 placeholder-gray-500 dark:placeholder-gray-400"
                                    />
                                    @error('phone')
                                        <span class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                            @endif

                            <button
                                wire:click="enableTwoFactor"
                                class="w-full bg-indigo-600 dark:bg-indigo-500 text-white py-3 px-4 rounded-lg font-semibold hover:bg-indigo-700 dark:hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors"
                            >
                                Habilitar Autenticación de Dos Factores
                            </button>
                        </div>
                    </div>

                    @if ($showQrCode)
                        <!-- Mostrar código QR para TOTP -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-4">Configurar Google Authenticator</h3>

                            <div class="bg-gray-50 dark:bg-gray-700 p-6 rounded-lg mb-4 text-center border border-gray-200 dark:border-gray-600">
                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">
                                    Escanee este código QR con Google Authenticator:
                                </p>
                                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrCodeUrl) }}" alt="QR Code" class="mx-auto">
                            </div>

                            <div class="mb-4">
                                <label for="verificationCode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Código de Verificación
                                </label>
                                <input
                                    type="text"
                                    id="verificationCode"
                                    wire:model="verificationCode"
                                    maxlength="6"
                                    placeholder="000000"
                                    class="w-full px-4 py-2 text-center text-xl tracking-widest border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400 placeholder-gray-500 dark:placeholder-gray-400"
                                />
                                @error('verificationCode')
                                    <span class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <button
                                wire:click="verifyAndEnable"
                                class="w-full bg-green-600 dark:bg-green-500 text-white py-3 px-4 rounded-lg font-semibold hover:bg-green-700 dark:hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors"
                            >
                                Verificar y Activar
                            </button>
                        </div>
                    @endif

                    @if (!empty($successMessage) && !$showQrCode && ($twoFactorType === 'email' || $twoFactorType === 'whatsapp'))
                        <!-- Formulario de verificación para Email/WhatsApp -->
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-6">
                            <div class="mb-4">
                                <label for="verificationCode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Código de Verificación
                                </label>
                                <input
                                    type="text"
                                    id="verificationCode"
                                    wire:model="verificationCode"
                                    maxlength="6"
                                    placeholder="000000"
                                    class="w-full px-4 py-2 text-center text-xl tracking-widest border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 dark:focus:border-indigo-400 dark:focus:ring-indigo-400 placeholder-gray-500 dark:placeholder-gray-400"
                                />
                                @error('verificationCode')
                                    <span class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <button
                                wire:click="verifyAndEnable"
                                class="w-full bg-green-600 dark:bg-green-500 text-white py-3 px-4 rounded-lg font-semibold hover:bg-green-700 dark:hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors"
                            >
                                Verificar y Activar
                            </button>
                        </div>
                    @endif

                @else
                    <!-- 2FA Ya Habilitado -->
                    <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-6 mb-6">
                        <div class="flex items-center">
                            <svg class="w-6 h-6 text-green-600 dark:text-green-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <div>
                                <h3 class="font-semibold text-green-900 dark:text-green-100">Autenticación de Dos Factores Activa</h3>
                                <p class="text-sm text-green-700 dark:text-green-200 mt-1">
                                    Método actual: <span class="font-medium">{{ ucfirst($twoFactorType) }}</span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <button
                        wire:click="disableTwoFactor"
                        class="w-full bg-red-600 dark:bg-red-700 text-white py-3 px-4 rounded-lg font-semibold hover:bg-red-700 dark:hover:bg-red-600 focus:outline-none focus:ring-2 focus:ring-red-500 dark:focus:ring-red-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition-colors"
                    >
                        Deshabilitar Autenticación de Dos Factores
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>