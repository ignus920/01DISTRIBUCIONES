<!-- Wrapper con padding y background -->
<div class="p-6 bg-gray-50 dark:bg-slate-900 min-h-screen transition-colors">
    <!-- Header Card -->
    <div class="bg-white dark:bg-slate-800 rounded-lg p-6 mb-6 border border-gray-200 dark:border-slate-700 transition-colors">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Cotizaciones</h1>
                <p class="text-gray-600 dark:text-slate-400 text-sm mt-1">Gestión de registros</p>
            </div>
            <div class="flex items-center space-x-3">
            

                <button
                    wire:click="nuevaCotizacion"
                    class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded text-sm font-medium flex items-center transition-colors"
                >
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Nueva Cotización
                </button>
            </div>
        </div>
    </div>

    <!-- Toolbar Card -->
    <div class="bg-white dark:bg-slate-800 rounded-lg p-4 mb-6 border border-gray-200 dark:border-slate-700 transition-colors">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
            <!-- Search Section -->
            <div class="flex-1 max-w-md">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input
                        type="text"
                        wire:model.live="search"
                        placeholder="Buscar registros..."
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200 placeholder-gray-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-slate-600 text-sm transition-colors"
                    >
                </div>
            </div>

            <!-- Actions Section -->
            <div class="flex items-center space-x-3">
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

                <!-- Botones de exportar -->
                        <div class="flex items-center gap-2">
                            <!-- Botón Excel -->
                            <button wire:click="exportExcel"
                                    title="Exportar a Excel"
                                    class="inline-flex items-center justify-center p-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M21,5V19A2,2 0 0,1 19,21H5A2,2 0 0,1 3,19V5A2,2 0 0,1 5,3H19A2,2 0 0,1 21,5M19,5H12V7H19V5M19,9H12V11H19V9M19,13H12V15H19V13M19,17H12V19H19V17M5,5V7H10V5H5M5,9V11H10V9H5M5,13V15H10V13H5M5,17V19H10V17H5Z"/>
                                </svg>
                            </button>
                            <!-- Botón PDF -->
                            <button wire:click="exportPdf"
                                    title="Exportar a PDF"
                                    class="inline-flex items-center justify-center p-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z"/>
                                </svg>
                            </button>
                            <!-- Botón CSV -->
                            <button wire:click="exportCsv"
                                    title="Exportar a CSV"
                                    class="inline-flex items-center justify-center p-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20M8,12V14H16V12H8M8,16V18H13V16H8Z"/>
                                </svg>
                            </button>

                           
                        </div>
            </div>
        </div>
    </div>

    <!-- Success Message -->
    @if (session()->has('message'))
        <div class="bg-green-100 dark:bg-green-800 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-200 px-4 py-3 rounded-lg mb-6">
            {{ session('message') }}
        </div>
    @endif

    <!-- Data Table Card -->
    <div class="bg-white dark:bg-slate-800 rounded-lg overflow-hidden border border-gray-200 dark:border-slate-700 transition-colors">
        <div class="overflow-x-auto">

            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-slate-700">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-slate-300 transition-colors">
                            <div class="flex items-center space-x-1">
                                <span>COTIZACIÓN #</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                </svg>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-slate-300 transition-colors">
                            <div class="flex items-center space-x-1">
                                <span>CLIENTE</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                </svg>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider cursor-pointer hover:text-slate-300 transition-colors">
                            <div class="flex items-center space-x-1">
                                <span>TIPO</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                </svg>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider cursor-pointer hover:text-slate-300 transition-colors">
                            <div class="flex items-center space-x-1">
                                <span>ESTADO</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                </svg>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider cursor-pointer hover:text-slate-300 transition-colors">
                            <div class="flex items-center space-x-1">
                                <span>SUCURSAL</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                </svg>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider cursor-pointer hover:text-slate-300 transition-colors">
                            <div class="flex items-center space-x-1">
                                <span>TELÉFONO</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                </svg>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider cursor-pointer hover:text-slate-300 transition-colors">
                            <div class="flex items-center space-x-1">
                                <span>FECHA</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                </svg>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-slate-400 uppercase tracking-wider">
                            ACCIONES
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($quotes as $quote)
                        <tr class="border-b border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                #{{ $quote->consecutive }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-slate-300">
                                @if($quote->customer)
                                    {{ $quote->customer_name }}
                                    @if($quote->customer->billingEmail)
                                        <br><small class="text-gray-500">{{ $quote->customer->billingEmail }}</small>
                                    @endif
                                    @if($quote->customer->identification)
                                        <br><small class="text-gray-500">{{ $quote->customer->identification }}</small>
                                    @endif
                                @else
                                    <span class="text-gray-400">Sin cliente asignado</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-slate-300">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    @if($quote->typeQuote === 'POS') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                    @else bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 @endif">
                                    {{ $quote->typeQuote }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-slate-300">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    @if($quote->status === 'REGISTRADO') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @elseif($quote->status === 'ANULADO') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                    @elseif($quote->status === 'FACTURADO') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                    @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 @endif">
                                    {{ $quote->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-slate-300">
                                @if($quote->warehouse)
                                    {{ $quote->warehouse->name }}
                                    @if($quote->warehouse->address)
                                        <br><small class="text-gray-500">{{ $quote->warehouse->address }}</small>
                                    @endif
                                @else
                                    <span class="text-gray-400">Sin sucursal</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-slate-300">
                                @if($quote->warehouse && $quote->warehouse->contacts && $quote->warehouse->contacts->isNotEmpty())
                                    @foreach($quote->warehouse->contacts->take(2) as $contact)
                                        @if($contact->business_phone)
                                            {{ $contact->business_phone }}
                                            @if(!$loop->last)<br>@endif
                                        @elseif($contact->personal_phone)
                                            {{ $contact->personal_phone }}
                                            @if(!$loop->last)<br>@endif
                                        @endif
                                    @endforeach
                                @else
                                    <span class="text-gray-400">Sin contacto</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-slate-300">
                                {{ $quote->created_at->format('d/m/Y H:i') }}
                            </td>

                            <!---Botones de accion--->
                              <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <!-- Menú de tres puntos con Alpine.js -->
                                <div x-data="{ open: false }" @click.outside="open = false" class="relative inline-block text-left">
                                    <button @click="open = !open"
                                        class="flex items-center text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-lg p-1 transition-colors">
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                        </svg>
                                    </button>

                                    <!-- Menú desplegable -->
                                    <div x-show="open"
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="transform opacity-0 scale-95"
                                        x-transition:enter-end="transform opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="transform opacity-100 scale-100"
                                        x-transition:leave-end="transform opacity-0 scale-95"
                                        @click="open = false"
                                        class="origin-top-right absolute right-0 mt-2 w-48 rounded-lg shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 dark:ring-gray-700 z-50"
                                        style="display: none;">
                                        <div class="py-1" role="menu" aria-orientation="vertical">
                                            <button wire:click="editarCotizacion({{ $quote->id }})"
                                                class="w-full text-left px-4 py-2 text-sm text-yellow-800 dark:text-yellow-300 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition-colors flex items-center">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                                Editar
                                            </button>
                                            <button wire:click="printQuote({{ $quote->id }})"
                                                class="w-full text-left px-4 py-2 text-sm text-green-800 dark:text-green-300 hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors flex items-center">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                                </svg>
                                                Imprimir
                                            </button>
                                           
                                          
                                        </div>
                                    </div>
                                </div>
                            </td>
                            



                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="text-gray-500 dark:text-slate-400">
                                    <svg class="mx-auto h-12 w-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-700 dark:text-slate-300 mb-2">No hay registros</h3>
                                    <p class="mb-6">
                                        @if($search)
                                            No se encontraron registros que coincidan con "{{ $search }}".
                                        @else
                                            Comienza creando tu primer registro.
                                        @endif
                                    </p>
                                    @if(!$search)
                                        <button
                                            wire:click="nuevaCotizacion"
                                            class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded font-medium transition-colors"
                                        >
                                            Crear Primer Registro
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        @if($quotes->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-600 dark:text-slate-300">
                        Mostrando <span class="font-medium">{{ $quotes->firstItem() ?? 0 }}</span> a
                        <span class="font-medium">{{ $quotes->lastItem() ?? 0 }}</span> de
                        <span class="font-medium">{{ $quotes->total() }}</span> resultados
                    </div>
                    <div>
                        {{ $quotes->links() }}
                    </div>
                </div>
            </div>
        @endif
        </div>
    </div>
</div>

{{-- Script de impresión inline para asegurar funcionamiento en producción --}}
<script>
// Prevenir múltiples configuraciones de listeners
if (typeof window.quoterPrintListenerConfigured === 'undefined') {
    window.quoterPrintListenerConfigured = true;

    // Función de impresión inline para producción
    function openPrintWindow(eventData) {
        console.log('🖨️ openPrintWindow ejecutada (inline):', eventData);

        const data = Array.isArray(eventData) ? eventData[0] : eventData;
        const url = data.url;
        const format = data.format;

        console.log('🔗 URL a imprimir:', url, '📄 Formato:', format);

        // Tamaño de ventana según formato
        const features = format === 'pos'
            ? 'width=400,height=600,scrollbars=yes,resizable=yes,menubar=no,toolbar=no'
            : 'width=800,height=900,scrollbars=yes,resizable=yes,menubar=no,toolbar=no';

        // Abrir ventana
        const win = window.open(url, 'printWindow_' + Date.now(), features);

        if (!win) {
            alert('⚠️ No se pudo abrir la ventana. Verifica que las ventanas emergentes estén permitidas.');
            return;
        }

        console.log('✅ Ventana abierta correctamente');
        win.focus();

        // Auto impresión cuando la página cargue
        win.onload = function() {
            setTimeout(() => {
                console.log('🖨️ Iniciando impresión automática...');
                win.print();
            }, 800);
        };
    }

    // Función para configurar listener una sola vez
    function configurePrintListener() {
        if (window.Livewire && !window.quoterPrintListenerRegistered) {
            window.quoterPrintListenerRegistered = true;
            Livewire.on('open-print-window', openPrintWindow);
            console.log('✅ Listener Livewire configurado una sola vez');
        }
    }

    // Configurar listeners cuando el documento esté listo
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🔧 Configurando listeners de impresión inline...');
        configurePrintListener();
    });

    // También configurar cuando Livewire se inicialice
    document.addEventListener('livewire:initialized', function() {
        console.log('🔧 Livewire inicializado, verificando configuración...');
        configurePrintListener();
    });

    // Para Livewire 3 también
    document.addEventListener('livewire:navigated', function() {
        console.log('🔧 Livewire navegado, verificando configuración...');
        configurePrintListener();
    });

    console.log('🛡️ Sistema de impresión protegido contra duplicados');
}
</script>

