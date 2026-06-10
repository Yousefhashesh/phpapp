<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\ClientReturn;
use App\Models\ClientSettlement;
use App\Models\Order;
use App\Models\ShipperCollection;
use App\Models\ShipperReturn;
use App\Support\Services\ActivityLogService;
use App\Support\Services\WhatsAppService;
use App\Support\Services\WorkflowNotificationService;
use Illuminate\Database\Eloquent\Model;

class EntityObserver
{
    public const IGNORED_COLUMNS = [
        'created_at',
        'updated_at',
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    public function created(Model $model): void
    {
        if ($model instanceof ActivityLog) {
            return;
        }

        ActivityLogService::logCreated($model);
        app(WorkflowNotificationService::class)->handleModelCreated($model);

        if ($model instanceof Order) {
            app(WhatsAppService::class)->notifyOrderChange($model, 'created');
        } elseif ($this->isFinancialWorkflowModel($model)) {
            app(WhatsAppService::class)->notifyWorkflowEvent($model, 'created');
        }
    }

    public function updated(Model $model): void
    {
        if ($model instanceof ActivityLog) {
            return;
        }

        $changes = $model->getChanges();

        if (empty($changes)) {
            return;
        }

        $oldValues = [];
        $newValues = [];

        foreach ($changes as $key => $newValue) {
            if (in_array($key, self::IGNORED_COLUMNS)) {
                continue;
            }

            $oldValue = $model->getOriginal($key);
            $oldValues[$key] = $oldValue;
            $newValues[$key] = $newValue;
        }

        if (empty($oldValues)) {
            return;
        }

        ActivityLogService::logUpdated($model, $oldValues, $newValues);
        app(WorkflowNotificationService::class)->handleModelUpdated($model, $changes);

        if ($model instanceof Order) {
            app(WhatsAppService::class)->notifyOrderChange($model, 'updated', $oldValues, $newValues);
        } elseif ($this->isFinancialWorkflowModel($model)) {
            app(WhatsAppService::class)->notifyWorkflowEvent($model, 'updated', $oldValues, $newValues);
        }
    }

    public function deleted(Model $model): void
    {
        if ($model instanceof ActivityLog) {
            return;
        }

        ActivityLogService::logDeleted($model);

        if ($this->isFinancialWorkflowModel($model)) {
            app(WhatsAppService::class)->notifyWorkflowEvent($model, 'deleted');
        }
    }

    public function restored(Model $model): void
    {
        if ($model instanceof ActivityLog) {
            return;
        }

        ActivityLogService::logAction($model, 'restored', 'Restored '.class_basename($model));
    }

    public function forceDeleted(Model $model): void
    {
        if ($model instanceof ActivityLog) {
            return;
        }

        ActivityLogService::logAction($model, 'force_deleted', 'Permanently deleted '.class_basename($model));
    }

    private function isFinancialWorkflowModel(Model $model): bool
    {
        return $model instanceof ShipperCollection
            || $model instanceof ClientSettlement
            || $model instanceof ShipperReturn
            || $model instanceof ClientReturn;
    }
}
