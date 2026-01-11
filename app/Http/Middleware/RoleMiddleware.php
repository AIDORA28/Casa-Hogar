<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role - Rol requerido (admin, tesorero)
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Verificar si el usuario está autenticado
        if (!$request->user()) {
            return response()->json([
                'message' => 'No autenticado. Por favor inicie sesión.',
            ], 401);
        }

        // Verificar si el usuario tiene el rol requerido
        if ($request->user()->role !== $role) {
            return response()->json([
                'message' => 'No tiene permisos para acceder a este recurso.',
                'required_role' => $role,
                'your_role' => $request->user()->role,
            ], 403);
        }

        return $next($request);
    }
}
