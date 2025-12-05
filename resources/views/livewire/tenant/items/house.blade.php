<div class="mb-3">
    <div class="flex items-end space-x-2">
        <div class="flex-1">
            @if ($showLabel)
                <label for="house_{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ $label }}
                </label>        
            @endif
        
            <select
                wire:model.live="houseId"
                name="{{ $name }}"
                id="house_{{ $name }}"
                @if($required) required @endif
                class="{{ $class }}"
                wire:loading.attr="disabled">
                <option value="">{{ $placeholder }}</option>
                @foreach($this->houses as $house)
                    <option value="{{ $house->id }}">
                        {{ $house->name }}
                    </option>
                @endforeach
            </select>
        
            @error($name)
                <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <!-- Botón para agregar nueva casa -->
        <button 
            type="button"
            wire:click="toggleHouseForm"
            class="h-[42px] aspect-square bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 border border-transparent rounded-lg text-white flex items-center justify-center shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            title="Agregar nueva categoría">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
        </button>
    </div>
    <!-- Formulario para crear nueva casa -->
    @if($showHouseForm)
    <div class="mt-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="flex space-x-2">
            <div class="flex-1">
                <input
                    type="text"
                    wire:model="newHouseName"
                    wire:keydown.enter="createHouse"
                    placeholder="Ingrese nombre de la casa"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                    autofocus>
                @error('newHouseName')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex space-x-2">
                <button
                    type="button"
                    wire:click="createHouse"
                    wire:loading.attr="disabled"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                    <span wire:loading.remove>Agregar</span>
                    <span wire:loading>Guardando...</span>
                </button>
                <button
                    type="button"
                    wire:click="toggleHouseForm"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
    @endif
</div>
