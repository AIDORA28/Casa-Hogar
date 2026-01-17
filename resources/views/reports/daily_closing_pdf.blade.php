<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cierre de Caja - {{ $date }}</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .header h1 { margin: 0; color: #2c3e50; }
        .header p { margin: 5px 0; color: #7f8c8d; }
        .summary { background: #ecf0f1; padding: 15px; margin: 20px 0; border-radius: 5px; }
        .summary-item { display: inline-block; width: 48%; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th { background: #34495e; color: white; padding: 8px; text-align: left; }
        td { padding: 8px; border-bottom: 1px solid #ddd; }
        .section-title { background: #3498db; color: white; padding: 10px; margin-top: 20px; font-weight: bold; }
        .total { font-weight: bold; background: #f39c12; color: white; }
        .footer { text-align: center; margin-top: 30px; font-size: 10px; color: #95a5a6; }
    </style>
</head>
<body>
    <div class="header">
        <h1>CASA HOGAR</h1>
        <p>Cierre de Caja Diario</p>
        <p>Fecha: {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</p>
        <p>Generado por: {{ $closing->user_name ?? ($closing->user ? $closing->user->name : 'Usuario eliminado') }}</p>
    </div>

    <div class="summary">
        <div class="summary-item"><strong>Total Ventas:</strong> S/ {{ number_format($closing->total_sales, 2) }}</div>
        <div class="summary-item"><strong>Total Gastos:</strong> S/ {{ number_format($closing->total_expenses, 2) }}</div>
        <div class="summary-item"><strong>Saldo Anterior:</strong> S/ {{ number_format($closing->previous_balance, 2) }}</div>
        <div class="summary-item"><strong>Saldo Final:</strong> S/ {{ number_format($closing->final_balance, 2) }}</div>
    </div>

    @if($includeSales)
    <div class="section-title">DETALLE DE VENTAS</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Hora</th>
                <th>Tesorero</th>
                <th>Enfermera</th>
                <th>Productos</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $sale)
            <tr>
                <td>{{ $sale->id }}</td>
                <td>{{ $sale->created_at->format('H:i') }}</td>
                <td>{{ $sale->user_name ?? ($sale->user ? $sale->user->name : 'Usuario eliminado') }}</td>
                <td>{{ $sale->nurse ? $sale->nurse->name : 'N/A' }}</td>
                <td>
                    @foreach($sale->saleItems as $item)
                        {{ $item->product->name }} (x{{ $item->quantity }})<br>
                    @endforeach
                </td>
                <td>S/ {{ number_format($sale->total_amount, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="6" style="text-align: center; color: #95a5a6;">No hay ventas registradas</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total">
                <td colspan="5" style="text-align: right;">TOTAL VENTAS:</td>
                <td>S/ {{ number_format($sales->sum('total_amount'), 2) }}</td>
            </tr>
        </tfoot>
    </table>
    @endif

    @if($includeExpenses)
    <div class="section-title">DETALLE DE GASTOS</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Hora</th>
                <th>Registrado por</th>
                <th>Descripción</th>
                <th>Monto</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenses as $expense)
            <tr>
                <td>{{ $expense->id }}</td>
                <td>{{ $expense->created_at->format('H:i') }}</td>
                <td>{{ $expense->user_name ?? ($expense->user ? $expense->user->name : 'Usuario eliminado') }}</td>
                <td>{{ $expense->description }}</td>
                <td>S/ {{ number_format($expense->amount, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; color: #95a5a6;">No hay gastos registrados</td>
            </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr class="total">
                <td colspan="4" style="text-align: right;">TOTAL GASTOS:</td>
                <td>S/ {{ number_format($expenses->sum('amount'), 2) }}</td>
            </tr>
        </tfoot>
    </table>
    @endif

    @if($includeInjections && $injections->count() > 0)
    <!-- SECCIÓN DE INYECCIONES DE CAPITAL -->
    <div class="section-title" style="background: #27ae60;">💰 INYECCIONES DE CAPITAL</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Hora</th>
                <th>Registrado por</th>
                <th>Motivo</th>
                <th>Monto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($injections as $injection)
            <tr>
                <td>{{ $injection->id }}</td>
                <td>{{ $injection->created_at->format('H:i') }}</td>
                <td>{{ $injection->user_name ?? ($injection->user ? $injection->user->name : 'Usuario eliminado') }}</td>
                <td>{{ $injection->reason }}</td>
                <td>S/ {{ number_format($injection->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="total" style="background: #27ae60;">
                <td colspan="4" style="text-align: right;">TOTAL INYECCIONES:</td>
                <td>S/ {{ number_format($injections->sum('amount'), 2) }}</td>
            </tr>
        </tfoot>
    </table>
    @elseif($includeInjections)
    <!-- Mensaje cuando se solicitó incluir pero no hay inyecciones -->
    <div class="section-title" style="background: #95a5a6;">💰 INYECCIONES DE CAPITAL</div>
    <p style="text-align: center; color: #7f8c8d; padding: 15px;">No hay inyecciones de capital registradas en esta fecha.</p>
    @endif

    <!-- SECCIÓN DE MERMAS/BAJAS DE INVENTARIO -->
    @if($includeWaste && $wasteRecords->count() > 0)
    <div class="section-title" style="background: #e74c3c;">📉 MERMAS / BAJAS DE INVENTARIO</div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Hora</th>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Motivo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($wasteRecords as $waste)
            <tr>
                <td>{{ $waste->id }}</td>
                <td>{{ $waste->created_at->format('H:i') }}</td>
                <td>{{ $waste->product->name }}</td>
                <td>{{ $waste->quantity }}</td>
                <td>{{ $waste->reason }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <p style="font-size: 10px; color: #7f8c8d; margin-top: 10px;">
        <strong>Nota:</strong> Las mermas/bajas NO afectan el balance de caja. Solo registran la disminución de inventario.
    </p>
    @endif

    <div class="footer">
        <p>Documento generado el {{ now()->format('d/m/Y H:i:s') }}</p>
        <p>Sistema Casa Hogar - Control de Ventas y Gastos</p>
    </div>
</body>
</html>
