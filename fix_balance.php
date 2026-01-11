import Illuminate\Support\Facades\DB;
import App\Models\Sale;
import App\Models\Expense;
import App\Models\DailyClosing;

$today = now()->format('Y-m-d');
$totalSales = Sale::whereDate('sale_date', $today)->sum('total_amount');
$totalExpenses = Expense::whereDate('expense_date', $today)->sum('amount');

$existing = DailyClosing::where('closing_date', $today)->first();

if ($existing) {
    $existing->update([
        'total_sales' => $totalSales,
        'total_expenses' => $totalExpenses,
        'final_balance' => $totalSales - $totalExpenses
    ]);
    dump('Cierre actualizado');
} else {
    DailyClosing::create([
        'closing_date' => $today,
        'total_sales' => $totalSales,
        'total_expenses' => $totalExpenses,
        'previous_balance' => 0,
        'final_balance' => $totalSales - $totalExpenses,
        'user_id' => 1
    ]);
    dump('Cierre creado');
}

$last = DailyClosing::orderBy('closing_date', 'desc')->first();
dump("Saldo final: S/ " . $last->final_balance);
