<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Nurse extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'is_active',
    ];

    protected $dates = ['deleted_at'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Scope para obtener solo enfermeras activas (no eliminadas y activas)
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->whereNull('deleted_at');
    }

    /**
     * Relación: Una enfermera puede tener muchas ventas
     */
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
