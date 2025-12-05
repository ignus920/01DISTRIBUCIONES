<div class="relative" x-data="{ open: @entangle('isOpen') }" @click.away="$wire.closeDropdown()">
    @if($showLabel)
        <label for="position_multi_{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
            {{ $label }}
            @if($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif

    <!-- Selected Items Display -->
    @if(count($selectedPositions) > 0)
        <div class="mb-2 flex flex-wrap gap-2">
            @foreach($selectedPositionNames as $position)
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                    {{ $position->name }}
                    <button type="button" 
                        wire:click="removePosition({{ $position->id }})"
                        class="ml-1.5 inline-flex items-center justify-center w-4 h-4 rounded-full text-indigo-400 hover:bg-indigo-200 hover:text-indigo-500 focus:outline-none focus:bg-indigo-500 focus:text-white dark:hover:bg-indigo-800">
                        <svg class="w-2 h-2" stroke="currentColor" fill="none" viewBox="0 0 8 8">
                            <path stroke-linecap="round" stroke-width="1.5" d="m1 1 6 6m0-6-6 6" />
                        </svg>
                    </button>
                </span>
            @endforeach
            @if(count($selectedPositions) > 1)
                <button type="button" 
                    wire:click="clearAll"
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 hover:bg-red-200 dark:bg-red-900 dark:text-red-200 dark:hover:bg-red-800">
                    Limpiar todo
                </button>
            @endif
        </div>
    @endif

    <!-- Dropdown Button -->
    <button type="button"
        wire:click="toggleDropdown"
        id="position_multi_{{ $name }}"
        class="{{ $class }} relative cursor-pointer bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg pl-3 pr-10 py-2 text-left focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
        aria-haspopup="listbox"
        aria-expanded="false">
        <span class="block truncate text-gray-900 dark:text-white">
            @if(count($selectedPositions) > 0)
                {{ count($selectedPositions) }} posición(es) seleccionada(s)
            @else
                {{ $placeholder }}
            @endif
        </span>
        <span class="absolute inset-y-0 right-0 flex items-center pr-2 pointer-events-none">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </span>
    </button>

    <!-- Dropdown Panel -->
    <div x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute z-50 mt-1 w-full bg-white dark:bg-gray-700 shadow-lg {{ $maxHeight }} rounded-lg border border-gray-300 dark:border-gray-600 overflow-hidden"
        style="display: none;">
        
        <!-- Search Input -->
        @if($searchable)
            <div class="p-3 border-b border-gray-200 dark:border-gray-600">
                <input type="text"
                    wire:model.live.debounce.300ms="search"
                    placeholder="Buscar posiciones..."
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
            </div>
        @endif

        <!-- Options List -->
        <div class="overflow-y-auto {{ $maxHeight }}">
            @if($positions->count() > 0)
                <ul class="py-1">
                    @foreach($positions as $position)
                        <li>
                            <button type="button"
                                wire:click="togglePosition({{ $position->id }})"
                                class="w-full text-left px-3 py-2 text-sm text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-600 flex items-center justify-between">
                                <span>{{ $position->name }}</span>
                                @if(in_array($position->id, $selectedPositions))
                                    <svg class="w-4 h-4 text-indigo-600 dark:text-indigo-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                            </button>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="px-3 py-4 text-sm text-gray-500 dark:text-gray-400 text-center">
                    @if($searchable && !empty($search))
                        No se encontraron posiciones que coincidan con "{{ $search }}"
                    @else
                        No hay posiciones disponibles
                    @endif
                </div>
            @endif
        </div>

        <!-- Footer with selection count -->
        @if(count($selectedPositions) > 0)
            <div class="px-3 py-2 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-600">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ count($selectedPositions) }} seleccionada(s)
                    </span>
                    <button type="button" 
                        wire:click="clearAll"
                        class="text-xs text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-200">
                        Limpiar todo
                    </button>
                </div>
            </div>
        @endif
    </div>

    @error($name)
        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
    @enderror
</div>