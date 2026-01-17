<?php

namespace App\Helpers;

use App\Models\User;

class UserHelper
{
    /**
     * Obtener nombre formateado con prefijo de rol
     * Admin: "Admin - Joe García"
     * Tesorero: "María López"
     */
    public static function getFormattedName(User $user): string
    {
        if ($user->role === 'admin') {
            return "Admin - {$user->name}";
        }
        return $user->name;
    }
}
