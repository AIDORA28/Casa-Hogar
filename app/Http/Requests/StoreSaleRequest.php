<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Permitir acceso (se puede agregar lógica de autorización aquí)
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sale_date' => 'required|date',
            'nurse_id' => 'nullable|exists:nurses,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ];
    }

    /**
     * Validación adicional después de las reglas básicas
     * Verifica que haya stock suficiente para cada producto
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $items = $this->input('items', []);
            
            foreach ($items as $index => $item) {
                $product = Product::find($item['product_id'] ?? null);
                
                if ($product) {
                    $requestedQuantity = $item['quantity'] ?? 0;
                    
                    // Validación crítica: verificar stock disponible
                    if ($product->stock < $requestedQuantity) {
                        $validator->errors()->add(
                            "items.{$index}.quantity",
                            "Stock insuficiente para {$product->name}. Disponible: {$product->stock}, Solicitado: {$requestedQuantity}"
                        );
                    }
                }
            }
        });
    }

    /**
     * Mensajes de error personalizados en español
     */
    public function messages(): array
    {
        return [
            'sale_date.required' => 'La fecha de venta es obligatoria',
            'sale_date.date' => 'La fecha de venta debe ser una fecha válida',
            'items.required' => 'Debe incluir al menos un producto en la venta',
            'items.array' => 'Los items deben ser un array',
            'items.min' => 'Debe incluir al menos un producto',
            'items.*.product_id.required' => 'El ID del producto es obligatorio',
            'items.*.product_id.exists' => 'El producto seleccionado no existe',
            'items.*.quantity.required' => 'La cantidad es obligatoria',
            'items.*.quantity.integer' => 'La cantidad debe ser un número entero',
            'items.*.quantity.min' => 'La cantidad debe ser al menos 1',
        ];
    }
}
