<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'action',
        'actor_name',
        'ip_address',
        'user_agent',
        'details',
    ];

    public static function log(string $action, ?string $details = null): void
    {
        try {
            self::create([
                'action'     => $action,
                'actor_name' => 'Super Admin',
                'ip_address' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 255),
                'details'    => $details,
            ]);
        } catch (\Throwable $e) {
            // Failsafe
        }
    }
}
