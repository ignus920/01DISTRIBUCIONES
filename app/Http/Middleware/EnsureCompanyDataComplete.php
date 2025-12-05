<?php

namespace App\Http\Middleware;

use App\Services\Company\CompanyDataValidator;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureCompanyDataComplete
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Solo aplicar a usuarios autenticados
        if (!Auth::check()) {
            return $next($request);
        }

        $user = Auth::user();

        // Los Super Administradores no necesitan completar datos de empresa
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        $validator = app(CompanyDataValidator::class);

        // Verificar si los datos de la empresa están completos
        if (!$validator->isCompanyDataComplete($user)) {
            // Si estamos en la ruta de setup, permitir acceso
            if ($request->routeIs('company.setup')) {
                return $next($request);
            }

            // Si los datos no están completos, redirigir al setup
            return redirect()->route('company.setup');
        }

        // Si estamos en la ruta de setup pero los datos ya están completos, redirigir al dashboard
        if ($request->routeIs('company.setup')) {
            return redirect()->route('tenant.select');
        }

        return $next($request);
    }
}