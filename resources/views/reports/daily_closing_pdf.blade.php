<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Cierre - {{ $date }}</title>
    <style>
        @page { margin: 2cm; }
        body { 
            font-family: 'Helvetica', 'Arial', sans-serif; 
            font-size: 11px; 
            color: #334155;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        
        /* Header */
        .header { 
            border-bottom: 2px solid #1e293b;
            padding-bottom: 15px;
            margin-bottom: 30px;
        }
        .header-table { width: 100%; border: none; }
        .brand { font-size: 24px; font-weight: 900; color: #1e293b; letter-spacing: -1px; }
        .report-type { 
            background: #1e293b; 
            color: white; 
            padding: 5px 12px; 
            font-size: 10px; 
            font-weight: bold; 
            display: inline-block;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .meta-text { text-align: right; font-size: 9px; color: #64748b; }
        .meta-value { color: #1e293b; font-weight: bold; }

        /* Summary Card */
        .exec-summary { 
            background: #f8fafc; 
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .summary-title { 
            font-size: 12px; 
            font-weight: 800; 
            color: #475569; 
            margin-bottom: 15px; 
            text-transform: uppercase;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
        }
        .grid { width: 100%; }
        .grid td { width: 25%; vertical-align: top; border: none; }
        .stat-label { font-size: 9px; color: #94a3b8; font-weight: bold; text-transform: uppercase; }
        .stat-value { font-size: 16px; font-weight: 800; color: #1e293b; }
        .stat-final { color: #2563eb; }

        /* Tables */
        .section-header { 
            font-size: 12px; 
            font-weight: 800; 
            color: #1e293b; 
            margin: 25px 0 10px 0;
            display: block;
            border-left: 4px solid #1e293b;
            padding-left: 10px;
        }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; border-radius: 6px; overflow: hidden; }
        th { 
            background: #f1f5f9; 
            color: #475569; 
            font-weight: bold; 
            font-size: 9px; 
            text-transform: uppercase; 
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        td { padding: 10px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tr:nth-child(even) { background-color: #f8fafc; }
        
        .badge {
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-green { background: #dcfce7; color: #166534; }
        .badge-red { background: #fee2e2; color: #991b1b; }
        .badge-blue { background: #dbeafe; color: #1e40af; }

        /* Total Row */
        .row-total td { 
            background: #f8fafc !important; 
            font-weight: 800; 
            border-top: 2px solid #e2e8f0;
            color: #1e293b;
        }
        
        /* Footer */
        .certification { 
            margin-top: 60px;
            width: 100%;
        }
        .signature-box { 
            width: 250px; 
            border-top: 1px solid #334155; 
            text-align: center; 
            padding-top: 8px;
            margin-top: 40px;
        }
        .footer-info { 
            text-align: center; 
            margin-top: 40px; 
            font-size: 8px; 
            color: #94a3b8; 
            border-top: 1px solid #f1f5f9;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="border:none;">
                    <span class="brand">CASA HOGAR</span><br>
                    <div class="report-type">Cierre de Caja Operativo</div>
                </td>
                <td style="text-align: right; border:none;" class="meta-text">
                    FECHA DE OPERACIÓN: <span class="meta-value">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</span><br>
                    GENERADO POR: <span class="meta-value">{{ $closing->user_name ?? ($closing->user ? $closing->user->name : 'N/A') }}</span><br>
                    EMISIÓN: <span class="meta-value">{{ now()->format('d/m/Y H:i') }}</span>
                </td>
            </tr>
        </table>
    </div>

    <div class="exec-summary">
        <div class="summary-title">Resumen de Liquidación</div>
        <table class="grid">
            <tr>
                <td style="border:none;">
                    <div class="stat-label">Ingresos Brutos</div>
                    <div class="stat-value">S/ {{ number_format($closing->total_sales, 2) }}</div>
                </td>
                <td style="border:none;">
                    <div class="stat-label">Egresos / Gastos</div>
                    <div class="stat-value" style="color: #ef4444;">S/ {{ number_format($closing->total_expenses, 2) }}</div>
                </td>
                <td style="border:none;">
                    <div class="stat-label">Arrastre Anterior</div>
                    <div class="stat-value">S/ {{ number_format($closing->previous_balance, 2) }}</div>
                </td>
                <td style="border:none;">
                    <div class="stat-label">Saldo en Caja</div>
                    <div class="stat-value stat-final">S/ {{ number_format($closing->final_balance, 2) }}</div>
                </td>
            </tr>
        </table>
    </div>

    @if($includeSales)
    <div class="section-header">DETALLE DE INGRESOS (VENTAS)</div>
    <table>
        <thead>
            <tr>
                <th style="width: 10%;">ID VENTA</th>
                <th style="width: 10%;">HORA</th>
                <th style="width: 20%;">TESORERÍA</th>
                <th style="width: 40%;">PRODUCTOS Y DETALLE</th>
                <th style="width: 20%; text-align: right;">SUBTOTAL</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sales as $sale)
            <tr>
                <td style="font-weight: bold;">#{{ $sale->id }}</td>
                <td>{{ $sale->created_at->format('H:i') }}</td>
                <td>{{ $sale->user_name ?? ($sale->user ? $sale->user->name : 'N/A') }}</td>
                <td style="font-size: 10px;">
                    @foreach($sale->saleItems as $item)
                        <div style="margin-bottom: 2px;">• {{ $item->product->name }} <span style="color: #64748b;">(x{{ $item->quantity }})</span></div>
                    @endforeach
                    <div class="badge badge-blue" style="margin-top: 5px;">Personal: {{ $sale->nurse ? $sale->nurse->name : 'N/A' }}</div>
                </td>
                <td style="text-align: right; font-weight: bold;">S/ {{ number_format($sale->total_amount, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align: center; color: #94a3b8; padding: 20px;">No se registraron ventas en este periodo</td>
            </tr>
            @endforelse
        </tbody>
        @if($sales->count() > 0)
        <tfoot>
            <tr class="row-total">
                <td colspan="4" style="text-align: right;">TOTAL INGRESOS VENTAS:</td>
                <td style="text-align: right;">S/ {{ number_format($sales->sum('total_amount'), 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
    @endif

    @if($includeExpenses)
    <div class="section-header">DETALLE DE EGRESOS (GASTOS OPERATIVOS)</div>
    <table>
        <thead>
            <tr>
                <th style="width: 10%;">ID</th>
                <th style="width: 10%;">HORA</th>
                <th style="width: 60%;">CONCEPTO / DESCRIPCIÓN</th>
                <th style="width: 20%; text-align: right;">IMPORTE</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenses as $expense)
            <tr>
                <td>#{{ $expense->id }}</td>
                <td>{{ $expense->created_at->format('H:i') }}</td>
                <td>{{ $expense->description }}</td>
                <td style="text-align: right; font-weight: bold; color: #ef4444;">S/ {{ number_format($expense->amount, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" style="text-align: center; color: #94a3b8; padding: 20px;">No se registraron gastos operativos</td>
            </tr>
            @endforelse
        </tbody>
        @if($expenses->count() > 0)
        <tfoot>
            <tr class="row-total">
                <td colspan="3" style="text-align: right;">TOTAL EGRESOS:</td>
                <td style="text-align: right; color: #ef4444;">S/ {{ number_format($expenses->sum('amount'), 2) }}</td>
            </tr>
        </tfoot>
        @endif
    </table>
    @endif

    @if($includeInjections && $injections->count() > 0)
    <div class="section-header">INYECCIONES DE CAPITAL EXCEPCIONALES</div>
    <table>
        <thead>
            <tr>
                <th style="width: 10%;">ID</th>
                <th style="width: 70%;">MOTIVO / JUSTIFICACIÓN</th>
                <th style="width: 20%; text-align: right;">MONTO</th>
            </tr>
        </thead>
        <tbody>
            @foreach($injections as $injection)
            <tr>
                <td>#{{ $injection->id }}</td>
                <td>{{ $injection->reason }}</td>
                <td style="text-align: right; font-weight: bold; color: #0891b2;">S/ {{ number_format($injection->amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="row-total" style="color: #0891b2;">
                <td colspan="2" style="text-align: right;">TOTAL INYECCIONES:</td>
                <td style="text-align: right;">S/ {{ number_format($injections->sum('amount'), 2) }}</td>
            </tr>
        </tfoot>
    </table>
    @endif

    <div class="certification">
        <table style="width: 100%; border:none;">
            <tr>
                <td style="border:none;">
                    <div class="signature-box">
                        <span style="font-size: 9px; font-weight: bold;">FIRMA RESPONSABLE TESORERÍA</span><br>
                        <span style="font-size: 8px; color: #64748b;">DNI / IDENTIFICACIÓN: ______________</span>
                    </div>
                </td>
                <td style="text-align: right; vertical-align: bottom; border:none; font-size: 9px; color: #64748b;">
                    ESTE DOCUMENTO ES UNA CONSTANCIA OFICIAL DEL SISTEMA CASA HOGAR.<br>
                    AUTENTICADO MEDIANTE LOG DE AUDITORÍA INTERNA.
                </td>
            </tr>
        </table>
    </div>

    <div class="footer-info">
        <p>Casa Hogar v2.0 - Reporte de Cumplimiento de Operaciones Diarias</p>
        <p>© {{ date('Y') }} - Generado de forma automática por el sistema de gestión interna.</p>
    </div>
</body>
</html>
