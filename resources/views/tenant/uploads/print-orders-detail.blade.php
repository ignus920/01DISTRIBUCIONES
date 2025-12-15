<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos Cargue #{{ $delivery->id }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark')
        } else {
            document.documentElement.classList.remove('dark')
        }
    </script>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            
            @page {
                margin: 1cm;
                size: letter;
            }

            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }

            .page-break {
                page-break-after: always;
            }
        }
    </style>
</head>
<body class="bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <!-- Botones de control -->
    <div class="no-print fixed top-4 right-4 flex gap-2 z-50">
        <button onclick="toggleDarkMode()" 
                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg shadow-lg transition font-medium">
            <span class="dark:hidden">🌙 Modo Oscuro</span>
            <span class="hidden dark:inline">☀️ Modo Claro</span>
        </button>
        <button onclick="window.print()" 
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white rounded-lg shadow-lg transition font-medium">
            🖨️ Imprimir
        </button>
    </div>

    <div class="container mx-auto p-8 max-w-6xl">
        @if(count($customerOrders) > 0)
            @foreach($customerOrders as $index => $order)
                <div class="{{ $index < count($customerOrders) - 1 ? 'page-break' : '' }}">
                    <!-- Header -->
                    <div class="text-center mb-6 pb-4 border-b-2 border-gray-300 dark:border-gray-700">
                        <h1 class="text-2xl font-bold mb-2">PEDIDO DE CARGUE</h1>
                        <p class="text-gray-600 dark:text-gray-400">Cargue #{{ $delivery->id }} - Remisión #{{ $order['customer']['remission_id'] }}</p>
                    </div>

                    <!-- Customer Info -->
                    <div class="grid grid-cols-2 gap-4 mb-6 bg-gray-50 dark:bg-gray-800 p-4 rounded-lg text-sm">
                        <div>
                            <p><span class="font-semibold">Cliente:</span> {{ $order['customer']['name'] }}</p>
                            <p><span class="font-semibold">Identificación:</span> {{ $order['customer']['identification'] }}</p>
                            <p><span class="font-semibold">Dirección:</span> {{ $order['customer']['address'] }}</p>
                        </div>
                        <div>
                            <p><span class="font-semibold">Barrio:</span> {{ $order['customer']['district'] }}</p>
                            <p><span class="font-semibold">Teléfono:</span> {{ $order['customer']['phone'] }}</p>
                            <p><span class="font-semibold">Vendedor:</span> {{ $order['customer']['salesPerson'] ?? 'N/A' }}</p>
                            <p><span class="font-semibold">Día de Venta:</span> {{ $order['customer']['saleDay'] }}</p>
                        </div>
                    </div>

                    <!-- Items Table -->
                    <div class="mb-6">
                        <h3 class="text-lg font-bold mb-3 text-gray-800 dark:text-gray-200">OBSERVACIONES</h3>
                        <div class="overflow-x-auto shadow-lg rounded-lg">
                            <table class="min-w-full bg-white dark:bg-gray-800 text-sm">
                                <thead class="bg-gray-100 dark:bg-gray-700">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase border-b-2 border-gray-300 dark:border-gray-600">
                                            Código
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase border-b-2 border-gray-300 dark:border-gray-600">
                                            Categoría
                                        </th>
                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase border-b-2 border-gray-300 dark:border-gray-600">
                                            Item
                                        </th>
                                        <th class="px-4 py-2 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase border-b-2 border-gray-300 dark:border-gray-600">
                                            Cantidad
                                        </th>
                                        <th class="px-4 py-2 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase border-b-2 border-gray-300 dark:border-gray-600">
                                            Subtotal
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                    @php
                                        $currentCategory = null;
                                    @endphp
                                    @foreach($order['items'] as $item)
                                        @if($currentCategory !== $item['category'])
                                            @php
                                                $currentCategory = $item['category'];
                                            @endphp
                                            <tr class="bg-gray-200 dark:bg-gray-700">
                                                <td colspan="5" class="px-4 py-2 font-bold text-gray-900 dark:text-gray-100 uppercase text-xs">
                                                    {{ $item['category'] }}
                                                </td>
                                            </tr>
                                        @endif
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-750">
                                            <td class="px-4 py-2 text-gray-900 dark:text-gray-100">
                                                {{ $item['code'] }}
                                            </td>
                                            <td class="px-4 py-2 text-gray-700 dark:text-gray-300">
                                                {{ $item['category'] }}
                                            </td>
                                            <td class="px-4 py-2 text-gray-900 dark:text-gray-100">
                                                {{ $item['name'] }}
                                            </td>
                                            <td class="px-4 py-2 text-right text-gray-900 dark:text-gray-100">
                                                {{ number_format($item['quantity'], 0) }}
                                            </td>
                                            <td class="px-4 py-2 text-right font-medium text-gray-900 dark:text-gray-100">
                                                $ {{ number_format($item['subtotal'], 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Totals Section -->
                    <div class="bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
                        <div class="mb-3">
                            <p class="text-sm font-semibold text-gray-700 dark:text-gray-300">VALOR EN LETRAS:</p>
                            <p class="text-base font-bold text-gray-900 dark:text-gray-100">{{ $order['totalInWords'] }}</p>
                        </div>
                        
                        <div class="border-t-2 border-gray-300 dark:border-gray-600 pt-3">
                            <h4 class="text-sm font-bold mb-2 text-gray-800 dark:text-gray-200">TOTAL PÁGINA</h4>
                            <div class="grid grid-cols-4 gap-4 text-sm">
                                <div class="text-center">
                                    <p class="font-semibold text-gray-700 dark:text-gray-300">Subtotal Pedido</p>
                                    <p class="text-lg font-bold text-gray-900 dark:text-gray-100">$ {{ number_format($order['subtotal'], 2) }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="font-semibold text-gray-700 dark:text-gray-300">IVA</p>
                                    <p class="text-lg font-bold text-gray-900 dark:text-gray-100">$ {{ number_format($order['iva'], 2) }}</p>
                                </div>
                                <div class="text-center">
                                    <p class="font-semibold text-gray-700 dark:text-gray-300">TOTAL A PAGAR</p>
                                    <p class="text-lg font-bold text-blue-600 dark:text-blue-400">$ {{ number_format($order['total'], 2) }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="text-center text-xs text-gray-500 dark:text-gray-400 mt-6">
                        <p>Fecha de Impresión: {{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</p>
                        <p>{{ config('app.name') }}</p>
                    </div>
                </div>
            @endforeach
        @else
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 dark:border-yellow-500 p-6 rounded">
                <p class="text-center text-yellow-700 dark:text-yellow-400">
                    No se encontraron pedidos para esta entrega.
                </p>
            </div>
        @endif
    </div>

    <script>
        function toggleDarkMode() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                document.documentElement.classList.add('dark');
                localStorage.theme = 'dark';
            }
        }
    </script>
</body>
</html>
