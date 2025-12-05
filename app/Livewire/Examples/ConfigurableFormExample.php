<?php

namespace App\Livewire\Examples;

use App\Traits\HasCompanyConfiguration;
use Livewire\Component;

/**
 * Ejemplo de cómo usar el trait HasCompanyConfiguration en tus componentes Livewire
 */
class ConfigurableFormExample extends Component
{
    use HasCompanyConfiguration;

    // Propiedades del formulario
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $address = '';
    public bool $isActive = true;

    // Configuración del módulo
    protected string $moduleName = 'VENTAS'; // Nombre del módulo en tu BD

    public function mount()
    {
        // SIEMPRE inicializar la configuración en mount()
        $this->initializeCompanyConfiguration();

        // DEBUG: Mostrar información para depuración
        \Log::info('🔍 DEBUG ConfigurableFormExample', [
            'currentCompanyId' => $this->currentCompanyId,
            'currentPlainId' => $this->currentPlainId,
            'moduleName' => $this->moduleName,
            'user' => auth()->user()->id ?? 'no-auth',
        ]);
    }

    /**
     * Ejemplo: Obtener campos visibles según configuración
     */
    public function getVisibleFields(): array
    {
        // Obtener todas las opciones para esta empresa
        $moduleConfig = $this->getModuleConfig($this->moduleName);
        $visibleFields = [];

        foreach ($moduleConfig as $option) {
            // Solo agregar si value = 1 (habilitado)
            if ($option->value == 1) {
                $visibleFields[] = "opcion_" . $option->opcion; // ej: opcion_1, opcion_2, etc.
            }
        }

        return $visibleFields;
    }

    /**
     * Ejemplo: Obtener campos deshabilitados según configuración
     */
    public function getDisabledFields(): array
    {
        // Obtener todas las opciones para esta empresa
        $moduleConfig = $this->getModuleConfig($this->moduleName);
        $disabledFields = [];

        foreach ($moduleConfig as $option) {
            // Solo agregar si value = 0 (deshabilitado)
            if ($option->value == 0) {
                $disabledFields[] = "opcion_" . $option->opcion; // ej: opcion_1, opcion_2, etc.
            }
        }

        return $disabledFields;
    }

    /**
     * Ejemplo: Validación dinámica según configuración
     */
    public function save()
    {
        // Reglas base
        $baseRules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'isActive' => ['boolean'],
        ];

        // Filtrar reglas según configuración
        $validatedRules = $this->validateFormFields($this->moduleName, $baseRules);

        // Validar solo campos que deben mostrarse
        $this->validate($validatedRules);

        // Filtrar datos según configuración
        $dataToSave = $this->filterDataByConfiguration($this->moduleName, [
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone,
            'address' => $this->address,
            'is_active' => $this->isActive,
        ]);

        // Guardar en BD...
        // Model::create($dataToSave);

        session()->flash('message', 'Usuario guardado exitosamente.');
    }

    /**
     * Ejemplo: Obtener configuración específica
     */
    public function getFieldLabel(string $field): string
    {
        // Obtener etiqueta personalizada desde configuración
        $customLabel = $this->getConfigValue($this->moduleName, "label_{$field}");

        return $customLabel ?: ucfirst($field);
    }

    /**
     * Ejemplo: Verificar permisos específicos
     */
    public function canEditField(string $field): bool
    {
        return $this->shouldShowField($this->moduleName, "edit_{$field}");
    }

    public function render()
    {
        $moduleConfig = $this->getModuleConfig($this->moduleName);

        // DEBUG: Log de toda la configuración
        \Log::info("🔍 DEBUG render() - Configuración completa", [
            'moduleName' => $this->moduleName,
            'total_opciones' => count($moduleConfig),
            'todas_las_opciones' => $moduleConfig,
            'visible_fields' => $this->getVisibleFields(),
            'disabled_fields' => $this->getDisabledFields(),
        ]);

        return view('livewire.examples.configurable-form-example', [
            'visibleFields' => $this->getVisibleFields(),
            'disabledFields' => $this->getDisabledFields(),
            'moduleConfig' => $moduleConfig,
        ]);
    }
}