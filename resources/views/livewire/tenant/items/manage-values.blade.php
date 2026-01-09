<div class="fixed inset-0 bg-gray-600 dark:bg-gray-900 bg-opacity-50 dark:bg-opacity-75 overflow-y-auto h-full w-full z-50"
    x-data="{ show: true }" x-show="show" x-transition:enter="ease-out duration-200"
    x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
    x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
    @keydown.escape.window="$wire.closeModal()">
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto"
            x-transition:enter="ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

            <!-- Header -->
            <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Gestión de Valores - {{ $itemName }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Valores asociados al item
                    </p>
                </div>
                <button wire:click="$dispatch('closeValuesModal')"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <x-heroicon-o-x-mark class="w-7 h-7 sm:w-6 sm:h-6" />
                </button>
            </div>

            <!-- Success Message -->
            @if($successMessage)
            <div x-data="{ show: true }"
                x-init="setTimeout(() => { show = false; $wire.set('successMessage', null) }, 3000)" x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform -translate-y-2"
                x-transition:enter-end="opacity-100 transform translate-y-0"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0 transform -translate-y-2"
                class="mx-6 mt-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                <p class="text-sm text-green-700 dark:text-green-400">{{ $successMessage }}</p>
            </div>
            @endif

            <!-- Warning Message -->
            @if($warningMessage)
            <div x-data="{ show: true }"
                x-init="setTimeout(() => { show = false; $wire.set('warningMessage', null) }, 3000)" x-show="show"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 transform -translate-y-2"
                x-transition:enter-end="opacity-100 transform translate-y-0"
                x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0 transform -translate-y-2"
                class="mx-6 mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                <p class="text-sm text-yellow-700 dark:text-yellow-400">{{ $warningMessage }}</p>
            </div>
            @endif

            <!-- Table Content -->
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Fecha
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Etiqueta
                                </th>
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Tipo
                                </th>
                                <th
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Valor
                                </th>
                                {{-- <th
                                    class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Sucursal
                                </th> --}}
                                <th
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @php
                                // Definir los 5 tipos de valores estáticos
                                $staticValues = [
                                    ['label' => 'Costo Inicial', 'type' => 'Costo'],
                                    ['label' => 'Costo', 'type' => 'Costo'],
                                    ['label' => 'Precio Base', 'type' => 'Precio'],
                                    ['label' => 'Precio Regular', 'type' => 'Precio'],
                                    ['label' => 'Precio Crédito', 'type' => 'Precio'],
                                ];
                                
                                // Obtener valores existentes indexados por label
                                $existingValuesByLabel = $values->keyBy('label');
                            @endphp

                            @foreach ($staticValues as $staticValue)
                                @php
                                    $existingValue = $existingValuesByLabel->get($staticValue['label']);
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <!-- Fecha -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        @if($existingValue)
                                            {{ $existingValue->created_at->format('d/m/Y H:i') }}
                                        @else
                                            <span class="text-gray-300 dark:text-gray-600">-</span>
                                        @endif
                                    </td>

                                    <!-- Etiqueta -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $staticValue['label'] }}<br>
                                        <span class="text-gray-500 dark:text-gray-600">(Precio sin IVA)</span>
                                    </td>

                                    <!-- Tipo -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            {{ $staticValue['type'] === 'Costo' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' }}">
                                            {{ $staticValue['type'] }}
                                        </span>
                                    </td>

                                    <!-- Valor -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium">
                                        @if($existingValue)
                                            <!-- Valor existente con opción de editar -->
                                            <div x-data="{ 
                                                editing: false, 
                                                originalValue: {{ $existingValue->values }},
                                                newValue: {{ $existingValue->values }}
                                            }">
                                                <div x-show="!editing" class="flex items-center justify-end space-x-2">
                                                    <span class="text-gray-900 dark:text-white">
                                                        ${{ number_format($existingValue->values, 2) }}
                                                    </span>
                                                    <button @click="editing = true"
                                                        class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 p-2 rounded hover:bg-blue-50 dark:hover:bg-blue-900/20">
                                                        <x-heroicon-o-pencil class="w-5 h-5 sm:w-4 sm:h-4 " stroke-width="2.5" />
                                                    </button>
                                                </div>
                                                <div x-show="editing" class="flex items-center justify-end space-x-2">
                                                    <input type="number" step="0.01" min="0" x-model="newValue" @keydown.enter="
                                                            $wire.updateValue({{ $existingValue->id }}, newValue);
                                                            originalValue = newValue;
                                                            editing = false;
                                                        " @keydown.escape="newValue = originalValue; editing = false"
                                                        class="w-32 px-2 py-1 text-right border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm">
                                                    <button @click="$wire.updateValue({{ $existingValue->id }}, newValue); originalValue = newValue; editing = false;"
                                                        class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 p-2 rounded hover:bg-green-50 dark:hover:bg-green-900/20">
                                                        <x-heroicon-o-check class="w-5 h-5 sm:w-4 sm:h-4" stroke-width="3.5" />
                                                    </button>
                                                    <button @click="newValue = originalValue; editing = false"
                                                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 p-2 rounded hover:bg-red-50 dark:hover:bg-red-900/20">
                                                        <x-heroicon-o-x-mark class="w-5 h-5 sm:w-4 sm:h-4" stroke-width="3.5" /> 
                                                    </button>
                                                </div>
                                            </div>
                                        @else
                                            <!-- Valor nuevo con input para editar -->
                                            <div x-data="{ 
                                                editing: true, 
                                                newValue: '',
                                                valueKey: '{{ $staticValue['label'] }}'
                                            }">
                                                <div x-show="!editing && newValue" class="flex items-center justify-end space-x-2">
                                                    <span class="text-gray-900 dark:text-white" x-text="'$' + parseFloat(newValue).toFixed(2)">
                                                    </span>
                                                    <button @click="editing = true"
                                                        class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 p-2 rounded hover:bg-blue-50 dark:hover:bg-blue-900/20">
                                                        <x-heroicon-o-pencil class="w-5 h-5 sm:w-4 sm:h-4" stroke-width="2.5" />
                                                    </button>
                                                </div>
                                                <div x-show="!editing && !newValue" class="text-gray-300 dark:text-gray-600">
                                                    -
                                                </div>
                                                <div x-show="editing" class="flex items-center justify-end space-x-2">
                                                    <input type="number" step="0.01" min="0" x-model="newValue" 
                                                        @wire:addNewValue="newValue = $event.detail.value"
                                                        placeholder="0.00"
                                                        class="w-32 px-2 py-1 text-right border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm">
                                                    <button @click="$wire.addNewValue(valueKey, newValue); newValue = '';"
                                                        :disabled="!newValue || newValue <= 0"
                                                        class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 p-2 rounded hover:bg-green-50 dark:hover:bg-green-900/20 disabled:opacity-50 disabled:cursor-not-allowed">
                                                        <x-heroicon-o-check class="w-5 h-5 sm:w-4 sm:h-4" stroke-width="3.5" />
                                                    </button>
                                                    <button @click="newValue = ''; editing = false;"
                                                        class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 p-2 rounded hover:bg-red-50 dark:hover:bg-red-900/20">
                                                        <x-heroicon-o-x-mark class="w-5 h-5 sm:w-4 sm:h-4" stroke-width="3.5" />
                                                    </button>
                                                </div>
                                            </div>
                                        @endif
                                    </td>

                                    <!-- Sucursal -->
                                    <td
                                        class="px-6 py-4 whitespace-nowrap text-sm text-center text-gray-500 dark:text-gray-400">
                                        <span class="text-gray-300 dark:text-gray-600">-</span>
                                    </td>

                                    <!-- Acciones -->
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                        @if($existingValue)
                                            <div class="flex items-center justify-center gap-2">
                                                <button wire:click="deleteValue({{ $existingValue->id }})"
                                                    class="inline-flex items-center gap-1 px-3 py-2 sm:px-3 sm:py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 text-xs font-medium rounded-full hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors">
                                                    <x-heroicon-o-trash class="w-5 h-5 sm:w-3 sm:h-3" />
                                                    Eliminar
                                                </button>
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <!-- Footer with Cancel Button -->
            <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-end">
                <button wire:click="$dispatch('closeValuesModal')" type="button"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                    Cancelar
                </button>
            </div>
        </div>
    </div>
</div>