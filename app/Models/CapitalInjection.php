<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CapitalInjection extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'injection_date',
        'amount',
        'reason',
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
