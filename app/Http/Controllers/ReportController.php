<?php

namespace App\Http\Controllers;

use App\Http\Resources\ReportResource;
use App\Models\DailyClosing;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * Dashboard: Estadísticas del mes actual
     */
    public function getDashboardStats()
    {
        $currentMonth = now()->month;
        $currentYear = now()->year;

        // Total ventas del mes
        $totalSales = Sale::whereYear('sale_date', $currentYear)
            ->whereMonth('sale_date', $currentMonth)
            ->sum('total_amount');

        // Total gastos del mes
        $totalExpenses = Expense::whereYear('expense_date', $currentYear)
            ->whereMonth('expense_date', $currentMonth)
            ->sum('amount');

        // Ganancia neta
        $netProfit = $totalSales - $totalExpenses;

        // Top 5 productos más vendidos
        $topProducts = Product::select('products.id', 'products.name', DB::raw('SUM(sale_items.quantity) as total_sold'))
            ->join('sale_items', 'products.id', '=', 'sale_items.product_id')
            ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
            ->whereYear('sales.sale_date', $currentYear)
            ->whereMonth('sales.sale_date', $currentMonth)
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_sold', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'period' => now()->format('F Y'),
            'total_sales' => number_format($totalSales, 2),
            'total_expenses' => number_format($totalExpenses, 2),
            'net_profit' => number_format($netProfit, 2),
            'top_products' => $topProducts->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'total_sold' => $product->total_sold,
                ];
            }),
        ], 200);
    }

    /**
     * Reporte de ventas por rango de fechas
     */
    public function getSalesByDateRange(Request $request)
    {
        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ], [
            'start_date.required' => 'La fecha de inicio es obligatoria',
            'end_date.required' => 'La fecha final es obligatoria',
            'end_date.after_or_equal' => 'La fecha final debe ser posterior o igual a la fecha de inicio',
        ]);

        $sales = Sale::with(['saleItems.product', 'user'])
            ->whereBetween('sale_date', [$validated['start_date'], $validated['end_date']])
            ->orderBy('sale_date', 'desc')
            ->get();

        $totalAmount = $sales->sum('total_amount');
        $totalTransactions = $sales->count();

        return response()->json([
            'period' => [
                'start' => $validated['start_date'],
                'end' => $validated['end_date'],
            ],
            'summary' => [
                'total_amount' => number_format($totalAmount, 2),
                'total_transactions' => $totalTransactions,
                'average_ticket' => $totalTransactions > 0 ? number_format($totalAmount / $totalTransactions, 2) : '0.00',
            ],
            'sales' => $sales->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'date' => $sale->sale_date->format('Y-m-d'),
                    'user' => $sale->user->name,
                    'total' => number_format($sale->total_amount, 2),
                    'items_count' => $sale->saleItems->count(),
                ];
            }),
        ], 200);
    }

    /**
     * Generar PDF del cierre de caja diario (on-demand)
     */
    public function generateDailyClosingPDF(Request $request, string $date)
    {
        // Leer parámetros opcionales para incluir secciones
        $includeSales = $request->query('include_sales', '1') === '1';
        $includeExpenses = $request->query('include_expenses', '1') === '1';
        $includeInjections = $request->query('include_injections', '1') === '1';
        $includeWaste = $request->query('include_waste', '1') === '1';
        
        // Buscar cierre existente
        $closing = DailyClosing::with('user')->where('closing_date', $date)->first();
        
        // Si no existe, calcularlo y crearlo automáticamente
        if (!$closing) {
            $sales = Sale::whereDate('sale_date', $date)->get();
            $expenses = Expense::whereDate('expense_date', $date)->get();
            $injections = \App\Models\CapitalInjection::whereDate('injection_date', $date)->get();
            
            $totalSales = $sales->sum('total_amount');
            $totalExpenses = $expenses->sum('amount');
            $totalInjections = $injections->sum('amount');
            
            // Obtener balance anterior
            $previousClosing = DailyClosing::where('closing_date', '<', $date)
                ->orderBy('closing_date', 'desc')
                ->first();
            
            $previousBalance = $previousClosing ? $previousClosing->final_balance : 0;
            
            // Crear cierre automáticamente
            $closing = DailyClosing::create([
                'closing_date' => $date,
                'total_sales' => $totalSales,
                'total_expenses' => $totalExpenses,
                'previous_balance' => $previousBalance,
                'final_balance' => ($totalSales - $totalExpenses + $totalInjections) + $previousBalance,
                'user_id' => auth()->id()
            ]);
        }

        // Obtener ventas del día con enfermera
        $sales = Sale::with(['saleItems.product', 'user', 'nurse'])
            ->whereDate('sale_date', $date)
            ->get();

        // Obtener gastos del día
        $expenses = Expense::with('user')
            ->whereDate('expense_date', $date)
            ->get();

        // Obtener inyecciones de capital
        $injections = \App\Models\CapitalInjection::with('user')
            ->whereDate('injection_date', $date)
            ->get();

        // Obtener mermas del día
        $wasteRecords = \App\Models\WasteRecord::with('product')
            ->whereDate('waste_date', $date)
            ->get();

        $data = [
            'closing' => $closing,
            'sales' => $sales,
            'expenses' => $expenses,
            'injections' => $injections,
            'wasteRecords' => $wasteRecords,
            'date' => $date,
            'includeSales' => $includeSales,
            'includeExpenses' => $includeExpenses,
            'includeInjections' => $includeInjections,
            'includeWaste' => $includeWaste,
        ];

        $pdf = Pdf::loadView('reports.daily_closing_pdf', $data);
        
        return $pdf->download("cierre_caja_{$date}.pdf");
    }

    /**
     * Obtener logs de actividad (auditoría)
     */
    public function getActivityLogs(Request $request)
    {
        $logs = \App\Models\ActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($logs, 200);
    }

    /**
     * Obtener desglose detallado de un día específico
     */
    public function getDailyDetail($date)
    {
        // Ventas con detalles de productos y enfermera responsable
        $sales = Sale::whereDate('sale_date', $date)
            ->with(['saleItems.product', 'user', 'nurse'])
            ->orderBy('created_at', 'asc')
            ->get();

        // Gastos
        $expenses = Expense::whereDate('expense_date', $date)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        // Inyecciones de capital
        $injections = \App\Models\CapitalInjection::whereDate('injection_date', $date)
            ->with('user')
            ->orderBy('created_at', 'asc')
            ->get();

        // Mermas/Bajas de inventario
        $wasteRecords = \App\Models\WasteRecord::whereDate('waste_date', $date)
            ->with('product')
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json([
            'date' => $date,
            'sales' => $sales->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'user' => $sale->user_name ?? ($sale->user ? $sale->user->name : 'Usuario eliminado'),
                    'nurse' => $sale->nurse ? $sale->nurse->name : 'No especificado',
                    'total' => number_format($sale->total_amount, 2),
                    'items' => $sale->saleItems->map(function ($item) {
                        return [
                            'product' => $item->product->name,
                            'quantity' => $item->quantity,
                            'unit_price' => number_format($item->unit_price, 2),
                            'subtotal' => number_format($item->subtotal, 2),
                        ];
                    }),
                    'created_at' => $sale->created_at->format('H:i:s')
                ];
            }),
            'expenses' => $expenses->map(function ($expense) {
                return [
                    'id' => $expense->id,
                    'user' => $expense->user_name ?? ($expense->user ? $expense->user->name : 'Usuario eliminado'),
                    'amount' => number_format($expense->amount, 2),
                    'description' => $expense->description,
                    'created_at' => $expense->created_at->format('H:i:s')
                ];
            }),
            'injections' => $injections->map(function ($injection) {
                return [
                    'id' => $injection->id,
                    'user' => $injection->user_name ?? ($injection->user ? $injection->user->name : 'Usuario eliminado'),
                    'amount' => number_format($injection->amount, 2),
                    'reason' => $injection->reason,
                    'created_at' => $injection->created_at->format('H:i:s')
                ];
            }),
            'waste_records' => $wasteRecords->map(function ($waste) {
                return [
                    'id' => $waste->id,
                    'product' => $waste->product->name,
                    'quantity' => $waste->quantity,
                    'reason' => $waste->reason,
                    'created_at' => $waste->created_at->format('H:i:s')
                ];
            }),
            'summary' => [
                'total_sales' => number_format($sales->sum('total_amount'), 2),
                'total_expenses' => number_format($expenses->sum('amount'), 2),
                'total_injections' => number_format($injections->sum('amount'), 2),
                'net_balance' => number_format(
                    $sales->sum('total_amount') - $expenses->sum('amount') + $injections->sum('amount'), 
                    2
                ),
            ]
        ]);
    }
}
