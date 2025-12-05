<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class MakeLivewireModuleCommand extends Command
{
    protected $signature = 'make:livewire-module 
        {name : Nombre del módulo (ej: product-list)}
        {--model= : Nombre del modelo}
        {--migration : Crear migración}
        {--tenant : Crear en carpeta tenant (por defecto true)}
        {--central : Crear en carpeta central}
        {--module= : Nombre del módulo padre}
        {--with-services : Generar carpeta Services con clases base}
        {--fields= : Campos del formulario (separados por coma)}';

    protected $description = 'Crea un módulo CRUD completo con Livewire, modelo, migración, vista con DataTable, búsqueda, paginación y exportación';

    public function handle()
    {
        $name = $this->argument('name');
        $modelName = $this->option('model') ?: Str::studly(Str::singular($name));
        $isTenant = !$this->option('central'); // Por defecto es tenant
        $moduleName = $this->option('module');

        // Si no se especifica módulo, inferirlo del nombre
        if (!$moduleName) {
            $moduleName = $this->inferModuleFromName($name);
        }

        $this->info("Creando módulo: {$name} en carpeta: {$moduleName}");

        // 1. Crear el modelo
        $this->createModel($modelName, $isTenant);

        // 2. Crear la migración si se solicita
        if ($this->option('migration')) {
            $this->createMigration($modelName);
        }

        // 3. Crear el componente Livewire
        $this->createLivewireComponent($name, $modelName, $isTenant, $moduleName);

        // 4. Crear la vista con carpeta components
        $this->createView($name, $isTenant, $moduleName);

        // 5. Crear Services si se solicita
        if ($this->option('with-services')) {
            $this->createServices($name, $modelName, $isTenant, $moduleName);
        }

        // 6. Mostrar las rutas sugeridas
        $this->showRoutes($name, $isTenant, $moduleName);

        $this->info("\n✅ Módulo {$name} creado exitosamente en {$moduleName}!");
        $this->info("\n📝 Próximos pasos:");
        $this->line("   1. Revisa y ajusta las migraciones en database/migrations");
        $this->line("   2. Ejecuta: php artisan migrate");
        $this->line("   3. Agrega la ruta sugerida a tu archivo de rutas");
        $this->line("   4. Personaliza los campos del formulario según tus necesidades");
    }

    private function inferModuleFromName($name)
    {
        // Inferir módulo basado en palabras clave comunes
        $name = strtolower($name);

        if (str_contains($name, 'product') || str_contains($name, 'catalog')) {
            return 'Products';
        } elseif (str_contains($name, 'sale') || str_contains($name, 'order') || str_contains($name, 'invoice')) {
            return 'Sales';
        } elseif (str_contains($name, 'customer') || str_contains($name, 'client')) {
            return 'Customers';
        } elseif (str_contains($name, 'inventory') || str_contains($name, 'stock')) {
            return 'Inventory';
        } elseif (str_contains($name, 'user') || str_contains($name, 'auth')) {
            return 'Users';
        } elseif (str_contains($name, 'config') || str_contains($name, 'setting')) {
            return 'Configuration';
        } elseif (str_contains($name, 'report') || str_contains($name, 'analytics')) {
            return 'Reports';
        } else {
            // Por defecto, usar la primera palabra en PascalCase
            $words = explode('-', $name);
            return Str::studly($words[0]);
        }
    }

    private function createModel($modelName, $isTenant)
    {
        $namespace = $isTenant ? 'App\\Models\\Tenant' : 'App\\Models\\Central';
        $directory = $isTenant ? 'app/Models/Tenant' : 'app/Models/Central';
        $connection = $isTenant ? 'tenant' : 'central';

        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $modelContent = $this->getModelStub($modelName, $namespace, $connection);

        File::put("{$directory}/{$modelName}.php", $modelContent);

        $this->line("✓ Modelo creado: {$directory}/{$modelName}.php");
    }

    private function createMigration($modelName)
    {
        $tableName = Str::snake(Str::plural($modelName));
        $migrationName = "create_{$tableName}_table";

        $this->call('make:migration', [
            'name' => $migrationName,
            '--create' => $tableName
        ]);

        $this->line("✓ Migración creada para tabla: {$tableName}");
    }

    private function createLivewireComponent($name, $modelName, $isTenant, $moduleName)
    {
        // Determinar carpeta según tipo y módulo
        $subfolder = $isTenant ? 'Tenant' : 'Central';
        $livewireDir = "app/Livewire/{$subfolder}/{$moduleName}";

        if (!File::exists($livewireDir)) {
            File::makeDirectory($livewireDir, 0755, true);
        }

        $className = Str::studly($name) . 'Form';
        $namespace = "App\\Livewire\\{$subfolder}\\{$moduleName}";
        $componentContent = $this->getLivewireStub($className, $modelName, $namespace, $isTenant, $moduleName);

        File::put("{$livewireDir}/{$className}.php", $componentContent);

        $this->line("✓ Componente Livewire creado: {$livewireDir}/{$className}.php");
    }

    private function createView($name, $isTenant, $moduleName)
    {
        // Determinar carpeta según tipo y módulo
        $subfolder = $isTenant ? 'tenant' : 'central';
        $moduleFolder = Str::kebab($moduleName);
        $viewDir = "resources/views/livewire/{$subfolder}/{$moduleFolder}";
        $componentsDir = "{$viewDir}/components";
        $viewName = Str::kebab($name);

        // Crear carpeta components
        if (!File::exists($componentsDir)) {
            File::makeDirectory($componentsDir, 0755, true);
        }

        $viewContent = $this->getViewStub($name);

        File::put("{$componentsDir}/{$viewName}-form.blade.php", $viewContent);

        $this->line("✓ Vista creada: {$componentsDir}/{$viewName}-form.blade.php");
    }

    private function createServices($name, $modelName, $isTenant, $moduleName)
    {
        $subfolder = $isTenant ? 'Tenant' : 'Central';
        $servicesDir = "app/Livewire/{$subfolder}/{$moduleName}/Services";

        if (!File::exists($servicesDir)) {
            File::makeDirectory($servicesDir, 0755, true);
        }

        $className = Str::studly($name);
        
        // Crear archivos de servicios básicos
        $services = [
            "{$className}Service.php" => $this->getServiceStub($className, $modelName, $subfolder, $moduleName),
            "{$className}QueryService.php" => $this->getQueryServiceStub($className, $modelName, $subfolder, $moduleName),
            "{$className}ValidationService.php" => $this->getValidationServiceStub($className, $subfolder, $moduleName),
        ];

        foreach ($services as $filename => $content) {
            File::put("{$servicesDir}/{$filename}", $content);
            $this->line("✓ Service creado: {$servicesDir}/{$filename}");
        }
    }

    private function showRoutes($name, $isTenant, $moduleName)
    {
        $routeName = Str::kebab($name);
        $componentName = Str::studly($name) . 'Form';
        $subfolder = $isTenant ? 'Tenant' : 'Central';

        $this->info("\n📋 Rutas sugeridas para routes/web.php:");
        $this->line("Route::get('/{$routeName}', App\\Livewire\\{$subfolder}\\{$moduleName}\\{$componentName}::class)->name('{$routeName}');");
    }

    private function getModelStub($modelName, $namespace, $connection)
    {
        return "<?php

namespace {$namespace};

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class {$modelName} extends Model
{
    use HasFactory;

    protected \$connection = '{$connection}';
    protected \$table = '" . Str::snake(Str::plural($modelName)) . "';

    protected \$fillable = [
        'name',
        'description',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'status' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
";
    }

    private function getLivewireStub($className, $modelName, $namespace, $isTenant, $moduleName)
    {
        $stubPath = base_path('stubs/livewire.component.stub');
        
        if (File::exists($stubPath)) {
            $content = File::get($stubPath);
            
            $modelType = $isTenant ? 'Tenant' : 'Central';
            $modelNamespace = "App\\Models\\{$modelType}\\{$modelName}";
            $subfolder = $isTenant ? 'tenant' : 'central';
            $moduleFolder = Str::kebab($moduleName);
            $viewPath = "livewire.{$subfolder}.{$moduleFolder}.components." . Str::kebab(str_replace('Form', '', $className)) . "-form";
            
            $replacements = [
                '{{NAMESPACE}}' => $namespace,
                '{{CLASS_NAME}}' => $className,
                '{{MODEL_NAMESPACE}}' => $modelNamespace,
                '{{MODEL_NAME}}' => $modelName,
                '{{VIEW_PATH}}' => $viewPath,
            ];
            
            return str_replace(array_keys($replacements), array_values($replacements), $content);
        }
        
        // Fallback si no existe el stub
        return $this->getDefaultLivewireStub($className, $modelName, $namespace, $isTenant, $moduleName);
    }

    private function getDefaultLivewireStub($className, $modelName, $namespace, $isTenant, $moduleName)
    {
        $modelType = $isTenant ? 'Tenant' : 'Central';
        $modelNamespace = "App\\Models\\{$modelType}\\{$modelName}";
        $subfolder = $isTenant ? 'tenant' : 'central';
        $moduleFolder = Str::kebab($moduleName);
        $viewPath = "livewire.{$subfolder}.{$moduleFolder}.components." . Str::kebab(str_replace('Form', '', $className)) . "-form";

        return "<?php

namespace {$namespace};

use Livewire\Component;
use Livewire\WithPagination;
use {$modelNamespace};

class {$className} extends Component
{
    use WithPagination;

    public \$search = '';
    public \$showModal = false;
    public \$editingId = null;
    public \$perPage = 10;
    public \$sortField = 'id';
    public \$sortDirection = 'desc';
    public \$reusable = false;

    // Propiedades del formulario
    public \$name = '';
    public \$description = '';
    public \$status = 1;

    protected \$rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:500',
    ];

    protected \$queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function updatingSearch()
    {
        \$this->resetPage();
    }

    public function updatingPerPage()
    {
        \$this->resetPage();
    }

    public function sortBy(\$field)
    {
        if (\$this->sortField === \$field) {
            \$this->sortDirection = \$this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            \$this->sortDirection = 'asc';
        }

        \$this->sortField = \$field;
        \$this->resetPage();
    }

    public function render()
    {
        \$items = {$modelName}::query()
            ->when(\$this->search, function (\$query) {
                \$query->where('name', 'like', '%' . \$this->search . '%')
                      ->orWhere('description', 'like', '%' . \$this->search . '%');
            })
            ->orderBy(\$this->sortField, \$this->sortDirection)
            ->paginate(\$this->perPage);

        return view('{$viewPath}', [
            'items' => \$items
        ]);
    }

    public function create()
    {
        \$this->resetForm();
        \$this->showModal = true;
    }

    public function edit(\$id)
    {
        \$item = {$modelName}::findOrFail(\$id);
        \$this->editingId = \$id;
        \$this->name = \$item->name;
        \$this->description = \$item->description;
        \$this->status = \$item->status ?? 1;
        \$this->showModal = true;
    }

    public function save()
    {
        \$this->validate();

        try {
            if (\$this->editingId) {
                \$item = {$modelName}::findOrFail(\$this->editingId);
                \$item->update([
                    'name' => \$this->name,
                    'description' => \$this->description,
                    'status' => \$this->status,
                ]);

                session()->flash('message', 'Registro actualizado exitosamente.');
            } else {
                {$modelName}::create([
                    'name' => \$this->name,
                    'description' => \$this->description,
                    'status' => \$this->status,
                ]);

                session()->flash('message', 'Registro creado exitosamente.');
            }

            \$this->resetForm();
            \$this->showModal = false;
        } catch (\Exception \$e) {
            session()->flash('error', 'Error al guardar: ' . \$e->getMessage());
        }
    }

    public function delete(\$id)
    {
        try {
            {$modelName}::findOrFail(\$id)->delete();
            session()->flash('message', 'Registro eliminado exitosamente.');
        } catch (\Exception \$e) {
            session()->flash('error', 'Error al eliminar: ' . \$e->getMessage());
        }
    }

    public function toggleItemStatus(\$id)
    {
        try {
            \$item = {$modelName}::findOrFail(\$id);
            \$item->status = !\$item->status;
            \$item->save();
            
            session()->flash('message', 'Estado actualizado exitosamente.');
        } catch (\Exception \$e) {
            session()->flash('error', 'Error al actualizar el estado: ' . \$e->getMessage());
        }
    }

    public function exportExcel()
    {
        \$this->dispatch('show-toast', [
            'type' => 'info',
            'message' => 'Exportación a Excel - En desarrollo'
        ]);
    }

    public function exportPdf()
    {
        \$this->dispatch('show-toast', [
            'type' => 'info',
            'message' => 'Exportación a PDF - En desarrollo'
        ]);
    }

    public function exportCsv()
    {
        \$this->dispatch('show-toast', [
            'type' => 'info',
            'message' => 'Exportación a CSV - En desarrollo'
        ]);
    }

    private function resetForm()
    {
        \$this->editingId = null;
        \$this->name = '';
        \$this->description = '';
        \$this->status = 1;
        \$this->resetErrorBag();
    }
}
";
    }

    private function getViewStub($name)
    {
        $stubPath = base_path('stubs/livewire.view.stub');
        
        if (File::exists($stubPath)) {
            $content = File::get($stubPath);
            
            $replacements = [
                '{{MODULE_TITLE}}' => Str::title(str_replace('-', ' ', $name)),
            ];
            
            return str_replace(array_keys($replacements), array_values($replacements), $content);
        }
        
        // Fallback básico si no existe el stub
        return "<div>\n    <h1>" . Str::title($name) . "</h1>\n    <!-- Contenido del módulo -->\n</div>";
    }

    private function getServiceStub($className, $modelName, $subfolder, $moduleName)
    {
        $namespace = "App\\Livewire\\{$subfolder}\\{$moduleName}\\Services";
        $modelNamespace = "App\\Models\\{$subfolder}\\{$modelName}";

        return "<?php

namespace {$namespace};

use {$modelNamespace};

class {$className}Service
{
    /**
     * Crear un nuevo registro
     */
    public function create(array \$data)
    {
        return {$modelName}::create(\$data);
    }

    /**
     * Actualizar un registro existente
     */
    public function update(int \$id, array \$data)
    {
        \$item = {$modelName}::findOrFail(\$id);
        \$item->update(\$data);
        return \$item;
    }

    /**
     * Eliminar un registro
     */
    public function delete(int \$id)
    {
        return {$modelName}::findOrFail(\$id)->delete();
    }

    /**
     * Toggle estado del registro
     */
    public function toggleStatus(int \$id)
    {
        \$item = {$modelName}::findOrFail(\$id);
        \$item->status = !\$item->status;
        \$item->save();
        return \$item;
    }
}
";
    }

    private function getQueryServiceStub($className, $modelName, $subfolder, $moduleName)
    {
        $namespace = "App\\Livewire\\{$subfolder}\\{$moduleName}\\Services";
        $modelNamespace = "App\\Models\\{$subfolder}\\{$modelName}";

        return "<?php

namespace {$namespace};

use {$modelNamespace};

class {$className}QueryService
{
    /**
     * Obtener registros paginados con búsqueda y ordenamiento
     */
    public function getPaginatedItems(string \$search, int \$perPage, string \$sortField, string \$sortDirection)
    {
        return {$modelName}::query()
            ->when(\$search, function (\$query) use (\$search) {
                \$query->where('name', 'like', '%' . \$search . '%')
                      ->orWhere('description', 'like', '%' . \$search . '%');
            })
            ->orderBy(\$sortField, \$sortDirection)
            ->paginate(\$perPage);
    }

    /**
     * Obtener un registro para edición
     */
    public function getForEdit(int \$id)
    {
        return {$modelName}::findOrFail(\$id);
    }
}
";
    }

    private function getValidationServiceStub($className, $subfolder, $moduleName)
    {
        $namespace = "App\\Livewire\\{$subfolder}\\{$moduleName}\\Services";

        return "<?php

namespace {$namespace};

class {$className}ValidationService
{
    /**
     * Obtener reglas de validación
     */
    public function getValidationRules()
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'status' => 'boolean',
        ];
    }

    /**
     * Obtener mensajes de validación personalizados
     */
    public function getValidationMessages()
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'description.max' => 'La descripción no puede exceder 500 caracteres.',
        ];
    }
}
";
    }
}