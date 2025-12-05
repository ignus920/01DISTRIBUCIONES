<?php

namespace App\Services\Tenant\Inventory;

use App\Models\Tenant\Items\Category;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;
use Carbon\Carbon;

class CategoriesService
{
    private function ensureTenantConnection()
    {
        $tenantId = session('tenant_id');

        if (!$tenantId) {
            return redirect()->route('tenant.select');
        }

        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            session()->forget('tenant_id');
            return redirect()->route('tenant.select');
        }

        // Establecer conexión tenant
        $tenantManager = app(TenantManager::class);
        $tenantManager->setConnection($tenant);

        // Inicializar tenancy
        tenancy()->initialize($tenant);
    }

    public function createCategory(array $data){

        $this->ensureTenantConnection();

        // Crear la categoría
        return Category::create([
            'name' => $data['name'],
            'status' => $data['status'] ?? 1,
            // Agregar otros campos si es necesario
        ]);
    }

    public function getActiveCategories()
    {
        return Category::where('status', 1)
            ->orderBy('name')
            ->get();
    }

    public function categoryExists($name)
    {
        return Category::where('name', $name)->exists();
    }


    public function deleteCategory($id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return $category;
    }

    public function getCategory($id)
    {
        return Category::findOrFail($id);
    }
}
