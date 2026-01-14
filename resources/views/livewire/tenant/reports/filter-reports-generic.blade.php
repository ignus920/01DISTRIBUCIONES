<div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Filtros de Búsqueda</h2>
        
        @if($hasActiveFilters)
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 dark:bg-indigo-900/30 text-indigo-800 dark:text-indigo-300">
                <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"></path>
                </svg>
                Filtros activos
            </span>
        @endif
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($fields as $field)
            <div class="{{ $field['fullWidth'] ?? false ? 'md:col-span-2' : '' }}">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {{ $field['label'] }}
                    @if($field['required'] ?? false)
                        <span class="text-red-500">*</span>
                    @endif
                </label>
                
                @if($field['type'] === 'date')
                    <input 
                        wire:model="filters.{{ $field['name'] }}" 
                        type="date"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 [color-scheme:light] dark:[color-scheme:dark]"
                        {{ ($field['required'] ?? false) ? 'required' : '' }}>
                    
                @elseif($field['type'] === 'select')
                    <select 
                        wire:model="filters.{{ $field['name'] }}"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        {{ ($field['required'] ?? false) ? 'required' : '' }}>
                        <option value="">{{ $field['placeholder'] ?? 'Seleccionar...' }}</option>
                        @foreach($field['options'] ?? [] as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    
                @elseif($field['type'] === 'text')
                    <input 
                        wire:model="filters.{{ $field['name'] }}" 
                        type="text"
                        placeholder="{{ $field['placeholder'] ?? '' }}"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        {{ ($field['required'] ?? false) ? 'required' : '' }}>
                    
                @elseif($field['type'] === 'number')
                    <input 
                        wire:model="filters.{{ $field['name'] }}" 
                        type="number"
                        placeholder="{{ $field['placeholder'] ?? '' }}"
                        min="{{ $field['min'] ?? '' }}"
                        max="{{ $field['max'] ?? '' }}"
                        step="{{ $field['step'] ?? 'any' }}"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        {{ ($field['required'] ?? false) ? 'required' : '' }}>
                    
                @elseif($field['type'] === 'livewire')
                    @livewire($field['component'], $field['params'] ?? [], key($field['name']))
                @endif
                
                @error('filters.' . $field['name']) 
                    <span class="text-red-600 dark:text-red-400 text-sm mt-1 block">{{ $message }}</span> 
                @enderror
            </div>
        @endforeach
    </div>

    <!-- Botones de Filtro -->
    <div class="flex flex-wrap gap-3 mt-4">
        <button 
            wire:click="applyFilters"
            wire:loading.attr="disabled"
            wire:loading.class="opacity-50 cursor-not-allowed"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
            <svg wire:loading.remove class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <svg wire:loading class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span wire:loading.remove>Buscar</span>
            <span wire:loading>Buscando...</span>
        </button>
        
        <button 
            wire:click="clearFilters"
            class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 font-semibold text-xs uppercase tracking-widest transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
            Limpiar
        </button>
    </div>
</div>
