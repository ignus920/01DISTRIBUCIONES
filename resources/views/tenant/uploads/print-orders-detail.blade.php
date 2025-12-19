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
                margin: 0.3cm;
                size: letter landscape;
            }

            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
                margin: 0;
                padding: 0;
            }

            .page-break {
                page-break-after: always;
            }

            .container {
                max-width: 100% !important;
                width: 100% !important;
                padding: 0.3cm !important;
                margin: 0 !important;
            }

            .orders-grid {
                display: grid !important;
                grid-template-columns: 1fr 1fr !important;
                gap: 0.5cm !important;
                width: 100% !important;
            }

            .order-card {
                break-inside: avoid;
                page-break-inside: avoid;
            }
        }

        .orders-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .order-card {
            break-inside: avoid;
            page-break-inside: avoid;
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

    <div class="container mx-auto p-4">
        @if(count($customerOrders) > 0)
        <!-- Grid de Pedidos en 2 Columnas -->
        <div class="orders-grid">
            @foreach($customerOrders as $order)
            <div class="order-card border-2 border-gray-300 dark:border-gray-700 rounded-lg p-3 bg-white dark:bg-gray-800">
                <!-- Header del Pedido -->
                <div class="text-center mb-3 pb-2 border-b border-gray-300 dark:border-gray-700">
                    <h2 class="text-sm font-bold mb-1">PEDIDO #{{ $loop->iteration }}</h2>
                </div>

                <!-- Customer Info -->
                <div class="mb-3 bg-gray-50 dark:bg-gray-900 p-2 rounded text-xs">
                    <div class="flex justify-between">
                        <div class="flex-1">
                            <p class="mb-1"><span class="font-semibold">Cliente:</span> {{ $order['customer']['name'] }}</p>
                            <p class="mb-1"><span class="font-semibold">ID:</span> {{ $order['customer']['identification'] }}</p>
                            <p class="mb-1"><span class="font-semibold">Dirección:</span> {{ $order['customer']['address'] }}</p>
                            <p class="mb-1"><span class="font-semibold">Barrio:</span> {{ $order['customer']['district'] }}</p>
                        </div>
                        <div class="flex-1 text-right">
                            <p class="mb-1"><span class="font-semibold">Teléfono:</span> {{ $order['customer']['phone'] }}</p>
                            <p class="mb-1"><span class="font-semibold">Vendedor:</span> {{ $order['customer']['salesPerson'] ?? 'N/A' }}</p>
                            <p><span class="font-semibold">Día de Venta:</span> {{ $order['customer']['saleDay'] }}</p>
                        </div>
                    </div>
                </div>

                <!-- Items -->
                <div class="mb-3">
                    <h3 class="text-xs font-bold mb-2 text-gray-800 dark:text-gray-200">ITEMS</h3>
                    <div class="space-y-1">
                        @php
                        $currentCategory = null;
                        @endphp
                        @foreach($order['items'] as $item)
                        @if($currentCategory !== $item['category'])
                        @php
                        $currentCategory = $item['category'];
                        @endphp
                        <div class="bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">
                            <p class="font-bold text-gray-900 dark:text-gray-100 text-xs">{{ $item['category'] }}</p>
                        </div>
                        @endif
                        <div class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded p-2 text-xs">
                            <div class="flex justify-between mb-1">
                                <span class="text-gray-600 dark:text-gray-400">{{ $item['code'] }}</span>
                                <span class="font-bold">Cant: {{ number_format($item['quantity'], 0) }}</span>
                            </div>
                            <p class="font-medium text-gray-900 dark:text-gray-100 mb-1">{{ $item['name'] }}</p>
                            <p class="text-right font-bold text-blue-600 dark:text-blue-400">$ {{ number_format($item['subtotal'], 2) }}</p>
                        </div>
                        @endforeach
                    </div>
                </div>

                <!-- Totals -->
                <div class="bg-gray-50 dark:bg-gray-900 p-2 rounded text-xs">
                    <p class="mb-2 font-semibold text-gray-700 dark:text-gray-300">{{ $order['totalInWords'] }}</p>
                    <div class="border-t border-gray-300 dark:border-gray-600 pt-2 space-y-1">
                        <div class="flex justify-between">
                            <span class="font-semibold">Subtotal:</span>
                            <span class="font-bold">$ {{ number_format($order['subtotal'], 2) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="font-semibold">IVA:</span>
                            <span class="font-bold">$ {{ number_format($order['iva'], 2) }}</span>
                        </div>
                        <div class="flex justify-between border-t border-gray-300 dark:border-gray-600 pt-1">
                            <span class="font-bold">TOTAL:</span>
                            <span class="font-bold text-blue-600 dark:text-blue-400">$ {{ number_format($order['total'], 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if($loop->iteration % 2 == 0 && !$loop->last)
            <div class="page-break col-span-2"></div>
            @endif
            @endforeach
        </div>
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