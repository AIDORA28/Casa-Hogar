<?php

namespace App\Http\Controllers;

use App\Http\Resources\DailyClosingResource;
use App\Models\DailyClosing;
use App\Models\Expense;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyClosingController extends Controller
{
    /**
     * Listar todos los cierres de caja
     */
    public function index()
    {
        $closings = DailyClosing::with('user')
            ->orderBy('closing_date', 'desc')
            ->paginate(15);

        return DailyClosingResource::collection($closings);
    }

    /**
     * Calcular y crear cierre de caja automáticamente
     */
    public function calculate(Request $request)
    {
        $request->validate([
            'closing_date' => 'required|date|unique:daily_closings,closing_date',
        ]);

        try {
            DB::beginTransaction();

            $closingDate = $request->closing_date;

            // Calcular total de ventas del día
            $totalSales = Sale::whereDate('sale_date', $closingDate)
                ->sum('total_amount');

            // Calcular total de gastos del día
            $totalExpenses = Expense::whereDate('expense_date', $closingDate)
                ->sum('amount');

            // Obtener el saldo del día anterior
            $previousClosing = DailyClosing::where('closing_date', '<', $closingDate)
                ->orderBy('closing_date', 'desc')
                ->first();

            $previousBalance = $previousClosing ? $previousClosing->final_balance : 0;

            // Calcular balance final: (Ventas - Gastos) + Saldo Anterior
            $finalBalance = ($totalSales - $totalExpenses) + $previousBalance;

            // Crear el cierre
            $closing = DailyClosing::create([
                'closing_date' => $closingDate,
                'total_sales' => $totalSales,
                'total_expenses' => $totalExpenses,
                'previous_balance' => $previousBalance,
                'final_balance' => $finalBalance,
                'user_id' => auth()->id(),
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Cierre de caja calculado exitosamente',
                'closing' => new DailyClosingResource($closing->load('user')),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Error al calcular el cierre de caja',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Ver un cierre específico
     */
    public function show(string $id)
    {
        $closing = DailyClosing::with('user')->findOrFail($id);
        return new DailyClosingResource($closing);
    }

    /**
     * Obtener el saldo del último cierre (para arrastre automático)
     */
    public function getLastBalance()
    {
        $lastClosing = DailyClosing::orderBy('closing_date', 'desc')->first();
        
        return response()->json([
            'last_balance' => $lastClosing ? $lastClosing->final_balance : 0.00,
            'last_date' => $lastClosing ? $lastClosing->closing_date : null
        ]);
    }
}
