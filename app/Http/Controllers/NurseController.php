<?php

namespace App\Http\Controllers;

use App\Models\Nurse;
use Illuminate\Http\Request;

class NurseController extends Controller
{
    /**
     * Listar todas las enfermeras (incluidas eliminadas)
     */
    public function index()
    {
        $nurses = Nurse::withTrashed()->orderBy('name')->get();
        
        return response()->json($nurses, 200);
    }

    /**
     * Obtener solo enfermeras activas (para selects)
     */
    public function getActive()
    {
        $nurses = Nurse::active()->orderBy('name')->get();
        
        return response()->json($nurses, 200);
    }

    /**
     * Crear nueva enfermera
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:nurses,name',
        ], [
            'name.required' => 'El nombre es obligatorio',
            'name.unique' => 'Ya existe una enfermera con este nombre',
        ]);

        $nurse = Nurse::create([
            'name' => $validated['name'],
            'is_active' => true,
        ]);

        return response()->json([
            'message' => 'Enfermera creada exitosamente',
            'nurse' => $nurse
        ], 201);
    }

    /**
     * Actualizar nombre de enfermera
     */
    public function update(Request $request, $id)
    {
        $nurse = Nurse::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:nurses,name,' . $id,
        ], [
            'name.required' => 'El nombre es obligatorio',
            'name.unique' => 'Ya existe una enfermera con este nombre',
        ]);

        $nurse->update([
            'name' => $validated['name'],
        ]);

        return response()->json([
            'message' => 'Enfermera actualizada exitosamente',
            'nurse' => $nurse
        ], 200);
    }

    /**
     * Activar/Desactivar enfermera
     */
    public function toggleActive($id)
    {
        $nurse = Nurse::withTrashed()->findOrFail($id);
        
        $nurse->update([
            'is_active' => !$nurse->is_active
        ]);

        $status = $nurse->is_active ? 'activada' : 'desactivada';

        return response()->json([
            'message' => "Enfermera {$status} exitosamente",
            'nurse' => $nurse
        ], 200);
    }

    /**
     * Restaurar enfermera eliminada
     */
    public function restore($id)
    {
        $nurse = Nurse::withTrashed()->findOrFail($id);
        $nurse->restore();
        
        return response()->json([
            'message' => 'Enfermera restaurada exitosamente',
            'nurse' => $nurse
        ], 200);
    }
}
