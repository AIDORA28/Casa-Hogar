<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SaleResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'role' => $this->user?->role,
            ],
            'sale_date' => $this->sale_date?->format('Y-m-d'),
            'total_amount' => number_format($this->total_amount, 2),
            'items' => SaleItemResource::collection($this->whenLoaded('saleItems')),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
