<?php

namespace App\Support\Services;

use App\Models\Client;
use App\Models\ClientReturn;
use App\Models\ClientSettlement;
use App\Models\Order;
use App\Models\Plan;
use App\Models\Setting;
use App\Models\ShipperCollection;
use App\Models\ShipperReturn;
use App\Models\User;
use App\Notifications\WorkflowEventNotification;
use Illuminate\Database\Eloquent\Model;

class WorkflowNotificationService
{
    /**
     * @param  array<string, mixed>  $changes
     */
    public function handleModelUpdated(Model $model, array $changes): void
    {
        if ($model instanceof Order && array_key_exists('status', $changes)) {
            $this->notifyOrderStatusChanged($model, (string) $model->getOriginal('status'), (string) $model->status);

            return;
        }

        if ($model instanceof Client && array_key_exists('plan_id', $changes)) {
            $this->notifyClientPlanChanged($model, $model->getOriginal('plan_id'), $model->plan_id);

            return;
        }

        if ($this->isApprovalWorkflowModel($model) && array_key_exists('approval_status', $changes)) {
            $this->notifyWorkflowApprovalChanged($model);

            return;
        }

        if ($model instanceof ShipperCollection && array_key_exists('status', $changes) && $model->status === 'COMPLETED') {
            $this->notifyCollectionCompleted($model);

            return;
        }

        if ($model instanceof ClientSettlement && array_key_exists('status', $changes) && $model->status === 'COMPLETED') {
            $this->notifySettlementCompleted($model);

            return;
        }

        if ($model instanceof ShipperReturn && array_key_exists('status', $changes) && $model->status === 'COMPLETED') {
            $this->notifyShipperReturnCompleted($model);

            return;
        }

        if ($model instanceof ClientReturn && array_key_exists('status', $changes) && $model->status === 'COMPLETED') {
            $this->notifyClientReturnCompleted($model);
        }
    }

    public function handleModelCreated(Model $model): void
    {
        if ($model instanceof Order) {
            $this->autoUpgradeClientPlanIfThresholdExceeded($model);

            return;
        }

        if ($this->isApprovalWorkflowModel($model)) {
            $this->notifyWorkflowCreated($model);
        }
    }

    private function notifyOrderStatusChanged(Order $order, string $oldStatus, string $newStatus): void
    {
        if ($oldStatus === $newStatus || $newStatus === '') {
            return;
        }

        if ($this->shouldSkipOrderStatusDatabaseNotification()) {
            return;
        }

        $payload = [
            'type' => 'order.status.changed',
            'title' => 'تم تحديث حالة الطلب',
            'message' => "تم تغيير حالة الطلب {$order->code} من {$oldStatus} إلى {$newStatus}.",
            'order_id' => $order->id,
            'order_code' => $order->code,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
        ];

        $this->notifyUsersByIds([
            $order->client_user_id,
            $order->shipper_user_id,
        ], $payload);
    }

    private function notifyCollectionCompleted(ShipperCollection $collection): void
    {
        $payload = [
            'type' => 'shipper.collection.completed',
            'title' => 'تم إكمال التحصيل',
            'message' => "تم إكمال تحصيل الشحنات برقم {$collection->id}.",
            'shipper_collection_id' => $collection->id,
            'status' => $collection->status,
            'collection_date' => optional($collection->collection_date)->toDateString(),
        ];

        $this->notifyUsersByIds([$collection->shipper_user_id], $payload);
    }

    private function notifySettlementCompleted(ClientSettlement $settlement): void
    {
        $payload = [
            'type' => 'client.settlement.completed',
            'title' => 'تم إكمال تسوية العميل',
            'message' => "تمت تسوية العميل برقم {$settlement->id} بنجاح.",
            'client_settlement_id' => $settlement->id,
            'status' => $settlement->status,
            'settlement_date' => optional($settlement->settlement_date)->toDateString(),
        ];

        $this->notifyUsersByIds([$settlement->client_user_id], $payload);
    }

