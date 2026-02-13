<div 
    x-data="{ 
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
    }" 
    x-on:keydown.escape.prevent.stop="close($refs.button)" 
    x-on:focusin.window="! $refs.panel.contains($event.target) && close()" 
    x-id="['dropdown-button']" 
    class="relative"
>
    @if($showLabel)
        <label for="route_{{ $name }}" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
            {{ $label }}
            @if($required) <span class="text-red-500">*</span> @endif
        </label>
    @endif

    <!-- Input oculto para formularios tradicionales -->
    <input type="hidden" name="{{ $name }}" wire:model="routeId">

    <!-- Botón que simula ser el Select -->
    <button 
        x-ref="button" 
        x-on:click="toggle()" 
        :aria-expanded="open" 
        :aria-controls="$id('dropdown-button')" 
        type="button" 
        class="{{ $class }} flex items-center justify-between bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 border-gray-300 dark:border-gray-600"
        id="route_{{ $name }}"
    >
        <span class="block truncate">
            {{ $this->selectedRouteName ?? $placeholder }}
        </span>

        <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
        </svg>
    </button>

    <!-- Panel desplegable (El buscador y la lista) -->
    <div 
        x-ref="panel" 
        x-show="open" 
        x-transition.origin.top.left 
        x-on:click.outside="close($refs.button)" 
        :id="$id('dropdown-button')" 
        style="display: none;" 
        class="absolute left-0 z-50 mt-2 w-full rounded-md bg-white dark:bg-gray-800 shadow-lg ring-1 ring-black ring-opacity-5 dark:ring-gray-700 focus:outline-none"
    >
        <!-- Buscador -->
        <div class="p-2 border-b border-gray-200 dark:border-gray-700">
            <input 
                wire:model.live.debounce.300ms="search" 
                type="text" 
                class="block w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500 dark:focus:border-indigo-400 dark:focus:ring-indigo-400" 
                placeholder="Buscar ruta..."
                x-trap="open" 
            >
        </div>

        <!-- Lista de opciones -->
        <ul class="max-h-60 overflow-auto py-1 text-base ring-1 ring-black ring-opacity-5 dark:ring-gray-700 focus:outline-none sm:text-sm">
            <!-- Opción vacía / Reset -->
            <li 
                class="text-gray-900 dark:text-gray-100 relative cursor-default select-none py-2 pl-3 pr-9 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-500"
                role="option" 
                x-on:click="$wire.selectRoute(''); close($refs.button)"
            >
                <span class="font-normal block truncate">{{ $placeholder }}</span>
            </li>

            @forelse($this->routes as $route)
                <li 
                    wire:key="route-{{ $route->id }}"
                    class="text-gray-900 dark:text-gray-100 relative cursor-default select-none py-2 pl-3 pr-9 hover:bg-indigo-600 hover:text-white dark:hover:bg-indigo-500 group"
                    role="option" 
                    x-on:click="$wire.selectRoute({{ $route->id }}); close($refs.button)"
                >
                    <div class="font-normal block truncate {{ $routeId == $route->id ? 'font-semibold' : '' }}">
                        <div>
                            <span class="font-semibold">{{ ucfirst($route->sale_day) }}</span> - 
                            {{ $route->salesman?->name ?? 'Sin vendedor' }}
                        </div>
                        <div class="text-xs text-gray-500 dark:text-gray-400 group-hover:text-gray-200">
                            {{ $route->name }}
                        </div>
                    </div>

                    <!-- Checkmark si está seleccionado -->
                    @if($routeId == $route->id)
                        <span class="text-indigo-600 dark:text-indigo-400 absolute inset-y-0 right-0 flex items-center pr-4 group-hover:text-white">
                            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                    @endif
                </li>
            @empty
                <li class="text-gray-500 dark:text-gray-400 relative cursor-default select-none py-2 pl-3 pr-9">
                    No se encontraron rutas.
                </li>
            @endforelse
        </ul>
        
        <!-- Loading indicator dentro del dropdown -->
        <div wire:loading.flex wire:target="search" class="absolute bottom-0 right-0 p-2">
            <svg class="animate-spin h-4 w-4 text-indigo-500 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
        </div>
    </div>

    @error($name)
        <span class="text-red-500 dark:text-red-400 text-sm mt-1">{{ $message }}</span>
    @enderror
</div>
