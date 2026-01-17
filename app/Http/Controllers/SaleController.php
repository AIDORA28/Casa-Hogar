<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Http\Resources\SaleResource;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Helpers\UserHelper; // Added this line
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Listar ventas con usuario y items
        $sales = Sale::with(['user', 'saleItems.product'])
            ->orderBy('sale_date', 'desc')
            ->paginate(15);

        return SaleResource::collection($sales);
    }

    /**
     * Store a newly created resource in storage.
     * Implementa transacciones DB para garantizar integridad referencial
     */
    public function store(StoreSaleRequest $request)
    {
        try {
            // Iniciar transacción DB
            DB::beginTransaction();

            $validated = $request->validated();
            $totalAmount = 0;

            // Calcular el total de la venta
            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $subtotal = $product->base_price * $item['quantity'];
                $totalAmount += $subtotal;
            }

            // Crear el registro de venta
            $sale = Sale::create([
                'user_id' => auth()->id(), // Usuario autenticado (tesorero)
                'user_name' => UserHelper::getFormattedName(auth()->user()),
                'nurse_id' => $validated['nurse_id'] ?? null, // Enfermera responsable
                'sale_date' => $validated['sale_date'],
                'total_amount' => $totalAmount,
            ]);

            // Procesar cada item de la venta
            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $quantity = $item['quantity'];
                $unitPrice = $product->base_price;
                $subtotal = $unitPrice * $quantity;

                // Crear el item de venta
                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);

                // CRÍTICO: Decrementar el stock del producto
                $product->stock -= $quantity;
                $product->save();

                Log::info("Stock actualizado para {$product->name}: {$product->stock} unidades restantes");
            }

            // Commit de la transacción si todo salió bien
            DB::commit();

            // Cargar las relaciones para la respuesta
            $sale->load(['user', 'saleItems.product']);

            return response()->json([
                'message' => 'Venta registrada exitosamente',
                'sale' => new SaleResource($sale),
            ], 201);

        } catch (\Exception $e) {
            // Rollback en caso de error
            DB::rollBack();

            Log::error('Error al registrar venta: ' . $e->getMessage());

            return response()->json([
                'message' => 'Error al registrar la venta',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $sale = Sale::with(['user', 'saleItems.product'])->findOrFail($id);
        return new SaleResource($sale);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        return response()->json([
            'message' => 'Las ventas no pueden ser modificadas por motivos de auditoría',
        ], 403);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        return response()->json([
            'message' => 'Las ventas no pueden ser eliminadas por motivos de auditoría',
        ], 403);
    }
}
