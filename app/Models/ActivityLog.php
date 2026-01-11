<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'model',
        'model_id',
        'changes',
        'ip_address',
    ];

    protected $casts = [
        'changes' => 'array', // Castear JSON a array
    ];

    // Relación con usuario
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper estático para registrar una acción
     */
    public static function log(string $action, string $model, int $modelId, array $changes = [])
    {
        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'model' => $model,
            'model_id' => $modelId,
            'changes' => $changes,
            'ip_address' => request()->ip(),
        ]);
    }
}
