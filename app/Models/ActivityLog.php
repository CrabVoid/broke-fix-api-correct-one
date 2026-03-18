<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_type',
        'action',
        'entity_type',
        'entity_id',
        'ip_address',
        'user_agent',
        'method',
        'url',
        'properties',
    ];

    protected $casts = [
        'properties' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function entity(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Log an action
     */
    public static function log(
        string $action,
        ?Model $entity = null,
        ?User $user = null,
        array $properties = []
    ): self {
        return self::create([
            'user_id' => $user?->id,
            'user_type' => $user ? get_class($user) : null,
            'action' => $action,
            'entity_type' => $entity ? get_class($entity) : null,
            'entity_id' => $entity?->id,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'method' => request()?->method(),
            'url' => request()?->fullUrl(),
            'properties' => $properties,
        ]);
    }

    /**
     * Log with old and new values
     */
    public static function logChange(
        string $action,
        Model $entity,
        ?User $user = null,
        ?array $old = null,
        ?array $new = null
    ): self {
        $properties = [];
        
        if ($old !== null) {
            $properties['old'] = $old;
        }
        
        if ($new !== null) {
            $properties['new'] = $new;
        }

        return self::log($action, $entity, $user, $properties);
    }
}
