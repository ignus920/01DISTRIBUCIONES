<!-- Modal de Creación Rápida de Cliente -->
<div 
    x-show="showOfflineCreateForm" 
    class="fixed inset-0 z-[9999] overflow-y-auto h-full w-full bg-gray-600 dark:bg-gray-900 bg-opacity-50 dark:bg-opacity-75"
    role="dialog"
    aria-modal="true"
    x-cloak
    @keydown.escape.window="showOfflineCreateForm = false; $wire.cancelCreateCustomer()"
>
    <!-- Contenedor -->
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div 
            x-show="showOfflineCreateForm"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full max-h-[90vh] overflow-y-auto"
            @click.away="showOfflineCreateForm = false; $wire.cancelCreateCustomer()"
        >

            <!-- Header -->
            <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Nuevo Cliente
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Completa los datos para el registro rápido
                    </p>
                </div>
                <button @click="showOfflineCreateForm = false; $wire.cancelCreateCustomer()"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-4">

                <!-- Datos básicos -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Tipo Identif. <span class="text-red-500">*</span>
                        </label>
                        <select x-model="newOfflineCustomer.typeIdentificationId"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                            <option value="1">C.C.</option>
                            <option value="2">NIT</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Documento <span class="text-red-500">*</span>
                        </label>
                        <input x-model="newOfflineCustomer.identification" type="tel"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 font-bold"
                            placeholder="Sin puntos ni comas">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Nombre / Razón Social <span class="text-red-500">*</span>
                    </label>
                    <input x-model="newOfflineCustomer.businessName" type="text"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 uppercase font-bold text-lg"
                        placeholder="Nombre completo del cliente">
                </div>

                <!-- Contacto -->
                <div class="grid grid-cols-1 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Correo Electrónico
                        </label>
                        <input x-model="newOfflineCustomer.billingEmail" type="email"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                            placeholder="correo@cliente.com">
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Teléfono
                            </label>
                            <input x-model="newOfflineCustomer.phone" type="tel"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                placeholder="Teléfono">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Dirección
                            </label>
                            <input x-model="newOfflineCustomer.address" type="text"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500"
                                placeholder="Dirección">
                        </div>
                    </div>
                </div>

                <!-- Checkbox -->
                <div class="p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-700">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" x-model="newOfflineCustomer.createUser"
                            class="w-4 h-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <div>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">Crear usuario</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Permitirá el acceso al sistema</p>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Footer -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-800/50 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                <button @click="showOfflineCreateForm = false; $wire.cancelCreateCustomer()"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                    Cancelar
                </button>

                <button @click="saveOfflineCustomer()"
                    class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg text-sm font-medium transition-colors shadow-sm">
                    Guardar Cliente
                </button>
            </div>

        </div>
    </div>
</div>
