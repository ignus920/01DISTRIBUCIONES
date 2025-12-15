<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalle de Cargue #{{ $delivery->id }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        // Detectar preferencia del sistema o localStorage
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
            }

            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
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
        <!-- Header -->
        <div class="text-center mb-8 pb-6 border-b-2 border-gray-300 dark:border-gray-700">
            <h1 class="text-3xl font-bold mb-2">Detalle de Cargue</h1>
            <p class="text-gray-600 dark:text-gray-400">Reporte de Items por Categoría</p>
        </div>

        <!-- Info Section -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8 bg-gray-50 dark:bg-gray-800 p-4 rounded-lg">
            <div class="text-sm">
                <span class="font-semibold text-gray-700 dark:text-gray-300">Cargue:</span>
                <span class="ml-2 text-gray-900 dark:text-gray-100">#{{ $delivery->id }}</span>
            </div>
            <div class="text-sm">
                <span class="font-semibold text-gray-700 dark:text-gray-300">Fecha:</span>
                <span class="ml-2 text-gray-900 dark:text-gray-100">{{ \Carbon\Carbon::parse($delivery->sale_date)->format('d/m/Y') }}</span>
            </div>
            <div class="text-sm">
                <span class="font-semibold text-gray-700 dark:text-gray-300">Fecha de Impresión:</span>
                <span class="ml-2 text-gray-900 dark:text-gray-100">{{ \Carbon\Carbon::now()->format('d/m/Y H:i') }}</span>
            </div>
        </div>

        <!-- Table -->
        @if(count($items) > 0)
            <div class="overflow-x-auto shadow-lg rounded-lg mb-8">
                <table class="min-w-full bg-white dark:bg-gray-800">
                    <thead class="bg-gray-100 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b-2 border-gray-300 dark:border-gray-600">
                                Código
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b-2 border-gray-300 dark:border-gray-600">
                                Categoría
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b-2 border-gray-300 dark:border-gray-600">
                                Item
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b-2 border-gray-300 dark:border-gray-600">
                                Cantidad
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider border-b-2 border-gray-300 dark:border-gray-600">
                                Subtotal
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @php
                            $currentCategory = null;
                        @endphp
                        @foreach($items as $item)
                            @if($currentCategory !== $item->category)
                                @php
                                    $currentCategory = $item->category;
                                @endphp
                                <tr class="bg-gray-200 dark:bg-gray-700">
                                    <td colspan="5" class="px-6 py-3 font-bold text-gray-900 dark:text-gray-100 uppercase">
                                        {{ $item->category }}
                                    </td>
                                </tr>
                            @endif
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-750 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                    {{ $item->code }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    {{ $item->category }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $item->name_item }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900 dark:text-gray-100">
                                    {{ number_format($item->quantity, 0) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium text-gray-900 dark:text-gray-100">
                                    $ {{ number_format($item->subtotal, 2) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-gray-100 dark:bg-gray-700 border-t-2 border-gray-300 dark:border-gray-600">
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-right text-sm font-bold text-gray-900 dark:text-gray-100">
                                TOTAL:
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900 dark:text-gray-100">
                                $ {{ number_format($total, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        @else
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 dark:border-yellow-500 p-6 rounded">
                <p class="text-center text-yellow-700 dark:text-yellow-400">
                    No se encontraron items para esta entrega.
                </p>
            </div>
        @endif

        <!-- Footer -->
        <div class="text-center text-xs text-gray-500 dark:text-gray-400 mt-8">
            <p>Documento generado automáticamente - {{ config('app.name') }}</p>
        </div>
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
