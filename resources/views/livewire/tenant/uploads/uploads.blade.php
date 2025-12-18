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

                <div>
                    <button wire:click="$set('showCharge', 'pedidos')"
                        class="inline-flex items-center px-4 py-2 {{ $showCharge === 'pedidos' ? 'bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600' : 'bg-gray-400 hover:bg-gray-500 dark:bg-gray-600 dark:hover:bg-gray-500' }} border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                        <!-- Icono de lista/clipboard para Pedidos -->
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                        Pedidos
                    </button>
                    <button wire:click="$set('showCharge', 'cargues')"
                        class="inline-flex items-center px-4 py-2 {{ $showCharge === 'cargues' ? 'bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600' : 'bg-gray-400 hover:bg-gray-500 dark:bg-gray-600 dark:hover:bg-gray-500' }} border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        Cargues
                    </button>

                </div>

            </div>
        </div>

        <div class="w-full">



            <div class="w-full mx-auto">
                <!-- Ajustado para ocupar todo el ancho disponible -->
                <!-- Mensajes -->

                @if($showCharge == "pedidos")
                <div wire:key="uploads-pedidos-container">
                <!--CARD IZQUIERDO-->
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

                @if (session()->has('error'))
                <div
                    class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg mb-6">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        {{ session('error') }}
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
                            <button wire:click="showConfirmUploadModal"
                                class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4"></path>
                                </svg>
                                Confirmar Cargue
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
                                        Ruta</th>
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
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $remission->ruta }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $remission->total_registros }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        {{ $remission->fecha }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <div class="flex items-center justify-center gap-2">
                                            @if($remission->existe == "NO")
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
                                            @else
                                            <button wire:click="eliminar({{ $remission->userId }})"
                                                class="inline-flex items-center px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 text-xs font-medium rounded-full hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors">
                                                <x-heroicon-o-trash class="w-5 h-4" />
                                                Eliminar
                                            </button>
                                            @endif
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
                @else
                <div wire:key="uploads-cargues-container">
                    <livewire:tenant.uploads.components.print-uploads-charges wire:key="print-uploads-charges-component" />
                </div>
                @endif

            </div>
        </div>
    </div>

    <!-- Modal de Confirmación de Cargue -->
    @if($showConfirmModal)
    <div class="fixed inset-0 bg-gray-600 dark:bg-gray-900 bg-opacity-50 dark:bg-opacity-75 overflow-y-auto h-full w-full z-50"
        x-data="{ show: true }" x-show="show" x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                <!-- Header -->
                <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                    <div class="flex items-center">
                        <div
                            class="flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 dark:bg-yellow-900/30">
                            <svg class="h-6 w-6 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4v2m0 4v2M6.3 6.3a9 9 0 1112.4 12.4M6.3 6.3L4.9 4.9m12.4 12.4l1.4 1.4">
                                </path>
                            </svg>
                        </div>
                        <h3 class="ml-4 text-lg font-semibold text-gray-900 dark:text-white">
                            Confirmar Cargue
                        </h3>
                    </div>
                </div>

                <!-- Body -->
                <div class="px-6 py-4">
                    @if($showFooter)
                    <p class="text-gray-600 dark:text-gray-300">
                        ¿Está seguro de que desea confirmar el cargue? <strong>Esta acción es irreversible</strong> y no
                        podrá deshacerla.
                    </p>
                    @endif

                    @if($showClearOptions)
                    <p class="text-gray-600 dark:text-gray-300">
                        ¿Desea limpiar la lista de cargue?
                    </p>
                    @endif
                </div>

                <!-- Footer -->
                @if($showFooter)
                <div
                    class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-4 px-6 pb-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" wire:click="cancelConfirmUpload"
                        class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 font-medium text-sm transition-colors">
                        Cancelar
                    </button>
                    <button type="button" wire:click="confirmUpload"
                        class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed border border-transparent rounded-lg font-medium text-sm text-white transition-colors">
                        Confirmar
                    </button>
                </div>
                @endif

                @if($showClearOptions)
                <div
                    class="flex flex-col sm:flex-row sm:justify-end gap-3 pt-4 px-6 pb-4 border-t border-gray-200 dark:border-gray-700">
                    <button type="button" wire:click="closeModal"
                        class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 font-medium text-sm transition-colors">
                        Cancelar
                    </button>
                    <button type="button" wire:click="clearListUpload"
                        class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed border border-transparent rounded-lg font-medium text-sm text-white transition-colors">
                        Si
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    <!-- Mensaje cuando hay faltantes -->
    @if($showScares)
    <div class="fixed inset-0 bg-gray-600 dark:bg-gray-900 bg-opacity-50 dark:bg-opacity-75 overflow-y-auto h-full w-full z-50"
        x-data="{ show: true }" x-show="show" x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                <!-- Header -->
                <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Unidades faltantes
                    </h3>
                </div>

                <div class="space-y-6">
                    <div class="mb-3">
                        <p class="px-3 pt-3">No hay productos suficientes</p>
                    </div>
                    <div
                        class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 px-6 pt-3">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Item</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Categoria</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Cantidad perdida</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Stock Actual</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($scarceUnits as $su)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $su->nombre_item }}</td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $su->categoria }}</td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $su->cantidad_pedida }}</td>
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $su->stock_actual }}</td>
                                </tr>
                                @empty
                                <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 text-center">
                                    <p class="text-gray-500 dark:text-gray-400">No hay valores registrados para este
                                        item</p>
                                </div>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mb-3">
                        <p class="px-3 pt-3">¿Desea agregar unidades de productos?</p>
                    </div>
                    <div
                        class="flex flex-col sm:flex-row sm:justify-end gap-3 pt-4 px-4 pb-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" wire:click="closeAlertScares"
                            class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 font-medium text-sm transition-colors order-2 sm:order-1">
                            No
                        </button>
                        <button type="button" wire:click="openMovementForm"
                            class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed border border-transparent rounded-lg font-medium text-sm text-white transition-colors order-1 sm:order-2">
                            Si
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($showModal)
    <livewire:tenant.movements.movement-form :reusable=true />
    @endif


