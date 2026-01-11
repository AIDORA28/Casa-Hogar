<?php

namespace App\Http\Controllers;

use App\Http\Resources\ExpenseResource;
use App\Models\ActivityLog;
use App\Models\Expense;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $expenses = Expense::with('user')
            ->orderBy('expense_date', 'desc')
            ->paginate(15);

        return ExpenseResource::collection($expenses);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'expense_date' => 'required|date',
            'amount' => 'required|numeric|min:0',
            'description' => 'required|string',
        ], [
            'expense_date.required' => 'La fecha del gasto es obligatoria',
            'amount.required' => 'El monto es obligatorio',
            'amount.min' => 'El monto debe ser positivo',
            'description.required' => 'La descripción es obligatoria',
        ]);

        $expense = Expense::create([
            ...$validated,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Gasto registrado exitosamente',
            'expense' => new ExpenseResource($expense->load('user')),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $expense = Expense::with('user')->findOrFail($id);
        return new ExpenseResource($expense);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $expense = Expense::findOrFail($id);
        $oldData = $expense->toArray();

        $validated = $request->validate([
            'expense_date' => 'sometimes|date',
            'amount' => 'sometimes|numeric|min:0',
            'description' => 'sometimes|string',
        ]);

        $expense->update($validated);

        // Registrar en activity log
        ActivityLog::log('updated', 'Expense', $expense->id, [
            'old' => $oldData,
            'new' => $expense->toArray(),
        ]);

        return response()->json([
            'message' => 'Gasto actualizado exitosamente',
            'expense' => new ExpenseResource($expense->load('user')),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $expense = Expense::findOrFail($id);
        $expense->delete();

        return response()->json([
            'message' => 'Gasto eliminado exitosamente',
        ], 200);
    }
}
