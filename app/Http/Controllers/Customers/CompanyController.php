<?php

namespace App\Http\Controllers\Customers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\TenantModels\Customers\TypeIdentification;
use App\Models\TenantModels\Customers\FiscalResponsibility;
use App\Models\TenantModels\Customers\VntCompanies;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Exception;

class CompanyController extends Controller
{
    //
  /**
     * Listar todas las compañías con filtros opcionales
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = VntCompanies::query();

            // Incluir relaciones
            $query->with(['typeIdentification', 'fiscalResponsibility']);

            // Filtro por estado (activo/inactivo)
            if ($request->has('status') && $request->status !== '') {
                $query->where('status', $request->status);
            }

            // Filtro por tipo de persona
            if ($request->has('typePerson')) {
                $query->where('typePerson', $request->typePerson);
            }

            // Filtro por tipo de identificación
            if ($request->has('typeIdentificationId')) {
                $query->where('typeIdentificationId', $request->typeIdentificationId);
            }

            // Búsqueda por identificación
            if ($request->has('identification')) {
                $query->where('identification', 'like', '%' . $request->identification . '%');
            }

            // Búsqueda general por nombre o razón social
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('businessName', 'like', "%{$search}%")
                      ->orWhere('firstName', 'like', "%{$search}%")
                      ->orWhere('lastName', 'like', "%{$search}%")
                      ->orWhere('identification', 'like', "%{$search}%")
                      ->orWhere('billingEmail', 'like', "%{$search}%");
                });
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'createdAt');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginación
            $perPage = $request->get('per_page', 15);
            
            if ($request->get('paginate', true)) {
                $companies = $query->paginate($perPage);
            } else {
                $companies = $query->get();
            }

            return response()->json([
                'success' => true,
                'data' => $companies
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las compañías',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear una nueva compañía
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'businessName' => 'nullable|string|max:255',
                'billingEmail' => 'nullable|email|max:255',
                'firstName' => 'nullable|string|max:255',
                'lastName' => 'nullable|string|max:255',
                'secondLastName' => 'nullable|string|max:255',
                'secondName' => 'nullable|string|max:255',
                'identification' => 'nullable|string|max:15|unique:vnt_companies,identification',
                'checkDigit' => 'nullable|integer|between:0,9',
                'status' => 'nullable|integer|in:0,1',
                'integrationDataId' => 'nullable|integer',
                'typePerson' => 'nullable|integer',
                'typeIdentificationId' => 'nullable|exists:cnf_type_identifications,id',
                'regimeId' => 'nullable|integer',
                'fiscalResponsibilityId' => 'nullable|exists:cnf_fiscal_responsabilities,id',
                'code_ciiu' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $company = VntCompanies::create($validator->validated());
            $company->load(['typeIdentification', 'fiscalResponsibility']);

            return response()->json([
                'success' => true,
                'message' => 'Compañía creada exitosamente',
                'data' => $company
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la compañía',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mostrar una compañía específica
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function show(int $id): JsonResponse
    {
        try {
            $company = VntCompanies::with(['typeIdentification', 'fiscalResponsibility'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $company
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Compañía no encontrada',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Buscar compañía por identificación
     * 
     * @param string $identification
     * @return JsonResponse
     */
    public function findByIdentification(string $identification): JsonResponse
    {
        try {
            $company = VntCompanies::with(['typeIdentification', 'fiscalResponsibility'])
                ->where('identification', $identification)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => $company
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Compañía no encontrada',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Actualizar una compañía existente
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $company = VntCompanies::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'businessName' => 'nullable|string|max:255',
                'billingEmail' => 'nullable|email|max:255',
                'firstName' => 'nullable|string|max:255',
                'lastName' => 'nullable|string|max:255',
                'secondLastName' => 'nullable|string|max:255',
                'secondName' => 'nullable|string|max:255',
                'identification' => [
                    'nullable',
                    'string',
                    'max:15',
                    Rule::unique('vnt_companies', 'identification')->ignore($company->id)
                ],
                'checkDigit' => 'nullable|integer|between:0,9',
                'status' => 'nullable|integer|in:0,1',
                'integrationDataId' => 'nullable|integer',
                'typePerson' => 'nullable|integer',
                'typeIdentificationId' => 'nullable|exists:cnf_type_identifications,id',
                'regimeId' => 'nullable|integer',
                'fiscalResponsibilityId' => 'nullable|exists:cnf_fiscal_responsabilities,id',
                'code_ciiu' => 'nullable|string|max:255',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $company->update($validator->validated());
            $company->load(['typeIdentification', 'fiscalResponsibility']);

            return response()->json([
                'success' => true,
                'message' => 'Compañía actualizada exitosamente',
                'data' => $company
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la compañía',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar parcialmente una compañía (PATCH)
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updatePartial(Request $request, int $id): JsonResponse
    {
        try {
            $company = VntCompanies::findOrFail($id);

            // Solo validar los campos que vienen en el request
            $rules = [];
            $data = $request->only([
                'businessName', 'billingEmail', 'firstName', 'lastName',
                'secondLastName', 'secondName', 'identification', 'checkDigit',
                'status', 'integrationDataId', 'typePerson', 'typeIdentificationId',
                'regimeId', 'fiscalResponsibilityId', 'code_ciiu'
            ]);

            foreach ($data as $key => $value) {
                if ($key === 'identification') {
                    $rules[$key] = [
                        'nullable',
                        'string',
                        'max:15',
                        Rule::unique('vnt_companies', 'identification')->ignore($company->id)
                    ];
                } elseif ($key === 'billingEmail') {
                    $rules[$key] = 'nullable|email|max:255';
                } elseif ($key === 'checkDigit') {
                    $rules[$key] = 'nullable|integer|between:0,9';
                } elseif ($key === 'status') {
                    $rules[$key] = 'nullable|integer|in:0,1';
                } elseif (in_array($key, ['typeIdentificationId'])) {
                    $rules[$key] = 'nullable|exists:cnf_type_identifications,id';
                } elseif (in_array($key, ['fiscalResponsibilityId'])) {
                    $rules[$key] = 'nullable|exists:cnf_fiscal_responsabilities,id';
                }
            }

            $validator = Validator::make($data, $rules);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $company->update($validator->validated());
            $company->load(['typeIdentification', 'fiscalResponsibility']);

            return response()->json([
                'success' => true,
                'message' => 'Compañía actualizada exitosamente',
                'data' => $company
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la compañía',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una compañía (soft delete)
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $company = VntCompanies::findOrFail($id);
            $company->delete();

            return response()->json([
                'success' => true,
                'message' => 'Compañía eliminada exitosamente'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la compañía',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restaurar una compañía eliminada
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function restore(int $id): JsonResponse
    {
        try {
            $company = VntCompanies::withTrashed()->findOrFail($id);
            
            if (!$company->trashed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'La compañía no está eliminada'
                ], 400);
            }

            $company->restore();
            $company->load(['typeIdentification', 'fiscalResponsibility']);

            return response()->json([
                'success' => true,
                'message' => 'Compañía restaurada exitosamente',
                'data' => $company
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al restaurar la compañía',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar permanentemente una compañía
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function forceDelete(int $id): JsonResponse
    {
        try {
            $company = VntCompanies::withTrashed()->findOrFail($id);
            $company->forceDelete();

            return response()->json([
                'success' => true,
                'message' => 'Compañía eliminada permanentemente'
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar permanentemente la compañía',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cambiar el estado de una compañía (activar/desactivar)
     * 
     * @param int $id
     * @return JsonResponse
     */
    public function toggleStatus(int $id): JsonResponse
    {
        try {
            $company = VntCompanies::findOrFail($id);
            $company->status = $company->status === 1 ? 0 : 1;
            $company->save();

            return response()->json([
                'success' => true,
                'message' => 'Estado actualizado exitosamente',
                'data' => $company
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al cambiar el estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Listar compañías eliminadas
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function trashed(Request $request): JsonResponse
    {
        try {
            $query = VntCompanies::onlyTrashed()
                ->with(['typeIdentification', 'fiscalResponsibility']);

            // Búsqueda
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('businessName', 'like', "%{$search}%")
                      ->orWhere('firstName', 'like', "%{$search}%")
                      ->orWhere('lastName', 'like', "%{$search}%")
                      ->orWhere('identification', 'like', "%{$search}%");
                });
            }

            $perPage = $request->get('per_page', 15);
            $companies = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $companies
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener compañías eliminadas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener estadísticas de compañías
     * 
     * @return JsonResponse
     */
    public function statistics(): JsonResponse
    {
        try {
            $stats = [
                'total' => VntCompanies::count(),
                'active' => VntCompanies::where('status', 1)->count(),
                'inactive' => VntCompanies::where('status', 0)->count(),
                'deleted' => VntCompanies::onlyTrashed()->count(),
                'by_type_person' => VntCompanies::selectRaw('typePerson, COUNT(*) as count')
                    ->groupBy('typePerson')
                    ->get(),
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
