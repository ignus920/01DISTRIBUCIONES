<div x-data="{ 
        open: false, 
        toggle() { 
            if (this.open) { return this.close() } 
            this.$refs.button.focus() 
            this.open = true 
        }, 
        close(focusAfter) { 
            if (! this.open) return 
            this.open = false 
            focusAfter && focusAfter.focus() 
        } 
    }" x-on:keydown.escape.prevent.stop="close($refs.button)"
    x-on:focusin.window="! $refs.panel.contains($event.target) && close()" x-id="['dropdown-button']" class="relative">
    <div class="flex items-end space-x-2">
        <div class="flex-1">
            @if($showLabel)
            <label for="brand_{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {{ $label }}
                @if($required) <span class="text-red-500">*</span> @endif
            </label>
            @endif

            <input type="hidden" name="{{ $name }}" wire:model="brandId">

            <button x-ref="button" x-on:click="toggle()" :aria-expanded="open" :aria-controls="$id('dropdown-button')"
                type="button"
                class="{{ $class }} flex items-center justify-between bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600"
                id="brand_{{ $name }}">
                <span class="block truncate">
                    {{ $this->selectedBrandName ?? $placeholder }}
                </span>
            </button>

            <div x-ref="panel" x-show="open" x-transition.origin.top.left x-on:click.outside="close($refs.button)"
                :id="$id('dropdown-button')" style="display: none;"
                class="absolute left-0 z-50 mt-2 w-full rounded-md bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 dark:ring-gray-700 focus:outline-none">

                <div class="p-2 border-b border-gray-200 dark:border-gray-700">
                    <input wire:model.live.debounce.300ms="search" type="text"
                        class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400"
                        placeholder="Buscar..." x-trap="open">
                </div>

                <ul
                    class="max-h-60 overflow-auto py-1 text-base ring-1 ring-black ring-opacity-5 dark:ring-gray-700 focus:outline-none sm:text-sm">
                    <li class="text-gray-900 dark:text-gray-100 relative cursor-default select-none py-2 pl-3 pr-9 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-500"
                        role="option" x-on:click="$wire.selectCity(''); close($refs.button)">
                        <span class="font-normal block truncate">{{ $placeholder }}</span>
                    </li>
                    @forelse($this->brands as $brand)
                    <li wire:key="brand-{{ $brand->id }}"
                        class="text-gray-900 dark:text-gray-100 relative cursor-default select-none py-2 pl-3 pr-9 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-500 group"
                        role="option" x-on:click="$wire.selectBrand({{ $brand->id }}); close($refs.button)">

                        <span class="font-normal block truncate {{ $brandId == $brand->id ? 'font-semibold' : '' }}">
                            {{ $brand->name }}
                        </span>

                        <!-- Checkmark si está seleccionado -->
                        @if($brandId == $brand->id)
                        <span
                            class="text-indigo-600 dark:text-indigo-400 absolute inset-y-0 right-0 flex items-center pr-4 group-hover:text-white">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                    clip-rule="evenodd" />
                            </svg>
                        </span>
                        @endif
                    </li>
                    @empty
                    <li class="text-gray-500 dark:text-gray-400 relative cursor-default select-none py-2 pl-3 pr-9">
                        No se encontraron resultados.
                    </li>
                    @endforelse
                </ul>
                <div wire:loading.flex wire:target="search" class="absolute bottom-0 right-0 p-2">
                    <svg class="animate-spin h-4 w-4 text-indigo-500 dark:text-indigo-400"
                        xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                        </circle>
                        <path class="opacity-75" fill="currentColor"
                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                        </path>
                    </svg>
                </div>
            </div>

            @error($name)
            <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror
        </div>

        <!-- Botón para agregar nueva marca -->
        <button type="button" wire:click="toggleBrandForm"
            class="h-[42px] aspect-square bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 border border-transparent rounded-lg text-white flex items-center justify-center shadow-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
            title="Agregar nueva marca">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
        </button>
    </div>
    <!-- Formulario para crear nueva marca -->
    @if($showBrandForm)
    <div class="mt-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-700">
        <div class="flex space-x-2">
            <div class="flex-1">
                <input type="text" wire:model="newBrandName" wire:keydown.enter="createBrand"
                    placeholder="Ingrese nombre de la marca"
                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                    autofocus>
                @error('newBrandName')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex space-x-2">
                <button type="button" wire:click="createBrand" wire:loading.attr="disabled"
                    class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                    <span wire:loading.remove>Agregar</span>
                    <span wire:loading>Guardando...</span>
                </button>
                <button type="button" wire:click="toggleBrandForm"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
    @endif
</div>