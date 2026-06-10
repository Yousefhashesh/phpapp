<?php

namespace App\Support\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogService
{
    public static function log(
        Model $model,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $label = null,
        ?array $meta = null,
    ): ActivityLog {
        $request = request();
        $user = Auth::user();

        return ActivityLog::create([
            'user_id' => $user?->id,
            'login_session_id' => $user?->loginSessions()?->latest()?->first()?->id,
            'event_type' => class_basename($model),
            'entity_type' => class_basename($model),
            'entity_id' => $model->id,
            'action' => $action,
            'label' => $label ?? "{$action} " . class_basename($model) . " #{$model->id}",
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'meta' => $meta,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    public static function logCreated(Model $model, ?string $label = null, ?array $meta = null): ActivityLog
    {
        return self::log(
            $model,
            'created',
            oldValues: null,
            newValues: $model->getAttributes(),
            label: $label ?? "Created " . class_basename($model),
            meta: $meta,
        );
    }

    public static function logUpdated(
        Model $model,
        array $oldValues,
        array $newValues,
        ?string $label = null,
        ?array $meta = null,
    ): ActivityLog {
        return self::log(
            $model,
            'updated',
            oldValues: $oldValues,
            newValues: $newValues,
            label: $label ?? "Updated " . class_basename($model),
            meta: $meta,
        );
    }

    public static function logDeleted(Model $model, ?string $label = null, ?array $meta = null): ActivityLog
    {
        return self::log(
            $model,
            'deleted',
            oldValues: $model->getAttributes(),
            newValues: null,
            label: $label ?? "Deleted " . class_basename($model),
            meta: $meta,
        );
    }

    public static function logStatusChange(
        Model $model,
        string $field,
        mixed $oldValue,
        mixed $newValue,
        ?array $meta = null,
    ): ActivityLog {
        return self::log(
            $model,
            'status_changed',
            oldValues: [$field => $oldValue],
            newValues: [$field => $newValue],
            label: "Changed {$field}: {$oldValue} → {$newValue}",
            meta: $meta,
        );
    }

    public static function logAction(
        Model $model,
        string $action,
        ?string $label = null,
        ?array $meta = null,
    ): ActivityLog {
        return self::log(
            $model,
            $action,
            oldValues: null,
            newValues: null,
            label: $label ?? "{$action} " . class_basename($model),
            meta: $meta,
        );
    }
}
