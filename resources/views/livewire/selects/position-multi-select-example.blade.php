{{-- 
    EJEMPLO DE USO DEL COMPONENTE PositionMultiSelect
    
    Este archivo muestra diferentes formas de usar el componente de selección múltiple de posiciones.
    NO es necesario incluir este archivo en tu aplicación, es solo para referencia.
--}}

{{-- Uso básico --}}
@livewire('selects.position-multi-select')

{{-- Uso con parámetros personalizados --}}
@livewire('selects.position-multi-select', [
    'selectedPositions' => [1, 3, 5], // IDs de posiciones preseleccionadas
    'name' => 'employee_positions',
    'placeholder' => 'Seleccionar posiciones del empleado',
    'label' => 'Posiciones del Empleado',
    'required' => true,
    'showLabel' => true,
    'class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500',
    'maxHeight' => 'max-h-48',
    'searchable' => true
])

{{-- Uso en un formulario --}}
<form wire:submit="save">
    <div class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Información del Empleado</label>
            <input type="text" wire:model="employeeName" placeholder="Nombre del empleado" class="w-full px-3 py-2 border rounded-lg">
        </div>
        
        <div>
            @livewire('selects.position-multi-select', [
                'selectedPositions' => $employeePositions,
                'name' => 'positions',
                'label' => 'Posiciones Asignadas',
                'required' => true
            ])
        </div>
        
        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg">
            Guardar Empleado
        </button>
    </div>
</form>

{{-- 
    ESCUCHAR CAMBIOS EN EL COMPONENTE PADRE:
    
    En tu componente Livewire padre, agrega este listener:
    
    protected $listeners = [
        'positions-changed' => 'updatePositions'
    ];
    
    public function updatePositions($selectedPositions)
    {
        $this->employeePositions = $selectedPositions;
        // Aquí puedes agregar lógica adicional cuando cambien las posiciones
    }
--}}

{{-- 
    VALIDACIÓN:
    
    En las reglas de validación de tu componente padre:
    
    protected function rules()
    {
        return [
            'employeePositions' => 'required|array|min:1',
            'employeePositions.*' => 'exists:cnf_positions,id'
        ];
    }
--}}

{{-- 
    PERSONALIZACIÓN DE ESTILOS:
    
    Puedes personalizar completamente los estilos pasando clases CSS:
--}}
@livewire('selects.position-multi-select', [
    'class' => 'custom-select-class border-2 border-blue-500 rounded-xl',
    'maxHeight' => 'max-h-32'
])

{{-- 
    CARACTERÍSTICAS DEL COMPONENTE:
    
    ✅ Selección múltiple con checkmarks visuales
    ✅ Búsqueda en tiempo real (opcional)
    ✅ Chips/tags para mostrar selecciones
    ✅ Botón "Limpiar todo"
    ✅ Contador de elementos seleccionados
    ✅ Soporte para modo oscuro
    ✅ Responsive design
    ✅ Accesibilidad (ARIA labels, navegación por teclado)
    ✅ Eventos Livewire para comunicación con componente padre
    ✅ Validación integrada
    ✅ Altamente personalizable
--}}