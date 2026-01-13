<div class="p-4 sm:p-6 dark:bg-slate-900 min-h-screen">
    <div class="max-w-7xl mx-auto">
        
        <!-- Header & Instructions -->
        <div class="mb-8">
            <h1 class="text-2xl sm:text-3xl font-bold text-gray-900 dark:text-white mb-2">
                Recepción de Pedidos {{ $order_number ? "- Orden #$order_number" : '' }}
            </h1>
            <p class="text-gray-600 dark:text-slate-400">
                Verifica y ajusta las cantidades recibidas antes de confirmar el ingreso al inventario.
            </p>
        </div>

        <!-- Toolbar / Search -->
        <div class="flex flex-col sm:flex-row justify-between items-center mb-6 gap-4">
            <div class="w-full sm:w-1/2 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" class="w-6 h-6" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input 
                    type="text" 
                    wire:model.live.debounce.300ms="search"
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-slate-700 rounded-lg leading-5 bg-white dark:bg-slate-800 dark:text-gray-300 placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm shadow-sm transition duration-150 ease-in-out" 
                    placeholder="Buscar por nombre, SKU o número de orden..."
                >
            </div>
            
            <div class="flex items-center gap-2">
                 <!-- Stats or Filters could go here -->
                 <span class="text-sm font-medium text-gray-500 dark:text-slate-400 bg-gray-100 dark:bg-slate-800 px-3 py-1 rounded-full">
                    {{ count($items) }} Ítems Pendientes
                 </span>
            </div>
        </div>

        <!-- Main Table Card -->
        <div class="bg-white dark:bg-slate-800 shadow rounded-lg overflow-hidden border border-gray-200 dark:border-slate-700">
            
            @if(count($items) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-700/50">
                            <tr class="border-b border-gray-200 dark:border-slate-700">
                                <th scope="col" class="px-6 py-3 text-center">
                                    @if($items->where('status', '!==', 'Recibido')->count() > 0)
                                        <input type="checkbox" wire:model.live="selectAll" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50">
                                    @else
                                        <span class="text-xs text-gray-500 font-medium">#</span>
                                    @endif
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase tracking-wider">
                                    Producto
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-300 uppercase tracking-wider">
                                    Referencia
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-blue-600 dark:text-blue-400 uppercase tracking-wider">
                                    Solicitado
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-indigo-600 dark:text-indigo-400 uppercase tracking-wider w-32">
                                    Recibido
                                </th>
                                <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-slate-300 uppercase tracking-wider">
                                    Diferencia
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                            @foreach($items as $item)
                                @php
                                    $req = $item->quantity_request;
                                    $rec = $quantities[$item->id] ?? $req; // Default to request if not set (though logic sets it)
                                    $recInt = intval($rec);
                                    $diff = $recInt - $req;
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50 transition-colors {{ $item->status === 'Recibido' ? 'bg-gray-50 dark:bg-slate-800/80' : '' }}">
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @if($item->status !== 'Recibido')
                                             <input 
                                                 type="checkbox" 
                                                 value="{{ $item->id }}" 
                                                 wire:model.live="selected" 
                                                 class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 disabled:opacity-50 disabled:bg-gray-200 dark:disabled:bg-slate-700 disabled:cursor-not-allowed"
                                                 @if(intval($quantities[$item->id] ?? 0) <= 0) disabled @endif
                                             >
                                        @else
                                            <span class="text-green-500">
                                                <svg class="w-5 h-5 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="ml-0">
                                                <div class="text-sm font-bold text-gray-900 dark:text-white uppercase">
                                                   {{$item->it_name_dis}}
                                                </div>
                                                <div class="text-xs text-gray-500 dark:text-slate-400 font-mono">
                                                    {{ $item->it_sku_dis }}
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-xs text-gray-500 dark:text-slate-400">
                                             Rem: # {{ $item->remise_number ?: 'Sin Remisión' }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-slate-400">
                                            Ord: #{{ $item->order_number }}
                                        </div>
                                        <div class="text-xs text-gray-400 dark:text-slate-500 mt-0.5">
                                            {{ \Carbon\Carbon::parse($item->created_at)->format('d/m/Y') }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                         <span class="inline-flex items-center justify-center px-2.5 py-0.5 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300 min-w-[3rem]">
                                            {{ number_format($req, 0) }}
                                         </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <!-- Input for Quantity -->
                                        <input 
                                            type="number" 
                                            wire:model.blur="quantities.{{ $item->id }}"
                                            class="w-20 sm:w-24 text-center border-gray-300 dark:border-slate-600 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm bg-white dark:bg-slate-900 dark:text-white font-bold disabled:opacity-60 disabled:bg-gray-100 dark:disabled:bg-slate-800"
                                            min="0"
                                            @if($item->status === 'Recibido') disabled @endif
                                        >
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        @if($diff == 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                                                Exacto
                                            </span>
                                        @elseif($diff > 0)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-300">
                                                +{{ $diff }} (Exceso)
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                                                {{ $diff }} (Faltante)
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <!-- Empty State -->
                <div class="text-center py-12 px-4">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white">No hay pedidos pendientes</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Todo está al día. ¡Buen trabajo!</p>
                </div>
            @endif
            
            <!-- Footer with Actions -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-slate-700/50 border-t border-gray-200 dark:border-slate-700 flex justify-between items-center gap-4">
                <a href="{{ route('tenant.tat.restock.list') }}" class="inline-flex items-center px-3 py-1.5 rounded-md text-sm font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/30 hover:text-red-700 dark:hover:text-red-300 border border-red-200 dark:border-red-800 transition-colors">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    {{ count($items) > 0 ? 'Cancelar y Volver' : 'Volver a la lista' }}
                </a>

                    @if(count($selected) > 0)
                    <div class="flex items-center gap-4 animate-fade-in">
                        <span class="text-sm font-medium text-gray-600 dark:text-slate-400">
                            {{ count($selected) }} items seleccionados
                        </span>
                            <button
                            wire:click="confirmSelected"
                            wire:confirm="¿Confirmar {{ count($selected) }} items seleccionados?"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors"
                        >
                            Confirmar Seleccionados
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Toast Scripts if needed (though usually in layout) -->
    <script>
        document.addEventListener('livewire:initialized', () => {
             Livewire.on('show-toast', (data) => {
                const payload = Array.isArray(data) ? data[0] : data;
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                Toast.fire({
                    icon: payload.type || 'info',
                    title: payload.message
                });
            });
            
             Livewire.on('item-marked-received', () => {
                 // Refresh handled by Livewire re-render
                 // Optional sound or confetti
            });
        });
    </script>
</div>


