<?php

namespace App\Http\Controllers;

use App\Models\WasteRecord;
use App\Models\Product;
use Illuminate\Http\Request;

class WasteRecordController extends Controller
{
    /**
     * Listar todas las mermas
     */
    public function index(Request $request)
    {
        $query = WasteRecord::with(['product', 'user']);

        // Filtrar por fecha si se proporciona
        if ($request->has('date')) {
            $query->whereDate('waste_date', $request->date);
        }

        // Filtrar por producto si se proporciona
        if ($request->has('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        $wasteRecords = $query->orderBy('waste_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($wasteRecords, 200);
    }

    /**
     * Registrar nueva merma
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string|max:500',
            'waste_date' => 'required|date',
        ], [
            'product_id.required' => 'Debe seleccionar un producto',
            'product_id.exists' => 'El producto seleccionado no existe',
            'quantity.required' => 'La cantidad es obligatoria',
            'quantity.min' => 'La cantidad debe ser mayor a 0',
            'reason.required' => 'Debe indicar el motivo de la merma',
            'waste_date.required' => 'La fecha es obligatoria',
        ]);

        // Verificar que haya stock disponible
        $product = Product::findOrFail($validated['product_id']);
        
        if ($product->stock < $validated['quantity']) {
            return response()->json([
                'message' => "Stock insuficiente. Disponible: {$product->stock} unidades"
            ], 422);
        }

        // Crear registro de merma (el stock se descuenta automáticamente por el modelo)
        $wasteRecord = WasteRecord::create([
            'product_id' => $validated['product_id'],
            'quantity' => $validated['quantity'],
            'reason' => $validated['reason'],
            'waste_date' => $validated['waste_date'],
            'user_id' => auth()->id(),
        ]);

        // Recargar con relaciones
        $wasteRecord->load(['product', 'user']);

        return response()->json([
            'message' => 'Merma registrada exitosamente',
            'waste_record' => $wasteRecord
        ], 201);
    }

    /**
     * Eliminar registro de merma (restaura el stock)
     */
    public function destroy($id)
    {
        $wasteRecord = WasteRecord::findOrFail($id);
        
        // Al eliminar, el modelo automáticamente restaura el stock
        $wasteRecord->delete();

        return response()->json([
            'message' => 'Registro de merma eliminado y stock restaurado'
        ], 200);
    }
}
