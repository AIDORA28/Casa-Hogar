<?php

namespace App\Http\Controllers;

use App\Models\CapitalInjection;
use App\Models\ActivityLog;
use App\Helpers\UserHelper;
use Illuminate\Http\Request;

class CapitalInjectionController extends Controller
{
    /**
     * Registrar una nueva inyección de capital
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
            'injection_date' => 'required|date'
        ], [
            'amount.required' => 'El monto es obligatorio',
            'amount.min' => 'El monto debe ser mayor a 0',
            'reason.required' => 'El motivo es obligatorio',
            'injection_date.required' => 'La fecha es obligatoria',
        ]);

        $injection = CapitalInjection::create([
            ...$validated,
            'user_id' => auth()->id(),
            'user_name' => UserHelper::getFormattedName(auth()->user()),
        ]);

        // Registrar en activity log
        ActivityLog::log('created', 'CapitalInjection', $injection->id, $injection->toArray());

        return response()->json([
            'message' => 'Inyección de capital registrada exitosamente',
            'injection' => $injection
        ], 201);
    }

    /**
     * Obtener inyecciones de capital por fecha
     */
    public function getByDate($date)
    {
        $injections = CapitalInjection::whereDate('injection_date', $date)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'injections' => $injections,
            'total' => $injections->sum('amount')
        ]);
    }

    /**
     * Listar todas las inyecciones (paginado)
     */
    public function index()
    {
        $injections = CapitalInjection::with('user')
            ->orderBy('injection_date', 'desc')
            ->paginate(15);

        return response()->json($injections);
    }
}
