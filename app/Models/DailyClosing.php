<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DailyClosing extends Model
{
    // Atributos que se pueden asignar masivamente
    protected $fillable = [
        'user_id',
        'user_name',
        'closing_date',
        'total_sales',
        'total_expenses',
        'previous_balance',
        'final_balance',
    ];

    // Cast de tipos
    protected $casts = [
        'closing_date' => 'date',
    ];

    // Relaciones
    /**
     * Relación muchos a uno: Un cierre diario pertenece a un usuario (tesorero que hizo el cierre)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
