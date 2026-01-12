<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        // Verificar si el usuario está autenticado
        if (!$request->user()) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        // Verificar si el usuario tiene el permiso requerido
        if (!$request->user()->permissions->contains('name', $permission)) {
            return response()->json([
                'message' => 'No tiene permisos para acceder a este recurso.',
                'required_permission' => $permission
            ], 403);
        }

        return $next($request);
    }
}
