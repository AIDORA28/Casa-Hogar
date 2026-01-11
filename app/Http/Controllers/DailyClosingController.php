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
        try {
            \Log::info('GET Last Balance - Iniciando');
            
            // Usar Eloquent que maneja PostgreSQL automáticamente
            $lastClosing = DailyClosing::orderBy('closing_date', 'desc')->first();
            
            \Log::info('Query ejecutado - Resultado: ' . ($lastClosing ? json_encode($lastClosing->toArray()) : 'null'));
            
            if ($lastClosing) {
                $balance = (float) $lastClosing->final_balance;
                \Log::info('Saldo encontrado: ' . $balance);
                
                return response()->json([
                    'last_balance' => $balance,
                    'last_date' => $lastClosing->closing_date
                ], 200);
            }
            
            \Log::info('No hay cierres - Retornando 0.00');
            return response()->json([
                'last_balance' => 0.00,
                'last_date' => null
            ], 200);
            
        } catch (\Exception $e) {
            \Log::error('ERROR CRÍTICO en getLastBalance');
            \Log::error('Mensaje: ' . $e->getMessage());
            \Log::error('Archivo: ' . $e->getFile() . ':' . $e->getLine());
            \Log::error('Trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'last_balance' => 0.00,
                'last_date' => null,
                'error' => $e->getMessage()
            ], 200); // Retornar 200 con valor 0 en lugar de 500
        }
    }
}
