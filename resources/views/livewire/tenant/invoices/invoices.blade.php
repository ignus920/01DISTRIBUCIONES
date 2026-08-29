<div class="p-6 bg-gray-50 dark:bg-slate-900 min-h-screen transition-colors">

    {{-- ===== ENCABEZADO ===== --}}
    <div class="bg-white dark:bg-slate-800 rounded-lg p-6 mb-6 border border-gray-200 dark:border-slate-700">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Facturas</h1>
                <p class="text-gray-600 dark:text-slate-400 text-sm mt-1">Gestión y seguimiento de facturas electrónicas</p>
            </div>
        </div>
    </div>

    {{-- ===== BARRA DE HERRAMIENTAS ===== --}}
    <div class="bg-white dark:bg-slate-800 rounded-lg p-4 mb-6 border border-gray-200 dark:border-slate-700">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">

            {{-- Buscador y Filtros de Fecha --}}
            <div class="flex-1 flex flex-col lg:flex-row lg:items-center gap-3">
                
                {{-- Buscador --}}
                <div class="flex-1 lg:max-w-md">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-gray-400 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </div>
                        <input type="text" wire:model.live="search"
                            placeholder="Buscar por número, cliente, remisión..."
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200 placeholder-gray-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm transition-all">
                    </div>
                </div>

                {{-- Filtros de Fecha Compactos (Estilo Solicitado) --}}
                <div class="flex items-center space-x-2 bg-gray-50 dark:bg-slate-700/50 px-3 py-1.5 border border-gray-200 dark:border-slate-600 rounded-lg transition-colors">
                    <div class="flex items-center space-x-2">
                        <span class="text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-tighter">Desde:</span>
                        <input type="date" wire:model.live="fromDate"
                            class="bg-transparent border-none text-xs text-gray-700 dark:text-slate-200 focus:ring-0 p-0 w-28">
                    </div>
                    <div class="h-4 w-px bg-gray-300 dark:bg-slate-600"></div>
                    <div class="flex items-center space-x-2">
                        <span class="text-[10px] font-bold text-gray-400 dark:text-slate-500 uppercase tracking-tighter">Hasta:</span>
                        <input type="date" wire:model.live="toDate"
                            class="bg-transparent border-none text-xs text-gray-700 dark:text-slate-200 focus:ring-0 p-0 w-28">
                    </div>
                </div>

            </div>

            {{-- Per page --}}
            <div class="flex items-center gap-2">
                <label class="text-xs font-semibold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Mostrar:</label>
                <select wire:model.live="perPage"
                    class="border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 py-1.5 pr-8 transition-all">
                    <option value="12">12</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
            </div>
        </div>
    </div>

    {{-- ===== TABLA (DESKTOP) ===== --}}
    <div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700">
        <div class="hidden lg:block overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-700/50">
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer"
                            wire:click="sortBy('vnt_invoices.invoiceNumber')">
                            <div class="flex items-center gap-1">
                                FACTURA #
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/></svg>
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer"
                            wire:click="sortBy('vnt_invoices.created_at')">
                            <div class="flex items-center gap-1">
                                FECHA
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"/></svg>
                            </div>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">REMISIÓN(ES)</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">CLIENTE</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">TIPO</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">TOTAL</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">ESTADO</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">PAGO</th>
                        <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">ACCIONES</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $invoice)
                        @php
                            $paymentStatus = strtoupper(trim((string) $invoice->status_payment));
                        @endphp
                        <tr class="border-b border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors">

                            {{-- FACTURA # --}}
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="text-sm font-bold text-gray-900 dark:text-white">
                                    {{ $invoice->invoiceNumber ?: '#' . $invoice->consecutive }}
                                </span>
                            </td>

                            {{-- FECHA --}}
                            <td class="px-4 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-slate-300">
                                <div class="font-medium">{{ $invoice->created_at->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-400">{{ $invoice->created_at->format('H:i') }}</div>
                            </td>

                            {{-- REMISIÓN(ES) --}}
                            <td class="px-4 py-4 text-sm text-gray-600 dark:text-slate-400">
                                @if($invoice->remission_consecutive)
                                    <span class="font-mono text-xs">{{ $invoice->remission_consecutive }}</span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>

                            {{-- CLIENTE --}}
                            <td class="px-4 py-4 text-sm text-gray-700 dark:text-slate-300 max-w-[180px]">
                                <span class="truncate block" title="{{ trim($invoice->client_name) }}">
                                    {{ trim($invoice->client_name) ?: 'N/A' }}
                                </span>
                            </td>

                            {{-- TIPO --}}
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="px-2 py-0.5 text-xs font-medium rounded-full
                                    {{ $invoice->tipo_factura === 'REMISIONADA'
                                        ? 'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300'
                                        : 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300' }}">
                                    {{ $invoice->tipo_factura }}
                                </span>
                            </td>

                            {{-- TOTAL --}}
                            <td class="px-4 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white text-right">
                                ${{ number_format($invoice->total_con_impuestos, 0, ',', '.') }}
                            </td>

                            {{-- ESTADO FACTURA --}}
                            <td class="px-4 py-4 whitespace-nowrap">
                                <div class="flex flex-col gap-1">
                                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                                        @if($invoice->status === 'FACTURADO') bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300
                                        @elseif($invoice->status === 'SIN EMITIR') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300
                                        @elseif($invoice->status === 'ANULADO') bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300
                                        @else bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 @endif">
                                        {{ $invoice->status }}
                                    </span>
                                    @if($invoice->creditNote)
                                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300 w-fit">
                                            N.C. {{ $invoice->creditNoteId }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- ESTADO PAGO --}}
                            <td class="px-4 py-4 whitespace-nowrap">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    @if($paymentStatus === 'PAGADO') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300
                                    @elseif($paymentStatus === 'ABONO') bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300
                                    @elseif($paymentStatus === 'ANULADO') bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300
                                    @else bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 @endif">
                                    <span class="{{ $paymentStatus === 'PAGADO' ? 'text-green-700 dark:text-green-300' : '' }}">
                                        {{ $invoice->status_payment }}
                                    </span>
                                </span>
                            </td>

                            {{-- ACCIONES --}}
                            <td class="px-4 py-4 whitespace-nowrap text-center">
                                <div x-data="{ open: false, top: 0, left: 0 }"
                                     @scroll.window="open = false"
                                     class="relative inline-block text-left">
                                    <button @click="
                                            open = !open;
                                            $nextTick(() => {
                                                const rect = $el.getBoundingClientRect();
                                                top = rect.bottom + window.scrollY;
                                                left = rect.right - 192;
                                            });"
                                        class="flex items-center text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-lg p-1 transition-colors">
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                                        </svg>
                                    </button>
                                    <div x-show="open"
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="transform opacity-0 scale-95"
                                        x-transition:enter-end="transform opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="transform opacity-100 scale-100"
                                        x-transition:leave-end="transform opacity-0 scale-95"
                                        @click.outside="open = false"
                                        class="fixed w-48 rounded-lg shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 dark:ring-gray-700 z-[9999]"
                                        :style="`top: ${top}px; left: ${left}px;`"
                                        style="display: none;">
                                        <div class="py-1">

                                            {{-- Descargar PDF (solo FACTURADO) --}}
                                            @if($invoice->status === 'FACTURADO')
                                                <button wire:click="downloadFacturaPdf({{ $invoice->id }})" @click="open = false"
                                                    wire:loading.attr="disabled" wire:target="downloadFacturaPdf({{ $invoice->id }})"
                                                    class="w-full text-left px-4 py-2 text-sm text-green-800 dark:text-green-300 hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors flex items-center gap-2">
                                                    <svg wire:loading.remove wire:target="downloadFacturaPdf({{ $invoice->id }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                                                    <svg wire:loading wire:target="downloadFacturaPdf({{ $invoice->id }})" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                                    Descargar PDF
                                                </button>
                                            @endif

                                            {{-- Descargar N. Crédito --}}
                                            @if($invoice->creditNote && $invoice->creditNoteId)
                                                <button wire:click="downloadCreditNotePdf({{ $invoice->id }})" @click="open = false"
                                                    wire:loading.attr="disabled" wire:target="downloadCreditNotePdf({{ $invoice->id }})"
                                                    class="w-full text-left px-4 py-2 text-sm text-rose-700 dark:text-rose-300 hover:bg-rose-50 dark:hover:bg-rose-900/20 transition-colors flex items-center gap-2">
                                                    <svg wire:loading.remove wire:target="downloadCreditNotePdf({{ $invoice->id }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                                                    <svg wire:loading wire:target="downloadCreditNotePdf({{ $invoice->id }})" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                                    N. Crédito {{ $invoice->creditNoteId }}
                                                </button>
                                            @endif

                                            {{-- Emitir (solo SIN EMITIR) --}}
                                            @if($invoice->status === 'SIN EMITIR')
                                                <button wire:click="emitirFactura({{ $invoice->id }})" @click="open = false"
                                                    wire:loading.attr="disabled" wire:target="emitirFactura({{ $invoice->id }})"
                                                    class="w-full text-left px-4 py-2 text-sm text-yellow-800 dark:text-yellow-300 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition-colors flex items-center gap-2">
                                                    <svg wire:loading.remove wire:target="emitirFactura({{ $invoice->id }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                                    <svg wire:loading wire:target="emitirFactura({{ $invoice->id }})" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                                    Emitir Factura
                                                </button>
                                            @endif

                                            {{-- Pagar (solo FACTURADO y no PAGADO) --}}
                                            @if($invoice->status === 'FACTURADO' && $paymentStatus !== 'PAGADO' && $paymentStatus !== 'ANULADO')
                                                <button wire:click="payInvoice({{ $invoice->id }})" @click="open = false"
                                                    wire:loading.attr="disabled" wire:target="payInvoice({{ $invoice->id }})"
                                                    class="w-full text-left px-4 py-2 text-sm text-indigo-800 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors flex items-center gap-2">
                                                    <svg wire:loading.remove wire:target="payInvoice({{ $invoice->id }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                                    <svg wire:loading wire:target="payInvoice({{ $invoice->id }})" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                                    Registrar Pago
                                                </button>
                                            @endif

                                            {{-- Nota Crédito (solo FACTURADO y no PAGADO/ANULADO) --}}
                                            @if($invoice->status === 'FACTURADO' && $paymentStatus !== 'PAGADO' && $paymentStatus !== 'ANULADO')
                                                <button wire:click="openCreditNoteModal({{ $invoice->id }})" @click="open = false"
                                                    wire:loading.attr="disabled" wire:target="openCreditNoteModal({{ $invoice->id }})"
                                                    class="w-full text-left px-4 py-2 text-sm text-orange-700 dark:text-orange-300 hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-colors flex items-center gap-2">
                                                    <svg wire:loading.remove wire:target="openCreditNoteModal({{ $invoice->id }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l-4-4m0 0l4-4m-4 4h11a4 4 0 010 8H7"/></svg>
                                                    <svg wire:loading wire:target="openCreditNoteModal({{ $invoice->id }})" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                                    Nota Crédito
                                                </button>
                                            @endif

                                        </div>
                                    </div>
                                </div>
                            </td>

                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center text-gray-400 dark:text-slate-500">
                                    <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="font-medium italic">No se encontraron facturas</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- ===== TARJETAS (MOBILE) ===== --}}
        <div class="lg:hidden p-4 space-y-4">
            @forelse($invoices as $invoice)
                @php
                    $paymentStatus = strtoupper(trim((string) $invoice->status_payment));
                @endphp
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden">

                    {{-- Header --}}
                    <div class="bg-slate-900 dark:bg-slate-950 px-4 py-3 flex justify-between items-center">
                        <div>
                            <span class="text-white font-bold text-base">
                                {{ $invoice->invoiceNumber ?: 'Consec. #' . $invoice->consecutive }}
                            </span>
                        </div>
                        <div class="text-right">
                            <p class="text-[11px] font-bold text-slate-400 uppercase">{{ $invoice->created_at->format('d/m/Y') }}</p>
                            <p class="text-[10px] text-slate-500">{{ $invoice->created_at->format('H:i') }}</p>
                        </div>
                    </div>

                    <div class="p-4 space-y-3">

                        {{-- Cliente --}}
                        <div>
                            <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">CLIENTE</p>
                            <p class="text-sm font-bold text-gray-900 dark:text-white">{{ trim($invoice->client_name) ?: 'N/A' }}</p>
                        </div>

                        {{-- Remisión / Tipo / Total --}}
                        <div class="grid grid-cols-3 gap-3 pt-2 border-t border-gray-50 dark:border-slate-700/50">
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">REMISIÓN(ES)</p>
                                <p class="text-xs text-gray-700 dark:text-slate-300 font-mono">{{ $invoice->remission_consecutive ?: '—' }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">TIPO</p>
                                <span class="px-1.5 py-0.5 text-[10px] font-bold rounded-full
                                    {{ $invoice->tipo_factura === 'REMISIONADA'
                                        ? 'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300'
                                        : 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300' }}">
                                    {{ $invoice->tipo_factura }}
                                </span>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">TOTAL</p>
                                <p class="text-sm font-bold text-gray-900 dark:text-white">${{ number_format($invoice->total_con_impuestos, 0, ',', '.') }}</p>
                            </div>
                        </div>

                        {{-- Estados --}}
                        <div class="grid grid-cols-3 gap-3 pt-2 border-t border-gray-50 dark:border-slate-700/50">
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">ESTADO</p>
                                <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full
                                    @if($invoice->status === 'FACTURADO') bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300
                                    @elseif($invoice->status === 'SIN EMITIR') bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300
                                    @elseif($invoice->status === 'ANULADO') bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300
                                    @else bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 @endif">
                                    {{ $invoice->status }}
                                </span>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">PAGO</p>
                                <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full
                                    @if($paymentStatus === 'PAGADO') bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300
                                    @elseif($paymentStatus === 'ABONO') bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300
                                    @elseif($paymentStatus === 'ANULADO') bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300
                                    @else bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300 @endif">
                                    <span class="{{ $paymentStatus === 'PAGADO' ? 'text-green-700 dark:text-green-300' : '' }}">
                                        {{ $invoice->status_payment }}
                                    </span>
                                </span>
                            </div>
                            <div>
                                @if($invoice->creditNote)
                                    <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">N.C.</p>
                                    <span class="inline-block px-2 py-0.5 text-[10px] font-bold rounded-full bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">
                                        {{ $invoice->creditNoteId }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Acciones --}}
                        <div class="flex items-center gap-2 pt-3 border-t border-gray-100 dark:border-slate-700">
                            @if($invoice->status === 'FACTURADO')
                                <button wire:click="downloadFacturaPdf({{ $invoice->id }})"
                                    wire:loading.attr="disabled" wire:target="downloadFacturaPdf({{ $invoice->id }})"
                                    class="flex-1 bg-green-600 hover:bg-green-700 text-white rounded-xl py-2.5 text-xs font-bold flex items-center justify-center gap-2 transition-all active:scale-95 disabled:opacity-50">
                                    <svg wire:loading.remove wire:target="downloadFacturaPdf({{ $invoice->id }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                                    <svg wire:loading wire:target="downloadFacturaPdf({{ $invoice->id }})" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    PDF
                                </button>
                            @endif

                            @if($invoice->creditNote && $invoice->creditNoteId)
                                <button wire:click="downloadCreditNotePdf({{ $invoice->id }})"
                                    wire:loading.attr="disabled" wire:target="downloadCreditNotePdf({{ $invoice->id }})"
                                    class="flex-1 bg-rose-600 hover:bg-rose-700 text-white rounded-xl py-2.5 text-xs font-bold flex items-center justify-center gap-2 transition-all active:scale-95 disabled:opacity-50">
                                    <svg wire:loading.remove wire:target="downloadCreditNotePdf({{ $invoice->id }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3M3 17V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/></svg>
                                    <svg wire:loading wire:target="downloadCreditNotePdf({{ $invoice->id }})" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    N.C.
                                </button>
                            @endif

                            @if($invoice->status === 'SIN EMITIR')
                                <button wire:click="emitirFactura({{ $invoice->id }})"
                                    wire:loading.attr="disabled" wire:target="emitirFactura({{ $invoice->id }})"
                                    class="flex-1 bg-yellow-500 hover:bg-yellow-600 text-white rounded-xl py-2.5 text-xs font-bold flex items-center justify-center gap-2 transition-all active:scale-95 disabled:opacity-50">
                                    <svg wire:loading.remove wire:target="emitirFactura({{ $invoice->id }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                                    <svg wire:loading wire:target="emitirFactura({{ $invoice->id }})" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    Emitir
                                </button>
                            @endif

                            @if($invoice->status === 'FACTURADO' && $paymentStatus !== 'PAGADO' && $paymentStatus !== 'ANULADO')
                                <button wire:click="payInvoice({{ $invoice->id }})"
                                    wire:loading.attr="disabled" wire:target="payInvoice({{ $invoice->id }})"
                                    class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl py-2.5 text-xs font-bold flex items-center justify-center gap-2 transition-all active:scale-95 disabled:opacity-50">
                                    <svg wire:loading.remove wire:target="payInvoice({{ $invoice->id }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                    <svg wire:loading wire:target="payInvoice({{ $invoice->id }})" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    Pagar
                                </button>
                            @endif

                            @if($invoice->status === 'FACTURADO' && $paymentStatus !== 'PAGADO' && $paymentStatus !== 'ANULADO')
                                <button wire:click="openCreditNoteModal({{ $invoice->id }})"
                                    wire:loading.attr="disabled" wire:target="openCreditNoteModal({{ $invoice->id }})"
                                    class="flex-1 bg-orange-500 hover:bg-orange-600 text-white rounded-xl py-2.5 text-xs font-bold flex items-center justify-center gap-2 transition-all active:scale-95 disabled:opacity-50">
                                    <svg wire:loading.remove wire:target="openCreditNoteModal({{ $invoice->id }})" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l-4-4m0 0l4-4m-4 4h11a4 4 0 010 8H7"/></svg>
                                    <svg wire:loading wire:target="openCreditNoteModal({{ $invoice->id }})" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                    NC
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white dark:bg-slate-800 p-12 text-center rounded-xl border-2 border-dashed border-gray-100 dark:border-slate-700">
                    <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-gray-500 font-medium italic">No se encontraron facturas</p>
                </div>
            @endforelse
        </div>

        {{-- Paginación --}}
        @if($invoices->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-700">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>

    {{-- ===== MODAL NOTA CRÉDITO ===== --}}
    @if($showCreditNoteModal && $creditNoteInvoiceData)
    <div x-data>
    <template x-teleport="body">
<div class="fixed inset-0 z-50 flex items-center justify-center p-6"
     x-data
     x-effect="document.body.style.overflow = $el.offsetParent ? 'hidden' : ''">

        {{-- Overlay --}}
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" wire:click="closeCreditNoteModal"></div>

        {{-- Modal Container --}}
        <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-2xl w-full max-w-[900px] max-h-[90vh] flex flex-col border border-gray-200 dark:border-slate-700 overflow-hidden">

            {{-- Header --}}
            <div class="shrink-0 flex items-center justify-between px-4 sm:px-6 py-4 border-b border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-700/50">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Crear Nota Crédito</h2>
                <button wire:click="closeCreditNoteModal"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors p-1 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Scrollable Body --}}
            <div class="overflow-y-auto overscroll-contain flex-1 p-4 sm:p-6 space-y-5">

                @php
                    $invoiceOriginalTotal = array_sum(array_column($creditNoteItems, 'total'));
                @endphp

                {{-- Resumen de Factura --}}
                <div class="bg-gray-50 dark:bg-slate-700/40 rounded-lg p-4 border border-gray-200 dark:border-slate-600">
                    <h3 class="text-sm font-bold text-gray-700 dark:text-slate-300 mb-3 uppercase tracking-wide">Resumen de Factura</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <span class="text-xs text-gray-500 dark:text-slate-400">Factura No.:</span>
                            <span class="ml-1 text-sm font-bold text-gray-900 dark:text-white">{{ $creditNoteInvoiceData['invoiceNumber'] }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-slate-400">Total Factura:</span>
                            <span class="ml-1 text-sm font-bold text-gray-900 dark:text-white">$ {{ number_format($invoiceOriginalTotal, 0, ',', '.') }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-slate-400">Cliente:</span>
                            <span class="ml-1 text-sm text-gray-800 dark:text-slate-200">{{ $creditNoteInvoiceData['client_name'] }}</span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-slate-400">Estado Pago:</span>
                            <span class="ml-1 text-sm font-semibold
                                {{ $creditNoteInvoiceData['status_payment'] === 'REGISTRADO' ? 'text-blue-600 dark:text-blue-400' : 'text-orange-600 dark:text-orange-400' }}">
                                {{ $creditNoteInvoiceData['status_payment'] }}
                            </span>
                        </div>
                        <div>
                            <span class="text-xs text-gray-500 dark:text-slate-400">Estado:</span>
                            <span class="ml-1 text-sm font-semibold text-green-600 dark:text-green-400">{{ $creditNoteInvoiceData['status'] }}</span>
                        </div>
                    </div>
                </div>

                {{-- Motivo de la Nota Crédito --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-2">
                        Motivo de la Nota Crédito <span class="text-red-500">*</span>
                    </label>
                    <select wire:model.live="correctionConceptCode"
                        class="w-full border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200 text-sm px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="1">Devolución parcial de bienes / no aceptación parcial del servicio</option>
                        <option value="2">Anulación de factura electrónica</option>
                        <option value="3">Rebaja o descuento parcial o total</option>
                        <option value="4">Ajuste de precio</option>
                        <option value="5">Descuento comercial por pronto pago</option>
                        <option value="6">Descuento comercial por volumen de ventas</option>
                    </select>
                    @if($correctionConceptCode === '2')
                        <p class="mt-1.5 text-xs text-green-700 dark:text-green-400 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            Anulación de factura: Todos los ítems han sido seleccionados automáticamente
                        </p>
                    @endif
                </div>

                {{-- Método de Pago --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-2">Método de Pago</label>
                    <select wire:model="creditNotePaymentMethod"
                        class="w-full border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200 text-sm px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="10">Efectivo</option>
                        <option value="42">Consignación</option>
                        <option value="20">Cheque</option>
                        <option value="47">Transferencia</option>
                        <option value="48">Tarjeta Crédito</option>
                        <option value="49">Tarjeta Débito</option>
                        <option value="1">Medio de pago no definido</option>
                    </select>
                </div>

                {{-- Observaciones --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-2">Observaciones</label>
                    <textarea wire:model="creditNoteObservation" rows="3" maxlength="250"
                        placeholder="Describa el motivo de la nota crédito"
                        class="w-full border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200 placeholder-gray-400 text-sm px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-orange-500 resize-none"></textarea>
                    <p class="text-xs text-gray-400 mt-1">{{ strlen($creditNoteObservation) }}/250 caracteres</p>
                </div>

                {{-- Ítems de la Factura --}}
                <div>
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-sm font-bold text-gray-700 dark:text-slate-300 uppercase tracking-wide">Ítems de la Factura</h3>
                        <div class="flex gap-2">
                            <button wire:click="selectAllCreditNoteItems" type="button"
                                class="flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg border border-blue-300 text-blue-700 dark:text-blue-400 dark:border-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                Seleccionar Todos
                            </button>
                            <button wire:click="deselectAllCreditNoteItems" type="button"
                                class="flex items-center gap-1 px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-300 text-gray-600 dark:text-gray-400 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                Deseleccionar Todos
                            </button>
                        </div>
                    </div>

                    @php
                        $selectedCount = count(array_filter($creditNoteItems, function ($i) {
                            return !empty($i['selected']);
                        }));
                    @endphp
                    <div class="overflow-auto rounded-lg border border-gray-200 dark:border-slate-600 max-h-52">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-gray-100 dark:bg-slate-700 border-b border-gray-200 dark:border-slate-600">
                                    <th class="px-3 py-2.5 w-8">
                                        <input type="checkbox"
                                            x-data
                                            :checked="{{ $selectedCount }} === {{ count($creditNoteItems) }}"
                                            @change="$event.target.checked ? $wire.selectAllCreditNoteItems() : $wire.deselectAllCreditNoteItems()"
                                            class="rounded border-gray-300 text-orange-500 focus:ring-orange-400 cursor-pointer">
                                    </th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase">Código</th>
                                    <th class="px-3 py-2.5 text-left text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase">Producto</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase">Cantidad</th>
                                    <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase">Precio Unit.</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase">%</th>
                                    <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase">Subtotal</th>
                                    <th class="px-3 py-2.5 text-right text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase">Total</th>
                                    <th class="px-3 py-2.5 text-center text-xs font-semibold text-gray-600 dark:text-slate-400 uppercase">Tipo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($creditNoteItems as $index => $item)
                                    @php
                                        $qty      = (int)($item['quantity'] ?? 1);
                                        $price    = (float)($item['price'] ?? 0);
                                        $taxRate  = (float)($item['tax_rate'] ?? 19);
                                        $priceBase = $taxRate > 0 ? ($price / (1 + $taxRate / 100)) : $price;
                                        $subtotal = round($priceBase * $qty, 0);
                                        $total    = round($price * $qty, 0);
                                    @endphp
                                    <tr class="border-b border-gray-100 dark:border-slate-700 hover:bg-orange-50/30 dark:hover:bg-orange-900/10 transition-colors {{ !$item['selected'] ? 'opacity-50' : '' }}">
                                        <td class="px-3 py-2.5 text-center">
                                            <input type="checkbox"
                                                wire:model.live="creditNoteItems.{{ $index }}.selected"
                                                {{ $correctionConceptCode === '2' ? 'disabled' : '' }}
                                                class="rounded border-gray-300 text-orange-500 focus:ring-orange-400 {{ $correctionConceptCode === '2' ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                                        </td>
                                        <td class="px-3 py-2.5">
                                            <span class="font-mono text-xs text-gray-600 dark:text-slate-400">{{ $item['code_reference'] }}</span>
                                        </td>
                                        <td class="px-3 py-2.5 max-w-[160px]">
                                            <span class="text-sm text-gray-800 dark:text-slate-200 line-clamp-2">{{ $item['name'] }}</span>
                                        </td>
                                        <td class="px-3 py-2.5 text-center">
                                            <input type="number"
                                                wire:model.live="creditNoteItems.{{ $index }}.quantity"
                                                min="0" max="{{ $item['max_quantity'] }}"
                                                {{ !$item['selected'] || $correctionConceptCode === '2' ? 'disabled' : '' }}
                                                class="w-16 text-center border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200 text-sm px-2 py-1 focus:outline-none focus:ring-1 focus:ring-orange-400 disabled:opacity-50 disabled:cursor-not-allowed">
                                        </td>
                                        <td class="px-3 py-2.5 text-right text-sm text-gray-700 dark:text-slate-300">
                                            ${{ number_format($priceBase, 0, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-2.5 text-center text-sm text-gray-600 dark:text-slate-400">
                                            {{ (int)$taxRate }}%
                                        </td>
                                        <td class="px-3 py-2.5 text-right text-sm text-gray-700 dark:text-slate-300">
                                            ${{ number_format($subtotal, 0, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-2.5 text-right text-sm font-semibold text-gray-900 dark:text-white">
                                            ${{ number_format($total, 0, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-2.5 text-center">
                                            <span class="px-1.5 py-0.5 text-xs bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300 rounded-full font-medium">
                                                PRINCIPAL
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Total Seleccionado --}}
                    <div class="mt-3 flex justify-end">
                        <span class="text-sm font-semibold text-gray-700 dark:text-slate-300">
                            Total Seleccionado:
                            <span class="text-base font-bold text-gray-900 dark:text-white ml-1">
                                $ {{ number_format($creditNoteTotal, 0, ',', '.') }}
                            </span>
                        </span>
                    </div>
                </div>

                {{-- Valor de la Nota Crédito --}}
                <div>
                    <label class="block text-sm font-semibold text-gray-700 dark:text-slate-300 mb-2">Valor de la Nota Crédito</label>
                    <input type="text" readonly
                        value="{{ number_format($creditNoteTotal, 2, ',', '.') }}"
                        class="w-full border border-gray-200 dark:border-slate-600 rounded-lg bg-gray-50 dark:bg-slate-700/50 text-gray-900 dark:text-slate-200 text-sm px-3 py-2.5 cursor-not-allowed">
                    <p class="text-xs text-gray-400 mt-1">El valor se calcula automáticamente basado en los ítems seleccionados</p>
                </div>

            </div>

            {{-- Footer --}}
            <div class="shrink-0 flex items-center justify-end gap-2 sm:gap-3 px-4 sm:px-6 py-4 border-t border-gray-200 dark:border-slate-700 bg-gray-50 dark:bg-slate-700/50">
                <button wire:click="closeCreditNoteModal" type="button"
                    class="px-5 py-2.5 text-sm font-medium text-gray-700 dark:text-slate-300 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-600 transition-colors">
                    Cancelar
                </button>
                <button wire:click="submitCreditNote" type="button"
                    wire:loading.attr="disabled" wire:target="submitCreditNote"
                    {{ $creditNoteTotal <= 0 ? 'disabled' : '' }}
                    class="px-5 py-2.5 text-sm font-medium text-white bg-orange-600 hover:bg-orange-700 disabled:opacity-50 disabled:cursor-not-allowed rounded-lg transition-colors flex items-center gap-2 shadow-sm">
                    <svg wire:loading.remove wire:target="submitCreditNote" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l-4-4m0 0l4-4m-4 4h11a4 4 0 010 8H7"/>
                    </svg>
                    <svg wire:loading wire:target="submitCreditNote" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                    Crear Nota Crédito
                </button>
            </div>
        </div>
    </div>
    </template>
    </div>
    @endif

    {{-- Toast listeners (heredados del sistema global) --}}
    @push('scripts')
    <script>
        document.addEventListener('livewire:initialized', () => {
            window.addEventListener('open-invoice-pdf', (event) => {
                const data = event.detail;
                const payload = Array.isArray(data) ? data[0] : data;
                if (payload.url) {
                    window.open(payload.url, '_blank');
                }
            });
        });
    </script>
    @endpush
</div>
