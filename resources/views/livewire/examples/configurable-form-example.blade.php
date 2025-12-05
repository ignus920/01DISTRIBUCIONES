<div class="max-w-4xl mx-auto p-6 bg-white shadow-lg rounded-lg">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">Sistema de Configuración - Módulo VENTAS</h2>

    {{-- Mostrar funciones habilitadas según configuración --}}
    @if(!empty($visibleFields))
        <div class="bg-green-50 border border-green-200 rounded-md p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-green-800">
                        Funciones habilitadas en tu plan
                    </h3>
                    <div class="mt-2 text-sm text-green-700">
                        <p>Las siguientes funciones están disponibles según tu configuración:</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="space-y-4">
            @foreach($visibleFields as $field)
                <div class="form-group">
                    <div class="flex items-center p-4 bg-gray-50 rounded-lg border">
                        <input
                            type="checkbox"
                            id="{{ Str::slug($field) }}"
                            class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
                            checked
                            disabled
                        >
                        <label for="{{ Str::slug($field) }}" class="ml-3 block text-sm font-medium text-gray-900">
                            ✅ <strong>{{ $field }}</strong> - Configuración habilitada
                        </label>
                        <span class="ml-auto text-xs text-gray-500 bg-green-100 px-2 py-1 rounded">
                            Activa
                        </span>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Mostrar campos deshabilitados --}}
        @if(!empty($disabledFields))
            <div class="mt-6">
                <h4 class="text-lg font-semibold mb-3 text-gray-700">Funciones Deshabilitadas</h4>
                <div class="space-y-2">
                    @foreach($disabledFields as $field)
                        <div class="form-group">
                            <div class="flex items-center p-3 bg-red-50 rounded-lg border border-red-200">
                                <input
                                    type="checkbox"
                                    id="{{ Str::slug($field) }}_disabled"
                                    class="h-4 w-4 text-red-600 focus:ring-red-500 border-gray-300 rounded"
                                    disabled
                                >
                                <label for="{{ Str::slug($field) }}_disabled" class="ml-3 block text-sm font-medium text-gray-700">
                                    ❌ <strong>{{ $field }}</strong> - Función deshabilitada
                                </label>
                                <span class="ml-auto text-xs text-red-700 bg-red-100 px-2 py-1 rounded">
                                    Inactiva
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @else
        <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">
                        No hay funciones configuradas
                    </h3>
                    <div class="mt-2 text-sm text-yellow-700">
                        <p>No se encontraron funciones habilitadas para el módulo VENTAS en tu plan actual.</p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Información del sistema --}}
    <div class="mt-8 bg-blue-50 border border-blue-200 rounded-md p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-blue-800">
                    ¿Cómo funciona este sistema?
                </h3>
                <div class="mt-2 text-sm text-blue-700">
                    <p>Este formulario muestra solo las funcionalidades que están habilitadas para tu empresa según:</p>
                    <ul class="list-disc list-inside mt-2 space-y-1">
                        <li><strong>Tu plan contratado:</strong> Básico, Avanzado o Superior</li>
                        <li><strong>Configuración de empresa:</strong> Opciones específicas por empresa</li>
                        <li><strong>Módulo específico:</strong> En este caso, el módulo VENTAS</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- Panel de debug (solo en desarrollo) --}}
    @if(app()->environment('local'))
        <div class="mt-8 p-4 bg-gray-100 rounded-lg">
            <h3 class="text-lg font-semibold mb-2">Debug - Configuración Actual</h3>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <h4 class="font-medium">Funciones Visibles:</h4>
                    @if(!empty($visibleFields))
                        <ul class="list-disc list-inside text-sm">
                            @foreach($visibleFields as $field)
                                <li>{{ $field }}</li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-sm text-gray-600">Ninguna función visible</p>
                    @endif
                </div>
                <div>
                    <h4 class="font-medium">Configuración del Módulo:</h4>
                    <pre class="text-xs bg-white p-2 rounded border overflow-x-auto">{{ json_encode($moduleConfig, JSON_PRETTY_PRINT) }}</pre>
                </div>
            </div>
        </div>
    @endif
</div>