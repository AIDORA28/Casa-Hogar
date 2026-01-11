<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyClosingResource extends JsonResource
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
            'closing_date' => $this->closing_date?->format('Y-m-d'),
            'total_sales' => number_format($this->total_sales, 2),
            'total_expenses' => number_format($this->total_expenses, 2),
            'previous_balance' => number_format($this->previous_balance, 2),
            'final_balance' => number_format($this->final_balance, 2),
            'user' => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
