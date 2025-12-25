<div class="min-h-screen bg-gray-50 dark:bg-gray-900 p-6">
    <div class="max-w-12xl mx-auto">
        <!-- Header -->
        <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Parámetros Caja</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Gestion de registros</p>
                </div>
                @if($this->canOpenPettyCash())
                <button wire:click="create"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Crear Nueva caja
                </button>
                @endif
            </div>
        </div>


        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
            <!--CARD IZQUIERDO-->
            <div class="lg:col-span-5 xl:col-span-5">
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
                    <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                            <!-- Búsqueda -->
                            <div class="flex-1 max-w-md">
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                    </div>
                                    <input wire:model.live.debounce.300ms="search" type="text"
                                        placeholder="Buscar registros..."
                                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                </div>
                            </div>

                            <!-- Controles -->
                            <div class="flex items-center gap-3">
                                <!-- Registros por página -->
                                <div class="flex items-center gap-2">
                                    <label class="text-sm text-gray-700 dark:text-gray-300">Mostrar:</label>
                                    <select wire:model.live="perPage"
                                        class="border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="5">5</option>
                                        <option value="10">10</option>
                                        <option value="25">25</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla -->
                    <div class="relative overflow-visible">
                        <div class="min-w-full overflow-x-auto">
                            <table class="w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th wire:click="sortBy('name')"
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 select-none">
                                        <div class="flex items-center gap-1">
                                            #
                                            @if($sortField === 'consecutive')
                                            @if($sortDirection === 'desc')
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z">
                                                </path>
                                            </svg>
                                            @else
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path
                                                    d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z">
                                                </path>
                                            </svg>
                                            @endif
                                            @endif
                                        </div>
                                    </th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Cajero</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Fecha</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Estado</th>
                                    <th
                                        class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Detalle</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse ($boxes as $bx)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{$bx->consecutive}}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{$bx->name}}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{$bx->created_at}}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        @if ($bx->status == 1)
                                        <span
                                            class="w-full text-left px-4 py-2 text-sm text-green-800 dark:text-green-300 hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors flex items-center">
                                            Abierta
                                        </span>
                                        @else
                                        <span
                                            class="w-full text-left px-4 py-2 text-sm text-red-800 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors flex items-center">
                                            Cerrada
                                        </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <div x-data="{ open: false }" @click.outside="open = false"
                                            class="relative inline-block text-left static"
                                            style="position: static !important;">
                                            <button @click="open = !open" x-ref="button"
                                                class="flex items-center text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-lg p-1 transition-colors">
                                                <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                    <path
                                                        d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                                </svg>
                                            </button>

                                            <div x-show="open" x-transition:enter="transition ease-out duration-100"
                                                x-transition:enter-start="transform opacity-0 scale-95"
                                                x-transition:enter-end="transform opacity-100 scale-100"
                                                x-transition:leave="transition ease-in duration-75"
                                                x-transition:leave-start="transform opacity-100 scale-100"
                                                x-transition:leave-end="transform opacity-0 scale-95"
                                                @click="open = false"
                                                class="origin-top-left fixed left-auto right-auto mt-2 w-48 rounded-lg shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 dark:ring-gray-700 z-[60]"
                                                x-anchor="$refs.button"
                                                style="display: none;">
                                                <div class="py-1" role="menu" aria-orientation="vertical">
                                                    <button wire:click="viewDetail({{ $bx->id }})"
                                                        class="w-full text-left px-4 py-2 text-sm text-blue-800 dark:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors flex items-center">
                                                        <x-heroicon-o-eye class="w-6 h-6 mr-1" />
                                                        Ver Detalle
                                                    </button>
                                                    <button wire:click="viewReconciliations({{ $bx->id }})"
                                                        class="w-full text-left px-4 py-2 text-sm text-purple-800 dark:text-purple-300 hover:bg-purple-50 dark:hover:bg-purple-900/20 transition-colors flex items-center">
                                                        <x-heroicon-o-document-check class="w-6 h-6 mr-1" />
                                                        Reconciliaciones
                                                    </button>
                                                    @if ($bx->status==1)
                                                    <button wire:click="openSalesFinishModal({{ $bx->id }})"
                                                        class="w-full text-left px-4 py-2 text-sm text-orange-800 dark:text-orange-300 hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-colors flex items-center">
                                                        <x-heroicon-o-lock-closed class="w-6 h-6 mr-1" />
                                                        Arqueo/Cerrar
                                                    </button>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 mb-4 text-gray-400 dark:text-gray-600" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                                                </path>
                                            </svg>
                                            <p class="text-lg font-medium">No se encontraron registros</p>
                                            <p class="text-sm">{{ $search ? 'Intenta ajustar tu búsqueda' : 'Comienza
                                                creando una nueva caja' }}</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        </div>
                    </div>

                    <!-- Paginación -->
                    @if($boxes->hasPages())
                    <div
                        class="bg-white dark:bg-gray-800 px-6 py-3 border-t border-gray-200 dark:border-gray-700 rounded-b-lg">
                        <div class="flex items-center justify-between">
                            <div class="text-sm text-gray-700 dark:text-gray-300">
                                Mostrando {{ $boxes->firstItem() }} a {{ $boxes->lastItem() }} de {{ $boxes->total() }}
                                resultados
                            </div>
                            <div>
                                {{ $boxes->links() }}
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!--CARD DERECHO-->
            <div class="lg:col-span-7 xl:col-span-7">
                @if($showDetail)
                @livewire('tenant.petty-cash.detail-petty-cash',['pettyCash_id'=>$pettyCash_id], key($pettyCash_id))
                @elseif($showReconciliations)
                @livewire('tenant.petty-cash.unreconciled-reconciliations',['pettyCash_id'=>$pettyCash_id], key($pettyCash_id . '-reconciliations'))
                @else
                <!-- Placeholder cuando no hay detalle seleccionado -->
                <div
                    class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 h-full flex items-center justify-center p-12">
                    <div class="text-center">
                        <svg class="w-24 h-24 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none"
                            stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                                d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z">
                            </path>
                        </svg>
                        <h3 class="text-xl font-semibold text-gray-500 dark:text-gray-400 mb-2">Selecciona una caja</h3>
                        <p class="text-gray-400 dark:text-gray-500">Haz clic en "Ver Detalle" o "Ver Reconciliaciones" para ver la información de una caja específica</p>
                    </div>
                </div>
                @endif
            </div>



        </div>
    </div>


    <!-- Modal -->
    @if($showModal)
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
                <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Crear Caja
                        </h3>
                    </div>
                    <button wire:click="cancel"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <x-heroicon-o-x-mark class="w-6 h-6" />
                    </button>
                </div>
                <!-- Form -->
                <form wire:submit.prevent="save" class="p-6 space-y-6">
                    <div class="space-y-6">
                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Base
                                *</label>
                            <input wire:model="base" type="number" id="base"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Ingrese el monto de la base">
                            @error('base') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>
                        <div
                            class="flex flex-col sm:flex-row sm:justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button type="button" wire:click="cancel"
                                class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 font-medium text-sm transition-colors order-2 sm:order-1">
                                Cancelar
                            </button>
                            <button type="submit" wire:loading.attr="disabled"
                                class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed border border-transparent rounded-lg font-medium text-sm text-white transition-colors order-1 sm:order-2">
                                <span>Crear</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Arqueo / Cierre -->
    @if ($showModalSalesFinish)
    <div class="fixed inset-0 bg-gray-600 dark:bg-gray-900 bg-opacity-50 dark:bg-opacity-75 overflow-y-auto h-full w-full z-50"
        x-data="{ show: true }" x-show="show" x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                <!-- Header -->
                <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Cierre de caja
                        </h3>
                        <button type="button" wire:click="$set('showModalSalesFinish', false)"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <x-heroicon-o-x-mark class="w-6 h-6" />
                        </button>
                    </div>
                </div>
                <!-- Contenido -->
                <div class="p-6">
                    <!-- Título -->
                    <div class="text-center mb-8">
                        <h4 class="text-xl font-bold text-gray-900 dark:text-white mb-2">Arqueo o cierre de caja</h4>
                    </div>

                    <!-- Tabla de formas de pago -->
                    <div class="mb-8">
                        <!-- Encabezados de la tabla -->
                        <div class="grid grid-cols-12 gap-4 mb-4 px-4">
                            <div class="col-span-6">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Forma de
                                    pago</span>
                            </div>
                            <div class="col-span-6 text-center">
                                <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Conteo</span>
                            </div>
                        </div>

                        <!-- Lista de formas de pago -->
                        <div class="space-y-2">
                            @php
                            $paymentMethods = [
                            '1' => 'Efectivo',
                            '2' => 'Transferencia',
                            '4' => 'Tarjeta de Crédito',
                            '10' => 'Tarjeta Débito',
                            '11' => 'Nequi',
                            '12' => 'Daviplata'
                            ];
                            @endphp

                            @foreach($paymentMethods as $key => $method)
                            <div
                                class="grid grid-cols-12 gap-4 items-center p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg">
                                <div class="col-span-6">
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $method }}</span>
                                </div>
                                <div class="col-span-6">
                                    <input type="number" wire:model.live="paymentCounts.{{ $key }}"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-center focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                        placeholder="0" min="0">
                                </div>
                            </div>
                            @endforeach

                            <!-- Total -->
                            <div
                                class="grid grid-cols-12 gap-4 items-center p-4 bg-indigo-50 dark:bg-indigo-900/20 border border-indigo-200 dark:border-indigo-800 rounded-lg mt-4">
                                <div class="col-span-6">
                                    <span class="text-sm font-bold text-indigo-900 dark:text-indigo-100">TOTAL</span>
                                </div>
                                <div class="col-span-3 text-center">
                                    <span class="text-sm font-bold text-indigo-900 dark:text-indigo-100">
                                        {{ array_sum($paymentCounts) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Línea divisoria -->
                    <div class="border-t border-gray-200 dark:border-gray-700 my-6"></div>

                    <!-- Observaciones -->
                    <div class="mb-6">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            Observaciones (Opcional)
                        </label>
                        <textarea wire:model="observations" rows="3"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="Ingresa cualquier observación adicional sobre el cierre de caja..."></textarea>
                    </div>

                    <!-- Botones de acción -->
                    <div
                        class="flex flex-col sm:flex-row sm:justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" wire:click="$set('showModalSalesFinish', false)"
                            class="inline-flex items-center justify-center px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 font-medium text-sm transition-colors">
                            Cancelar
                        </button>
                        <button type="button" wire:click="closePettyCash()"
                            class="inline-flex items-center justify-center px-6 py-3 bg-[#6958a7] hover:bg-[#564889] dark:bg-[#6958a7] dark:hover:bg-[#564889] disabled:opacity-50 disabled:cursor-not-allowed border border-transparent rounded-lg font-medium text-sm text-white transition-colors">
                            <span>Cerrar Caja</span>
                        </button>
                        <button type="button" wire:click="arqueoPettyCash"
                            class="inline-flex items-center justify-center px-6 py-3 bg-[#9f94c7] hover:bg-[#8476b7] dark:bg-[#9f94c7] dark:hover:bg-[#8476b7] disabled:opacity-50 disabled:cursor-not-allowed border border-transparent rounded-lg font-medium text-sm text-white transition-colors">
                            <span>Arqueo Caja</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>