    private function notifyShipperReturnCompleted(ShipperReturn $return): void
    {
        $payload = [
            'type' => 'shipper.return.completed',
            'title' => 'تمت مرتجعات الشيبّر',
            'message' => "تم إكمال مرتجع الشيبّر برقم {$return->id}.",
            'shipper_return_id' => $return->id,
            'status' => $return->status,
            'return_date' => optional($return->return_date)->toDateString(),
        ];

        $this->notifyUsersByIds([$return->shipper_user_id], $payload);
    }

    private function notifyClientReturnCompleted(ClientReturn $return): void
    {
        $payload = [
            'type' => 'client.return.completed',
            'title' => 'تمت مرتجعات العميل',
            'message' => "تم إكمال مرتجع العميل برقم {$return->id}.",
            'client_return_id' => $return->id,
            'status' => $return->status,
            'return_date' => optional($return->return_date)->toDateString(),
        ];

        $this->notifyUsersByIds([$return->client_user_id], $payload);
    }

    private function notifyClientPlanChanged(Client $client, mixed $oldPlanId, mixed $newPlanId): void
    {
        if (! $newPlanId || $oldPlanId === $newPlanId) {
            return;
        }

        $oldPlanName = Plan::query()->whereKey($oldPlanId)->value('name');
        $newPlan = Plan::query()->find($newPlanId);

        if (! $newPlan) {
            return;
        }

        $payload = [
            'type' => 'client.plan.changed',
            'title' => 'تم نقل خطتك تلقائيا',
            'message' => 'تم نقل حسابك إلى خطة '.$newPlan->name.($oldPlanName ? " بدلا من {$oldPlanName}" : '').'.',
            'old_plan_id' => $oldPlanId,
            'old_plan_name' => $oldPlanName,
            'new_plan_id' => $newPlan->id,
            'new_plan_name' => $newPlan->name,
            'new_plan_order_count' => $newPlan->order_count,
        ];

        $this->notifyUsersByIds([$client->user_id], $payload);
    }

    private function autoUpgradeClientPlanIfThresholdExceeded(Order $order): void
    {
        if (! $order->client_user_id) {
            return;
        }

        $client = Client::query()
            ->with('plan:id,name,order_count')
            ->where('user_id', $order->client_user_id)
            ->first();

        if (! $client || ! $client->plan) {
            return;
        }

        $currentPlan = $client->plan;
        $currentCount = Order::query()->where('client_user_id', $order->client_user_id)->count();

        if ($currentCount <= $currentPlan->order_count) {
            return;
        }

        $nextPlan = Plan::query()
            ->where('order_count', '>', $currentPlan->order_count)
            ->where('order_count', '>=', $currentCount)
            ->orderBy('order_count')
            ->first();

        if (! $nextPlan) {
            $nextPlan = Plan::query()
                ->where('order_count', '>', $currentPlan->order_count)
                ->orderBy('order_count')
                ->first();
        }

        if (! $nextPlan || $nextPlan->id === $client->plan_id) {
            return;
        }

        $client->update([
            'plan_id' => $nextPlan->id,
        ]);
    }

    private function isApprovalWorkflowModel(Model $model): bool
    {
        return $model instanceof ShipperCollection
            || $model instanceof ClientSettlement
            || $model instanceof ShipperReturn
            || $model instanceof ClientReturn;
    }

    private function notifyWorkflowCreated(Model $model): void
    {
        $model->loadMissing($this->workflowRelations($model));

        $payload = [
            'type' => $this->workflowType($model).'.requested',
            'title' => $this->workflowTitle($model),
            'message' => $this->workflowMessage($model),
            'record_id' => $model->getKey(),
            'status' => $model->status ?? null,
            'approval_status' => $model->approval_status ?? null,
            'number_of_orders' => $model->number_of_orders ?? null,
        ];

        $this->notifyUsersByPermission($this->workflowApprovePermission($model), $payload);
    }

    private function notifyWorkflowApprovalChanged(Model $model): void
    {
        $model->loadMissing($this->workflowRelations($model));

        $payload = [
            'type' => $this->workflowType($model).'.approval.changed',
            'title' => $this->workflowTitle($model).' - تحديث الموافقة',
            'message' => $this->workflowMessage($model).' حالة الموافقة: '.($model->approval_status ?? '-').'.',
            'record_id' => $model->getKey(),
            'status' => $model->status ?? null,
            'approval_status' => $model->approval_status ?? null,
            'number_of_orders' => $model->number_of_orders ?? null,
        ];

        $ownerId = match (true) {
            $model instanceof ShipperCollection, $model instanceof ShipperReturn => $model->shipper_user_id,
            $model instanceof ClientSettlement, $model instanceof ClientReturn => $model->client_user_id,
            default => null,
        };

        $this->notifyUsersByIds([$ownerId], $payload);
    }

