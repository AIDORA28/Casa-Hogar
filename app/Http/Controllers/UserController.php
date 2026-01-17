<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class UserController extends Controller
{
    /**
     * Listar todos los usuarios con sus permisos (incluidos soft deleted)
     */
    public function index()
    {
        $users = User::withTrashed()->with('permissions')->orderBy('created_at', 'desc')->get();
        
        return response()->json($users->map(function ($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'permissions' => $user->permissions->pluck('name'),
                'deleted_at' => $user->deleted_at,
                'created_at' => $user->created_at->format('d/m/Y')
            ];
        }), 200);
    }

    /**
     * Crear un nuevo usuario
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', Rules\Password::defaults()],
            'permissions' => 'array'
        ], [
            'name.required' => 'El nombre es obligatorio',
            'email.required' => 'El correo electrónico es obligatorio',
            'email.email' => 'Debe ser un correo electrónico válido',
            'email.unique' => 'Este correo ya está registrado',
            'password.required' => 'La contraseña es obligatoria'
        ]);

        // Crear usuario (rol 'tesorero' por defecto)
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'tesorero'
        ]);

        // Asignar permisos si fueron enviados
        if (isset($validated['permissions']) && is_array($validated['permissions'])) {
            $permissionIds = Permission::whereIn('name', $validated['permissions'])->pluck('id');
            $user->permissions()->attach($permissionIds);
        }

        return response()->json([
            'message' => 'Usuario creado exitosamente',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'permissions' => $user->load('permissions')->permissions->pluck('name')
            ]
        ], 201);
    }

    /**
     * Actualizar usuario y sus permisos
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // No permitir editar al admin
        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'No se puede editar la cuenta del administrador'
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'password' => ['sometimes', Rules\Password::defaults()],
            'permissions' => 'sometimes|array'
        ], [
            'name.required' => 'El nombre es obligatorio',
            'email.email' => 'Debe ser un correo electrónico válido',
            'email.unique' => 'Este correo ya está registrado'
        ]);

        // Actualizar datos básicos
        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }
        if (isset($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }
        
        $user->save();

        // Actualizar permisos
        if (isset($validated['permissions'])) {
            $permissionIds = Permission::whereIn('name', $validated['permissions'])->pluck('id');
            $user->permissions()->sync($permissionIds);
        }

        return response()->json([
            'message' => 'Usuario actualizado exitosamente',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'permissions' => $user->fresh()->permissions->pluck('name')
            ]
        ], 200);
    }

    /**
     * Eliminar usuario
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);

        // No permitir eliminar al admin
        if ($user->role === 'admin') {
            return response()->json([
                'message' => 'No se puede eliminar la cuenta del administrador'
            ], 403);
        }

        // No permitir auto-eliminación
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'No puedes eliminar tu propia cuenta'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado exitosamente'
        ], 200);
    }

    /**
     * Listar permisos disponibles
     */
    public function getPermissions()
    {
        return response()->json(Permission::all(['name', 'description']), 200);
    }

    /**
     * Restaurar usuario eliminado
     */
    public function restore($id)
    {
        $user = User::withTrashed()->findOrFail($id);
        $user->restore();
        
        return response()->json([
            'message' => 'Usuario restaurado exitosamente',
            'user' => $user
        ], 200);
    }
}
