<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Log a user action
     */
    public static function log(
        string $action,
        ?Model $entity = null,
        array $properties = []
    ): ActivityLog {
        return ActivityLog::log(
            $action,
            $entity,
            Auth::user(),
            $properties
        );
    }

    /**
     * Log resource creation
     */
    public static function created(Model $entity, array $attributes = []): ActivityLog
    {
        return self::log('created', $entity, $attributes);
    }

    /**
     * Log resource update
     */
    public static function updated(
        Model $entity,
        ?array $old = null,
        ?array $new = null
    ): ActivityLog {
        return ActivityLog::logChange(
            'updated',
            $entity,
            Auth::user(),
            $old,
            $new
        );
    }

    /**
     * Log resource deletion
     */
    public static function deleted(Model $entity, array $properties = []): ActivityLog
    {
        return self::log('deleted', $entity, $properties);
    }

    /**
     * Log login
     */
    public static function login(array $properties = []): ActivityLog
    {
        return self::log('login', null, $properties);
    }

    /**
     * Log logout
     */
    public static function logout(): ActivityLog
    {
        return self::log('logout');
    }
}
