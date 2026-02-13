<div class="space-y-6">
    <!-- Tipo de Contacto (Solo si se permite o no es reusable) -->
    @if(isset($showTypeSelection) && $showTypeSelection)
    <div class="md:col-span-2">
        <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipo de Contacto *</label>
        <select wire:model.live="type" x-model="offlineCustomer.type" id="type" name="type"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            <option value="">Seleccione un tipo de contacto</option>
            <option value="CLIENTE">CLIENTE</option>
            <option value="PROVEEDOR">PROVEEDOR</option>
        </select>
        @error('type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>
    @endif

    <!-- Tipo de Identificación -->
    <div>
        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            Tipo de Identificación <span class="text-red-500">*</span>
        </label>
        <div x-show="isOnline">
            @livewire('selects.type-identification-select', [
                'typeIdentificationId' => $typeIdentificationId,
                'name' => 'typeIdentificationId',
                'placeholder' => 'Seleccione un tipo de identificación',
                'label' => '',
                'showLabel' => false,
                'class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500'
            ], key('type-id-select-' . ($editingId ?? 'new')))
        </div>
        <div x-show="!isOnline" x-cloak>
            <select x-model="offlineCustomer.typeIdentificationId" 
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">Seleccione tipo...</option>
                <template x-for="type in typeIdentifications" :key="type.id">
                    <option :value="type.id" x-text="type.name"></option>
                </template>
            </select>
        </div>
        @error('typeIdentificationId')
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>

    <!-- NIT/Identificación con campo DV condicional -->
    <div x-show="offlineCustomer.typeIdentificationId == 2 || typeIdentificationId == 2">
        <!-- NIT con DV -->
        <div class="grid grid-cols-3 gap-4">
            <div class="col-span-2">
                <label for="identification" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">NIT *</label>
                <div class="relative">
                    <input wire:model.live.debounce.500ms="identification" x-model="offlineCustomer.identification" 
                        type="text" id="identification" maxlength="15"
                        class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                           @error('identification') border-red-500 @enderror
                           @if(isset($identificationExists) && $identificationExists) border-red-500 @endif"
                        placeholder="123456789" required>

                    @if(isset($validatingIdentification) && $validatingIdentification)
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                        <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    @endif
                </div>

                @error('identification')
                <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror

                @if(isset($identificationExists) && $identificationExists && !$errors->has('identification'))
                <span class="text-red-500 text-sm">
                    Este número de identificación ya está registrado
                </span>
                @endif
            </div>
            <div>
                <label for="verification_digit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">DV *</label>
                <input wire:model="verification_digit" x-model="offlineCustomer.verification_digit" 
                    type="text" id="verification_digit" maxlength="1"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="0"
                    oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                @error('verification_digit') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
            <!-- Tipo de Persona -->
            <div>
                <label for="typePerson" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipo de Persona *</label>
                <select wire:model.live="typePerson" x-model="offlineCustomer.typePerson" id="typePerson"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Seleccionar tipo</option>
                    <option value="Natural">Persona Natural</option>
                    <option value="Juridica">Persona Jurídica</option>
                </select>
                @error('typePerson') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    <div x-show="offlineCustomer.typeIdentificationId != 2 && typeIdentificationId != 2">
        <!-- Otros tipos de identificación -->
        <div>
            <label for="identification_alt" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Número de Identificación *</label>
            <div class="relative">
                <input wire:model.live.debounce.500ms="identification" x-model="offlineCustomer.identification" 
                    type="text" id="identification_alt" maxlength="15"
                    class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                       @error('identification') border-red-500 @enderror
                       @if(isset($identificationExists) && $identificationExists) border-red-500 @endif"
                    placeholder="Ingrese el número">

                @if(isset($validatingIdentification) && $validatingIdentification)
                <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                    <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </div>
                @endif
            </div>

            @error('identification')
            <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror

            @if(isset($identificationExists) && $identificationExists && !$errors->has('identification'))
            <span class="text-red-500 text-sm">
                Este número de identificación ya está registrado
            </span>
            @endif
        </div>
    </div>

    <!-- Campos condicionales según tipo de persona -->
    <div x-show="offlineCustomer.typePerson == 'Natural' || typePerson == 'Natural' || (isset($showNaturalPersonFields) && $showNaturalPersonFields)">
        <!-- Persona Natural: Nombre y Apellido -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="firstName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Primer Nombre *</label>
                <input wire:model="firstName" x-model="offlineCustomer.firstName" type="text" id="firstName"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Ingrese su nombre">
                @error('firstName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="lastName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Segundo Nombre </label>
                <input wire:model="lastName" x-model="offlineCustomer.lastName" type="text" id="lastName"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Ingrese su apellido">
                @error('lastName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <div>
                <label for="secondName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Primer Apellido *</label>
                <input wire:model="secondName" x-model="offlineCustomer.secondName" type="text" id="secondName"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Ingrese su primer apellido">
                @error('secondName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="secondLastName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Segundo Apellido </label>
                <input wire:model="secondLastName" x-model="offlineCustomer.secondLastName" type="text" id="secondLastName"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Ingrese su segundo apellido">
                @error('secondLastName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    <div x-show="offlineCustomer.typePerson == 'Juridica' || typePerson == 'Juridica'">
        <!-- Persona Jurídica: Razón Social -->
        <div>
            <label for="businessName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Razón Social *</label>
            <input wire:model="businessName" x-model="offlineCustomer.businessName" type="text" id="businessName"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="Ingrese la razón social de la empresa">
            @error('businessName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
            <!-- Régimen -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Régimen <span class="text-red-500">*</span>
                </label>
                <div x-show="isOnline">
                    @livewire('selects.regime-select', [
                        'regimeId' => $regimeId,
                        'name' => 'regimeId',
                        'label' => '',
                        'showLabel' => false,
                        'placeholder' => 'Seleccionar régimen',
                        'class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500'
                    ], key('regime-select-' . ($editingId ?? 'new')))
                </div>
                <div x-show="!isOnline" x-cloak>
                    <select x-model="offlineCustomer.regimeId" 
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Seleccionar régimen...</option>
                        <template x-for="regime in regimes" :key="regime.id">
                            <option :value="regime.id" x-text="regime.name"></option>
                        </template>
                    </select>
                </div>
                @error('regimeId')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <!-- Responsabilidad Fiscal -->
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Responsabilidad Fiscal <span class="text-red-500">*</span>
                </label>
                <div x-show="isOnline">
                    @livewire('selects.fiscal-responsibility-select', [
                        'fiscalResponsibilityId' => $fiscalResponsabilityId,
                        'name' => 'fiscalResponsibilityId',
                        'label' => '',
                        'showLabel' => false,
                        'placeholder' => 'Seleccionar responsabilidad fiscal',
                        'class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500'
                    ], key('fiscal-select-' . ($editingId ?? 'new')))
                </div>
                <div x-show="!isOnline" x-cloak>
                    <select x-model="offlineCustomer.fiscalResponsabilityId" 
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Seleccionar responsabilidad...</option>
                        <template x-for="resp in fiscalResponsibilities" :key="resp.id">
                            <option :value="resp.id" x-text="resp.name"></option>
                        </template>
                    </select>
                </div>
                @error('fiscalResponsabilityId')
                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>
    </div>

    <!-- Email de Facturación -->
    <div>
        <label for="billingEmail" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email de Facturación</label>
        <input wire:model.live.debounce.500ms="billingEmail" x-model="offlineCustomer.billingEmail" type="email" id="billingEmail"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
             @error('billingEmail') border-red-500 @enderror
             @if(isset($emailExists) && $emailExists) border-red-500 @endif"
            placeholder="Ingrese el email de facturación" required>
        @error('billingEmail') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

        @if(isset($emailExists) && $emailExists && !$errors->has('billingEmail'))
        <span class="text-red-500 text-sm">
            Este email ya está registrado
        </span>
        @endif
    </div>

    @if (!isset($hideRoute) || !$hideRoute)
    <div x-show="offlineCustomer.type == 'CLIENTE' || type == 'CLIENTE' || (isset($forceShowRoute) && $forceShowRoute)">
        <!-- Vendedor / Ruta -->
        <div x-show="isOnline">
            @livewire('selects.route-sales-day', [
                'name' => 'routeId',
                'label' => 'Ruta',
                'required' => false,
                'placeholder' => 'Seleccione una ruta (opcional)',
                'routeId' => $routeId ?? ''
            ], key('route-select-' . ($editingId ?? 'new')))
        </div>
        <div x-show="!isOnline" x-cloak>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ruta</label>
            <select x-model="offlineCustomer.routeId" 
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                <option value="">Seleccione ruta...</option>
                <template x-for="route in routes" :key="route.id">
                    <option :value="route.id" x-text="route.name + ' (' + route.sale_day + ')'"></option>
                </template>
            </select>
        </div>
        
        <!-- Barrio -->
        <div class="mt-4">
            <label for="district" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Barrio</label>
            <input wire:model="district" x-model="offlineCustomer.district" type="text" id="district"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="ej: Galan" required>
            @error('district') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
    </div>
    @endif

    <!-- Teléfonos -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
            <label for="business_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Teléfono Empresarial</label>
            <input wire:model="business_phone" x-model="offlineCustomer.business_phone" type="text" id="business_phone"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="ej: +57 300 123 4567" required>
            @error('business_phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
        <div>
            <label for="personal_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Teléfono Personal</label>
            <input wire:model="personal_phone" x-model="offlineCustomer.personal_phone" type="text" id="personal_phone"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="ej: +57 310 987 6543">
            @error('personal_phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
    </div>

    <!-- Código CIIU -->
    <div>
        <label for="code_ciiu" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Código CIIU</label>
        <input wire:model="code_ciiu" x-model="offlineCustomer.code_ciiu" type="text" id="code_ciiu"
            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            placeholder="ej: 4711">
        @error('code_ciiu') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
    </div>

    <!-- Sucursal Info -->
    <div class="space-y-4 pt-4 border-t border-gray-200 dark:border-gray-700">
        <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Información de Sucursal Principal</h4>
        
        @if(isset($editingId) && $editingId)
        <div>
            <label for="warehouseName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nombre Sucursal *</label>
            <input wire:model="warehouseName" x-model="offlineCustomer.warehouseName" type="text" id="warehouseName"
                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                placeholder="Ej: Sucursal Principal">
            @error('warehouseName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>
        @endif

        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Ciudad *</label>
            <div x-show="isOnline">
                @livewire('selects.city-select', [
                    'cityId' => $warehouseCityId ?? '',
                    'countryId' => 48,
                    'name' => 'warehouseCityId',
                    'placeholder' => 'Seleccionar ciudad',
                    'label' => '',
                    'required' => true,
                    'showLabel' => false,
                    'class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500',
                    'index' => 0
                ], key('city-select-' . ($editingId ?? 'new')))
            </div>
            <div x-show="!isOnline" x-cloak>
                <select x-model="offlineCustomer.warehouseCityId" 
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="">Seleccionar ciudad...</option>
                    <template x-for="city in cities" :key="city.id">
                        <option :value="city.id" x-text="city.name"></option>
                    </template>
                </select>
            </div>
            @error('warehouseCityId')
            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="warehouseAddress" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Dirección *</label>
                <input wire:model="warehouseAddress" x-model="offlineCustomer.warehouseAddress" type="text" id="warehouseAddress"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Ej: Calle 123 #45-67">
                @error('warehouseAddress') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
            <div>
                <label for="warehousePostcode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Código Postal</label>
                <input wire:model="warehousePostcode" x-model="offlineCustomer.warehousePostcode" type="text" id="warehousePostcode"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="Ej: 110111">
                @error('warehousePostcode') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
            </div>
        </div>
    </div>

    <!-- Gestión de Usuario (Solo Online) -->
    <div x-show="isOnline">
        <div class="flex items-center gap-3 p-4 rounded-lg
            {{ empty($billingEmail) || (isset($emailExists) && $emailExists) || (isset($hasExistingUser) && $hasExistingUser) || (isset($validatingType) && $validatingType) ? 'bg-gray-50 dark:bg-gray-900/20 border border-gray-200 dark:border-gray-700' : 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800' }}">
            <input
                wire:model="createUser"
                type="checkbox"
                id="createUser"
                {{ empty($billingEmail) || (isset($emailExists) && $emailExists) || (isset($hasExistingUser) && $hasExistingUser) || (isset($validatingType) && $validatingType) ? 'disabled' : '' }}
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded {{ empty($billingEmail) || (isset($emailExists) && $emailExists) || (isset($hasExistingUser) && $hasExistingUser) || (isset($validatingType) && $validatingType) ? 'opacity-50 cursor-not-allowed' : '' }}">
            <label for="createUser" class="text-sm font-medium flex-1 {{ empty($billingEmail) || (isset($emailExists) && $emailExists) || (isset($hasExistingUser) && $hasExistingUser) || (isset($validatingType) && $validatingType) ? 'text-gray-400 dark:text-gray-600' : 'text-gray-700 dark:text-gray-300' }}">
                <span class="font-semibold">{{ ($editingId ?? false) ? 'Crear Usuario para este Cliente' : 'Convertir en Usuario' }}</span>
                <p class="text-xs mt-1 {{ empty($billingEmail) || (isset($emailExists) && $emailExists) || (isset($hasExistingUser) && $hasExistingUser) || (isset($validatingType) && $validatingType) ? 'text-gray-400 dark:text-gray-600' : 'text-gray-600 dark:text-gray-400' }}">
                    @if(empty($billingEmail))
                    Ingrese un email de facturación válido para habilitar esta opción
                    @elseif(isset($emailExists) && $emailExists)
                    No disponible: el email ya está registrado
                    @elseif(isset($validatingType) && $validatingType)
                    No disponible para proveedores
                    @else
                    Crear automáticamente un usuario para acceder al sistema con perfil de Tienda
                    @endif
                </p>
            </label>
            <div class="text-xs px-2 py-1 rounded {{ empty($billingEmail) || (isset($emailExists) && $emailExists) || (isset($hasExistingUser) && $hasExistingUser) || (isset($validatingType) && $validatingType) ? 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400' : 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-300' }}">
                Perfil: Tienda
            </div>
        </div>
    </div>
</div>