</div>

@script
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
                $wire.set('showModal', false);
            }
        });

        // Cerrar modal haciendo clic fuera
        const modalBackdrop = document.querySelector('[wire\\:key="modal-backdrop"]');
        if (modalBackdrop) {
            modalBackdrop.addEventListener('click', function(e) {
                if (e.target === this) {
                    $wire.set('showModal', false);
                }
            });
        }
    });

    // Debug: Verificar que Livewire responde
    if (typeof Livewire !== 'undefined') {
        Livewire.hook('request', ({
            uri,
            options,
            payload
        }) => {
            console.log('Livewire request:', {
                uri,
                payload
            });
        });

        Livewire.hook('response', ({
            status,
            component
        }) => {
            console.log('Livewire response:', status, component);
        });
    }

    window.addEventListener('open-movement-form', (e) => {
        // If you dispatch the event with the child component id: dispatchBrowserEvent('open-movement-form', { componentId: 'xyz' })
        if (e?.detail?.componentId) {
            try {
                if (typeof Livewire !== 'undefined') {
                    Livewire.find(e.detail.componentId).call('create');
                    return;
                }
            } catch (err) {
                console.error('Error finding Livewire component:', err);
            }
        }

        // Option A — recommended: wrap the movement form in <div id="movementFormLivewire"> livewire('tenant.movements.movement-form') </div>
        const wrapper = document.getElementById('movementFormLivewire');
        if (wrapper) {
            const lwEl = wrapper.querySelector('[wire\\:id]');
            if (lwEl) {
                const id = lwEl.getAttribute('wire:id');
                try {
                    if (typeof Livewire !== 'undefined') {
                        Livewire.find(id).call('create');
                        return;
                    }
                } catch (err) {
                    console.error('Error finding Livewire component by ID:', err);
                }
            }
        }
    });
@endscript