    private function workflowRelations(Model $model): array
    {
        return match (true) {
            $model instanceof ShipperCollection, $model instanceof ShipperReturn => ['shipper:id,name'],
            $model instanceof ClientSettlement, $model instanceof ClientReturn => ['client:id,name'],
            default => [],
        };
    }

    private function workflowType(Model $model): string
    {
        return match (true) {
            $model instanceof ShipperCollection => 'shipper.collection',
            $model instanceof ClientSettlement => 'client.settlement',
            $model instanceof ShipperReturn => 'shipper.return',
            $model instanceof ClientReturn => 'client.return',
            default => 'workflow',
        };
    }

    private function workflowTitle(Model $model): string
    {
        return match (true) {
            $model instanceof ShipperCollection => 'طلب تحصيل مندوب جديد',
            $model instanceof ClientSettlement => 'طلب تحصيل عميل جديد',
            $model instanceof ShipperReturn => 'طلب مرتجع مندوب جديد',
            $model instanceof ClientReturn => 'طلب مرتجع عميل جديد',
            default => 'طلب جديد',
        };
    }

    private function workflowMessage(Model $model): string
    {
        $owner = match (true) {
            $model instanceof ShipperCollection, $model instanceof ShipperReturn => $model->shipper?->name,
            $model instanceof ClientSettlement, $model instanceof ClientReturn => $model->client?->name,
            default => null,
        };

        $ownerPart = $owner ? " من {$owner}" : '';

        $approvalStatus = (string) ($model->approval_status ?? 'PENDING');
        $suffix = $approvalStatus === 'PENDING'
            ? ' في انتظار المراجعة.'
            : " بحالة موافقة {$approvalStatus}.";

        return $this->workflowTitle($model).$ownerPart." برقم {$model->getKey()}".$suffix;
    }

    private function workflowApprovePermission(Model $model): string
    {
        return match (true) {
            $model instanceof ShipperCollection => 'shipper-collection.approve',
            $model instanceof ClientSettlement => 'client-settlement.approve',
            $model instanceof ShipperReturn => 'shipper-return.approve',
            $model instanceof ClientReturn => 'client-return.approve',
            default => '',
        };
    }

    /**
     * @param  array<int, int|string|null>  $ids
     * @param  array<string, mixed>  $payload
     */
    private function shouldSkipOrderStatusDatabaseNotification(): bool
    {
        if (! $this->isTruthySetting(Setting::getValue('whatsapp_enabled', config('whatsapp.enabled')))) {
            return false;
        }

        return $this->isTruthySetting(Setting::getValue('whatsapp_replace_order_notifications', 'yes'));
    }

    private function isTruthySetting(mixed $value): bool
    {
        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }

    private function notifyUsersByIds(array $ids, array $payload): void
    {
        $userIds = collect($ids)
            ->filter(fn ($id): bool => ! is_null($id) && (int) $id > 0)
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return;
        }

        User::query()
            ->whereIn('id', $userIds->all())
            ->get()
            ->each(function (User $user) use ($payload): void {
                $user->notify(new WorkflowEventNotification($payload));
            });
    }

    private function notifyUsersByPermission(string $permission, array $payload): void
    {
        if ($permission === '') {
            return;
        }

        $userIds = User::query()
            ->permission($permission)
            ->pluck('id')
            ->merge(
                User::query()
                    ->whereHas('roles', function ($query): void {
                        $query->whereIn('name', ['admin', 'super-admin']);
                    })
                    ->pluck('id')
            )
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return;
        }

        User::query()
            ->whereIn('id', $userIds->all())
            ->get()
            ->each(function (User $user) use ($payload): void {
                $user->notify(new WorkflowEventNotification($payload));
            });
    }
}
