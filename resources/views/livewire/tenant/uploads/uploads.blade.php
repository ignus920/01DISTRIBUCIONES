<div class="min-h-screen bg-gray-50 dark:bg-gray-900 p-4 lg:p-6">
    <!-- Cambiado p-6 a p-4 lg:p-6 -->
    <div class="w-full mx-auto px-2 sm:px-4">
        <!-- Cambiado max-w-12xl por w-full y añadido padding horizontal -->
        <!-- Header -->
        <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-4 md:p-6 mb-4 md:mb-6">
            <!-- Ajustado padding -->
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-xl md:text-2xl font-bold text-gray-900 dark:text-white">Parámetros cargue de pedidos
                    </h1>
                    <!-- Ajustado tamaño texto -->
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Gestion de cargue de pedidos</p>
                </div>
            </div>
        </div>

        <div class="w-full">
            <!--CARD IZQUIERDO-->
            <div class="w-full mx-auto">
                <!-- Ajustado para ocupar todo el ancho disponible -->
                <!-- Mensajes -->
                @if (session()->has('message'))
                <div
                    class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        {{ session('message') }}
                    </div>
                </div>
                @endif
                <!-- DataTable Card -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 h-full">
                    <!-- Toolbar -->
                    <div class="p-4 md:p-6 border-b border-gray-200 dark:border-gray-700">
                        <!-- Ajustado padding -->
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 w-full">
                            <!-- Selector de fechas -->
                            <div class="flex-1">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <x-heroicon-o-calendar-days class="w-6 h-6" />
                                    </div>
                                    <input type="date" wire:model.live="selectedDate"
                                        class="block w-full ps-9 pe-3 py-2.5 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 text-gray-900 dark:text-white text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 px-3 py-2.5 shadow-xs placeholder:text-gray-500 dark:placeholder:text-gray-400"
                                        placeholder="Selecciona una fecha">

                                    {{-- Botón para limpiar fecha --}}
                                    @if($selectedDate)
                                    <button type="button" wire:click="clearDate"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                    @endif
                                </div>
                            </div>
                            <!-- Rutas -->
                            <div class="flex-1 flex items-center gap-3">
                                <label
                                    class="text-sm text-gray-700 dark:text-gray-300 whitespace-nowrap">Transportador:</label>
                                <select wire:model.live="selectedRoute"
                                    class="w-full border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="">-- Seleccione --</option>
                                    @foreach ($users as $rt)
                                    <option value="{{ $rt->id }}">{{ $rt->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        {{-- <div class="border-b border-default justify-center flex">
                            <ul class="flex flex-wrap -mb-px text-sm font-medium text-center text-body">
                                <li class="me-2">
                                    <a href="#"
                                        class="inline-flex items-center justify-center p-4 border-b border-transparent rounded-t-base hover:text-fg-brand hover:border-brand group">
                                        Rutas
                                    </a>
                                </li>
                                <li class="me-2">
                                    <a href="#"
                                        class="inline-flex items-center justify-center p-4 text-fg-brand border-b border-brand rounded-t-base active group"
                                        aria-current="page">
                                        Cargue
                                    </a>
                                </li>
                            </ul>
                        </div> --}}
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 w-full pt-3">
                            <button wire:click="preupload"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4"></path>
                                </svg>
                                Previa del Cargue
                            </button>
                        </div>
                    </div>

                    <!-- Tabla -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Vendedor</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Cantidad</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Fecha</th>
                                    <th
                                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Cargar/Eliminar
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($remissions as $remission)
                                <tr wire:key="remission-{{ $loop->index }}"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $remission->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $remission->total_registros }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $remission->fecha }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <div class="flex items-center justify-center gap-2">
                                            <button wire:click="cargar({{ $remission->userId }})"
                                                wire:loading.attr="disabled"
                                                wire:target="cargar({{ $remission->userId }})"
                                                class="inline-flex items-center px-3 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 text-xs font-medium rounded-full hover:bg-green-200 dark:hover:bg-green-900/50 transition-colors disabled:opacity-50">
                                                <x-heroicon-o-arrow-up-tray class="w-5 h-4" />
                                                <span wire:loading.remove
                                                    wire:target="cargar({{ $remission->userId }})">Cargar</span>
                                                <span wire:loading wire:target="cargar({{ $remission->userId }})"
                                                    class="flex items-center">
                                                    <svg class="animate-spin h-3 w-3 mr-1"
                                                        xmlns="http://www.w3.org/2000/svg" fill="none"
                                                        viewBox="0 0 24 24">
                                                        <circle class="opacity-25" cx="12" cy="12" r="10"
                                                            stroke="currentColor" stroke-width="4"></circle>
                                                        <path class="opacity-75" fill="currentColor"
                                                            d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                                                        </path>
                                                    </svg>
                                                    Cargando...
                                                </span>
                                            </button>
                                            <button
                                                class="inline-flex items-center px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 text-xs font-medium rounded-full hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors">
                                                <x-heroicon-o-trash class="w-5 h-4" />
                                                Eliminar
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 mb-4 text-gray-400 dark:text-gray-600" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                                                </path>
                                            </svg>
                                            <p class="text-lg font-medium">No hay registros</p>
                                            <p class="text-sm">Selecciona una fecha para ver los datos</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    {{-- <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">

                                    </th>
                                </tr>
                            </thead>
                        </table>
                    </div> --}}
                </div>
            </div>
        </div>
    </div>
    <!-- Modal -->
    @if($showModal)

    @endif
</div>

<script>
    // Solo JavaScript simple para mejorar la UX
    document.addEventListener('DOMContentLoaded', function() {
        // Formatear fecha para input type="date"
        const dateInput = document.querySelector('input[type="date"]');
        if (dateInput && !dateInput.value) {
            // Opcional: establecer fecha actual por defecto
            // const today = new Date().toISOString().split('T')[0];
            // dateInput.value = today;
        }
        
        // Cerrar modal con ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                @this.set('showModal', false);
            }
        });
        
        // Cerrar modal haciendo clic fuera
        const modalBackdrop = document.querySelector('[wire\\:key="modal-backdrop"]');
        if (modalBackdrop) {
            modalBackdrop.addEventListener('click', function(e) {
                if (e.target === this) {
                    @this.set('showModal', false);
                }
            });
        }
    });
    
    // Debug: Verificar que Livewire responde
    Livewire.hook('request', ({ uri, options, payload }) => {
        console.log('Livewire request:', { uri, payload });
    });
    
    Livewire.hook('response', ({ status, component }) => {
        console.log('Livewire response:', status, component);
    });
</script>