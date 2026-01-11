<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['name', 'description'];

    /**
     * Relación con usuarios que tienen este permiso
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_permissions');
    }
}
