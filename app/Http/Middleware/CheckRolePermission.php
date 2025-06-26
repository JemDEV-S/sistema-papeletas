<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRolePermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Verificar si el usuario está autenticado
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Debe iniciar sesión para acceder a esta página.');
        }

        $user = auth()->user();

        // Verificar si el usuario está activo
        if (!$user->is_active) {
            auth()->logout();
            return redirect()->route('login')->with('error', 'Su cuenta ha sido desactivada. Contacte al administrador.');
        }

        // Verificar si el usuario tiene el permiso requerido
        if (!$user->hasPermission($permission)) {
            abort(403, 'No tiene permisos para acceder a esta funcionalidad.');
        }

        return $next($request);
    }
}