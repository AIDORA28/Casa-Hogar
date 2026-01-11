<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProductResource;
use App\Models\ActivityLog;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::paginate(15);
        return ProductResource::collection($products);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'stock' => 'required|integer|min:0',
            'base_price' => 'required|numeric|min:0',
        ]);

        $product = Product::create($validated);

        return response()->json([
            'message' => 'Producto creado exitosamente',
            'product' => new ProductResource($product),
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::findOrFail($id);
        return new ProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);
        $oldData = $product->toArray();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'stock' => 'sometimes|integer|min:0',
            'base_price' => 'sometimes|numeric|min:0',
        ]);

        $product->update($validated);

        // Registrar en activity log
        ActivityLog::log('updated', 'Product', $product->id, [
            'old' => $oldData,
            'new' => $product->toArray(),
        ]);

        return response()->json([
            'message' => 'Producto actualizado exitosamente',
            'product' => new ProductResource($product),
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $productData = $product->toArray();

        // Registrar en activity log ANTES de eliminar
        ActivityLog::log('deleted', 'Product', $product->id, $productData);

        $product->delete();

        return response()->json([
            'message' => 'Producto eliminado exitosamente',
        ], 200);
    }
}
