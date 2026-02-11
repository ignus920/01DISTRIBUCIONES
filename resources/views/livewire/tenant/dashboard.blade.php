<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-6 bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-1 xl:grid-cols-5 gap-8 items-start">

                    <!-- Columna Izquierda: Bienvenida y Filtros -->
                    <div class="lg:col-span-1 flex flex-col gap-4">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Bienvenido, {{ $user->name }}</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Resumen de operaciones y estadísticas</p>
                        </div>

                        <!-- Filtros de Fecha (Solo Admin) -->
                        @if(auth()->user()->profile_id != 4)
                        <div class="flex flex-col gap-2 bg-gray-50 dark:bg-gray-800 p-3 rounded-xl border border-gray-200 dark:border-gray-700 w-full">
                            <span class="text-[10px] font-bold uppercase text-gray-400">Rango de Datos</span>
                            <div class="flex items-center gap-2 justify-between">
                                <div class="flex flex-col">
                                    <span class="text-[9px] font-bold uppercase text-gray-400">Desde</span>
                                    <input type="date" wire:model.live="startDate" 
                                        class="bg-transparent border-none text-xs font-semibold text-gray-700 dark:text-gray-200 focus:ring-0 p-0 w-24">
                                </div>
                                <div class="h-8 w-[1px] bg-gray-300 dark:bg-gray-600 mx-1"></div>
                                <div class="flex flex-col">
                                    <span class="text-[9px] font-bold uppercase text-gray-400">Hasta</span>
                                    <input type="date" wire:model.live="endDate" 
                                        class="bg-transparent border-none text-xs font-semibold text-gray-700 dark:text-gray-200 focus:ring-0 p-0 w-24">
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Columna Derecha: Accesos Rápidos (Ampliado) -->
                    <div class="lg:col-span-3">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            @foreach($enabledFeatures as $key => $feature)
                            <a href="{{ $feature['url'] }}" 
                                class="group flex flex-col items-center justify-center p-4 border border-gray-200 dark:border-gray-700 rounded-xl bg-white dark:bg-gray-800 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 hover:border-indigo-300 dark:hover:border-indigo-500 hover:shadow-lg transition-all duration-300 ease-in-out h-28 w-full" 
                                wire:navigate.hover>
                                <div class="p-3 rounded-lg mb-2 
                                    {{ $key === 'ventas' ? 'bg-indigo-100 text-indigo-600' : '' }}
                                    {{ $key === 'clientes' ? 'bg-green-100 text-green-600' : '' }}
                                    {{ $key === 'productos' ? 'bg-yellow-100 text-yellow-600' : '' }}
                                    {{ $key === 'caja' ? 'bg-purple-100 text-purple-600' : '' }}
                                    group-hover:scale-110 transition-transform duration-300 shadow-sm">
                                    
                                    @if($key === 'ventas')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    @elseif($key === 'clientes')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                                    </svg>
                                    @elseif($key === 'productos')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                    @elseif($key === 'caja')
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                    </svg>
                                    @endif
                                </div>
                                <span class="text-xs font-bold text-gray-700 dark:text-gray-300 group-hover:text-indigo-600 transition-colors uppercase tracking-wide">{{ $feature['name'] }}</span>
                            </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Ventas Hoy -->
            <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-indigo-100 rounded-md p-3">
                            <svg class="h-6 w-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-5">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">Ventas Hoy</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">${{ number_format($stats['total_ventas_hoy'], 0) }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Clientes -->
            <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-100 rounded-md p-3">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-5">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">Total Clientes</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['total_clientes'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Total Productos -->
            <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-yellow-100 rounded-md p-3">
                            <svg class="h-6 w-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                        <div class="ml-5">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">Total Productos</p>
                            <p class="text-2xl font-semibold text-gray-900 dark:text-white">{{ $stats['total_productos'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ventas en Rango -->
            <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg border-b-4 border-purple-500 transition-all hover:shadow-md">
                <div class="p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-purple-100 dark:bg-purple-900/30 rounded-xl p-3">
                            <svg class="h-6 w-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div class="ml-5">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Ventas en Rango</p>
                            <p class="text-2xl font-black text-gray-900 dark:text-white">${{ number_format($stats['ventas_rango'], 0) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos de Ventas -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Gráfico Diario -->
            <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 dark:border-gray-800"
                x-data="dailyChart" x-on:update-charts.window="update($event.detail.daily)">
                <div class="p-6 border-b border-gray-50 dark:border-gray-800 flex items-center justify-between h-20">
                    <div class="flex items-center gap-3" x-show="!selectedLabel">
                        <div class="p-2 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg">
                            <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">Ventas por Día</h3>
                    </div>
                    <div class="flex flex-col animate-fadeIn" x-show="selectedLabel" style="display: none;">
                        <span class="text-xs font-bold text-indigo-500 uppercase tracking-widest" x-text="selectedLabel"></span>
                        <span class="text-2xl font-black text-gray-900 dark:text-white" x-text="formatMoney(selectedValue)"></span>
                    </div>
                </div>
                <div class="p-6 h-80 relative cursor-pointer group">
                    <canvas id="dailySalesChart"></canvas>
                </div>
            </div>

            <!-- Gráfico Mensual -->
            <div class="bg-white dark:bg-gray-900 overflow-hidden shadow-sm sm:rounded-2xl border border-gray-100 dark:border-gray-800"
                x-data="monthlyChart" x-on:update-charts.window="update($event.detail.monthly)">
                <div class="p-6 border-b border-gray-50 dark:border-gray-800 flex items-center justify-between h-20">
                    <div class="flex items-center gap-3" x-show="!selectedLabel">
                        <div class="p-2 bg-purple-50 dark:bg-purple-900/20 rounded-lg">
                            <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"></path>
                            </svg>
                        </div>
                        <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase tracking-wider">Rendimiento Mensual</h3>
                    </div>
                    <div class="flex flex-col animate-fadeIn" x-show="selectedLabel" style="display: none;">
                        <span class="text-xs font-bold text-purple-500 uppercase tracking-widest" x-text="selectedLabel"></span>
                        <span class="text-2xl font-black text-gray-900 dark:text-white" x-text="formatMoney(selectedValue)"></span>
                    </div>
                </div>
                <div class="p-6 h-80 relative cursor-pointer group">
                    <canvas id="monthlySalesChart"></canvas>
                </div>
            </div>
        </div>


        <!-- Información adicional -->
        <div class="mt-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        Este es el panel de control de <strong>{{ $user->name }}</strong>.
                        Aquí podrá gestionar todas las operaciones de su empresa.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('alpine:init', () => {
        // Formateador global
        const moneyFormatter = new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 });

        // Objeto base para estilos premium
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                axis: 'x',
                intersect: false
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#1f2937',
                    titleFont: { size: 13, weight: 'bold' },
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) label += ': ';
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { size: 10, weight: '600' }, color: '#9ca3af' }
                },
                y: {
                    beginAtZero: true,
                    grid: { color: '#f3f4f6', borderDash: [5, 5] },
                    ticks: { 
                        font: { size: 10, weight: '600' }, 
                        color: '#9ca3af',
                        callback: function(value) {
                            if (value >= 1000000) return '$' + (value / 1000000).toFixed(1) + 'M';
                            if (value >= 1000) return '$' + (value / 1000).toFixed(0) + 'k';
                            return '$' + value;
                        }
                    }
                }
            }
        };

        Alpine.data('dailyChart', () => {
            let chartInstance = null;

            return {
                selectedLabel: null,
                selectedValue: null,
                moneyFormatter: new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }),
                formatMoney(value) {
                    return this.moneyFormatter.format(value);
                },
                init() {
                    const ctx = document.getElementById('dailySalesChart').getContext('2d');
                    const gradient = ctx.createLinearGradient(0, 0, 0, 300);
                    gradient.addColorStop(0, 'rgba(79, 70, 229, 0.4)');
                    gradient.addColorStop(1, 'rgba(79, 70, 229, 0)');
                    const self = this;

                    chartInstance = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: @json($chartDailyData['labels'] ?? []),
                            datasets: [{
                                label: 'Ventas',
                                data: @json($chartDailyData['values'] ?? []),
                                borderColor: '#4f46e5',
                                borderWidth: 3,
                                fill: true,
                                backgroundColor: gradient,
                                tension: 0.4,
                                pointRadius: 4,
                                pointBackgroundColor: '#fff',
                                pointBorderColor: '#4f46e5',
                                pointBorderWidth: 2,
                                pointHoverRadius: 10,
                                pointHoverBorderWidth: 4,
                                pointHoverBackgroundColor: '#ffffff',
                                pointHoverBorderColor: '#4f46e5',
                                pointHitRadius: 50,
                                hoverRadius: 10,
                                hoverBorderWidth: 4
                            }]
                        },
                        options: {
                            ...commonOptions,
                            onHover: (e, elements, chart) => {
                                if (elements && elements.length > 0) {
                                    const index = elements[0].index;
                                    self.selectedLabel = chart.data.labels[index];
                                    self.selectedValue = chart.data.datasets[0].data[index];
                                } else {
                                    // Opcional: limpiar al salir
                                    // self.selectedLabel = null;
                                }
                            }
                        }
                    });
                },
                update(data) {
                    if (!chartInstance) return;
                    chartInstance.data.labels = data.labels;
                    chartInstance.data.datasets[0].data = data.values;
                    chartInstance.update();
                }
            };
        });

        Alpine.data('monthlyChart', () => {
            let chartInstance = null;

            return {
                selectedLabel: null,
                selectedValue: null,
                moneyFormatter: new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }),
                formatMoney(value) {
                    return this.moneyFormatter.format(value);
                },
                init() {
                    const ctx = document.getElementById('monthlySalesChart').getContext('2d');
                    const self = this;
                    chartInstance = new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: @json($chartMonthlyData['labels'] ?? []),
                            datasets: [{
                                label: 'Ventas',
                                data: @json($chartMonthlyData['values'] ?? []),
                                backgroundColor: '#8b5cf6',
                                borderRadius: 8,
                                hoverBackgroundColor: '#7c3aed'
                            }]
                        },
                        options: {
                            ...commonOptions,
                            scales: {
                                ...commonOptions.scales,
                                x: { ...commonOptions.scales.x, grid: { display: false } }
                            },
                            onHover: (e, elements, chart) => {
                                if (elements && elements.length > 0) {
                                    const index = elements[0].index;
                                    self.selectedLabel = chart.data.labels[index];
                                    self.selectedValue = chart.data.datasets[0].data[index];
                                }
                            }
                        }
                    });
                },
                update(data) {
                    if (!chartInstance) return;
                    chartInstance.data.labels = data.labels;
                    chartInstance.data.datasets[0].data = data.values;
                    chartInstance.update();
                }
            };
        });
    });
</script>
@endpush
