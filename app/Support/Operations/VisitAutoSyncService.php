<?php

namespace App\Support\Operations;

use App\Models\MaterialRequest;
use App\Models\PickupRequest;
use App\Models\Visit;

class VisitAutoSyncService
{
    public function syncForPickupRequest(PickupRequest $pickupRequest): ?Visit
    {
        $pickupRequest->loadMissing('visit');

        if (! $pickupRequest->shipper_id || in_array($pickupRequest->status, ['COMPLETED', 'CANCELLED'], true)) {
            $this->detachPickupRequest($pickupRequest);

            return null;
        }

        $materialRequest = $pickupRequest->visit?->materialRequest ?? $this->findMaterialCandidateForPickup($pickupRequest);
        $visit = $pickupRequest->visit;

        if (! $visit) {
            $visit = Visit::query()->create([
                'shipper_id' => $pickupRequest->shipper_id,
                'client_id' => $pickupRequest->client_id,
                'pickup_request_id' => $pickupRequest->id,
                'material_request_id' => $materialRequest?->id,
                'visit_cost' => (float) ($pickupRequest->pickup_cost ?? 0),
            ]);
        } else {
            $visit->update([
                'shipper_id' => $pickupRequest->shipper_id,
                'client_id' => $pickupRequest->client_id,
                'pickup_request_id' => $pickupRequest->id,
                'material_request_id' => $visit->material_request_id ?? $materialRequest?->id,
            ]);
        }

        $visit = $visit->fresh(['pickupRequest', 'materialRequest']);
        $this->applyShippingRules($visit);

        return $visit->fresh(['pickupRequest', 'materialRequest']);
    }

    public function syncForMaterialRequest(MaterialRequest $materialRequest): ?Visit
    {
        $materialRequest->loadMissing('visit');

        if ($materialRequest->delivery_type !== 'DELIVERY' || in_array($materialRequest->status, ['COMPLETED', 'CANCELLED'], true)) {
            $this->detachMaterialRequest($materialRequest);

            return null;
        }

        $pickupRequest = $materialRequest->visit?->pickupRequest ?? $this->findPickupCandidateForMaterial($materialRequest);

        if (! $pickupRequest || ! $pickupRequest->shipper_id) {
            $this->detachMaterialRequest($materialRequest);

            return null;
        }

        $visit = $pickupRequest->visit;

        if (! $visit) {
            $visit = Visit::query()->create([
                'shipper_id' => $pickupRequest->shipper_id,
                'client_id' => $pickupRequest->client_id,
                'pickup_request_id' => $pickupRequest->id,
                'material_request_id' => $materialRequest->id,
                'visit_cost' => (float) ($pickupRequest->pickup_cost ?? 0),
            ]);
        } else {
            $visit->update([
                'shipper_id' => $pickupRequest->shipper_id,
                'client_id' => $pickupRequest->client_id,
                'material_request_id' => $materialRequest->id,
            ]);
        }

        $visit = $visit->fresh(['pickupRequest', 'materialRequest']);
        $this->applyShippingRules($visit);

        return $visit->fresh(['pickupRequest', 'materialRequest']);
    }

    public function detachPickupRequest(PickupRequest $pickupRequest): void
    {
        $pickupRequest->loadMissing('visit');

        $visit = $pickupRequest->visit;

        if (! $visit) {
            return;
        }

        $materialRequest = $visit->materialRequest;

        if ($materialRequest) {
            $restoredShippingCost = (float) ($visit->visit_cost ?? $pickupRequest->pickup_cost ?? 0);

            $materialRequest->update([
                'combined_visit' => false,
                'shipping_cost' => $materialRequest->delivery_type === 'DELIVERY' ? $restoredShippingCost : 0,
            ]);
        }

        $pickupRequest->update([
            'combined_with_material' => false,
        ]);

        $visit->delete();
    }

    public function detachMaterialRequest(MaterialRequest $materialRequest): void
    {
        $materialRequest->loadMissing('visit');

        $visit = $materialRequest->visit;

        $materialRequest->update([
            'combined_visit' => false,
            'shipping_cost' => $materialRequest->delivery_type === 'DELIVERY'
                ? (float) ($visit?->visit_cost ?? $materialRequest->shipping_cost ?? 0)
                : 0,
        ]);

        if (! $visit) {
            return;
        }

        $pickupRequest = $visit->pickupRequest;

        if (! $pickupRequest) {
            $visit->delete();

            return;
        }

        $visit->update([
            'material_request_id' => null,
            'visit_cost' => (float) ($pickupRequest->pickup_cost ?? 0),
        ]);

        $pickupRequest->update([
            'combined_with_material' => false,
        ]);
    }

    private function findMaterialCandidateForPickup(PickupRequest $pickupRequest): ?MaterialRequest
    {
        return MaterialRequest::query()
            ->where('client_id', $pickupRequest->client_id)
            ->where('delivery_type', 'DELIVERY')
            ->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
            ->whereDoesntHave('visit')
            ->latest('id')
            ->first();
    }

    private function findPickupCandidateForMaterial(MaterialRequest $materialRequest): ?PickupRequest
    {
        return PickupRequest::query()
            ->where('client_id', $materialRequest->client_id)
            ->whereNotNull('shipper_id')
            ->whereNotIn('status', ['COMPLETED', 'CANCELLED'])
            ->where(function ($query): void {
                $query->whereDoesntHave('visit')
                    ->orWhereHas('visit', function ($visitQuery): void {
                        $visitQuery->whereNull('material_request_id');
                    });
            })
            ->latest('id')
            ->first();
    }

    private function applyShippingRules(Visit $visit): void
    {
        $pickupRequest = $visit->pickupRequest;
        $materialRequest = $visit->materialRequest;

        if (! $pickupRequest) {
            return;
        }

        $shippingFee = (float) ($pickupRequest->pickup_cost ?? 0);

        if ($materialRequest && $pickupRequest->client_id === $materialRequest->client_id) {
            $pickupRequest->update([
                'combined_with_material' => true,
                'pickup_cost' => $shippingFee,
            ]);

            $materialRequest->update([
                'combined_visit' => true,
                'shipping_cost' => 0,
            ]);

            $visit->update(['visit_cost' => $shippingFee]);

            return;
        }

        $pickupRequest->update([
            'combined_with_material' => false,
        ]);

        if ($materialRequest) {
            $materialRequest->update([
                'combined_visit' => false,
            ]);
        }

        $visit->update(['visit_cost' => $shippingFee]);
    }
}
