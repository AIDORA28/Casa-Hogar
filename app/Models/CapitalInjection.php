<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CapitalInjection extends Model
{
    protected $fillable = [
        'injection_date',
        'amount',
        'reason',
        'user_id'
    ];

    protected $casts = [
        'injection_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Relación con el usuario que registró la inyección
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
