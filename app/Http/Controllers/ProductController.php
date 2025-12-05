<?php

namespace App\Http\Controllers;

use App\Models\Tenant\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * Mostrar lista de productos
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::query();

        // Filtros opcionales
        if ($request->has('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        if ($request->has('in_stock')) {
            $query->where('stock', '>', 0);
        }

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        $products = $query->orderBy('created_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $products,
            'tenant_id' => session('tenant_id')
        ]);
    }

    /**
     * Crear nuevo producto
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'sku' => 'required|string|unique:products,sku',
            'is_active' => 'boolean',
        ]);

        $product = Product::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Producto creado exitosamente',
            'data' => $product,
            'tenant_id' => session('tenant_id')
        ], 201);
    }

    /**
     * Mostrar producto específico
     */
    public function show($id): JsonResponse
    {
        $product = Product::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $product,
            'tenant_id' => session('tenant_id')
        ]);
    }

    /**
     * Actualizar producto
     */
    public function update(Request $request, $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'stock' => 'sometimes|required|integer|min:0',
            'sku' => 'sometimes|required|string|unique:products,sku,' . $id,
            'is_active' => 'boolean',
        ]);

        $product->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Producto actualizado exitosamente',
            'data' => $product,
            'tenant_id' => session('tenant_id')
        ]);
    }

    /**
     * Eliminar producto (soft delete)
     */
    public function destroy($id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado exitosamente',
            'tenant_id' => session('tenant_id')
        ]);
    }

    /**
     * Restaurar producto eliminado
     */
    public function restore($id): JsonResponse
    {
        $product = Product::withTrashed()->findOrFail($id);
        $product->restore();

        return response()->json([
            'success' => true,
            'message' => 'Producto restaurado exitosamente',
            'data' => $product,
            'tenant_id' => session('tenant_id')
        ]);
    }

    /**
     * Eliminar permanentemente
     */
    public function forceDelete($id): JsonResponse
    {
        $product = Product::withTrashed()->findOrFail($id);
        $product->forceDelete();

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado permanentemente',
            'tenant_id' => session('tenant_id')
        ]);
    }

    /**
     * Alternar estado activo/inactivo
     */
    public function toggleStatus($id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->update(['is_active' => !$product->is_active]);

        return response()->json([
            'success' => true,
            'message' => 'Estado del producto actualizado',
            'data' => $product,
            'tenant_id' => session('tenant_id')
        ]);
    }

    /**
     * Productos eliminados
     */
    public function trashed(): JsonResponse
    {
        $products = Product::onlyTrashed()->orderBy('deleted_at', 'desc')->paginate(15);

        return response()->json([
            'success' => true,
            'data' => $products,
            'tenant_id' => session('tenant_id')
        ]);
    }

    /**
     * Estadísticas de productos
     */
    public function stats(): JsonResponse
    {
        $stats = [
            'total' => Product::count(),
            'active' => Product::where('is_active', true)->count(),
            'inactive' => Product::where('is_active', false)->count(),
            'in_stock' => Product::where('stock', '>', 0)->count(),
            'out_of_stock' => Product::where('stock', 0)->count(),
            'deleted' => Product::onlyTrashed()->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
            'tenant_id' => session('tenant_id')
        ]);
    }
}