<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\City;
use App\Models\Client;
use App\Models\ClientReturn;
use App\Models\ClientReturnOrder;
use App\Models\ClientSettlement;
use App\Models\ClientSettlementOrder;
use App\Models\Content;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Governorate;
use App\Models\LoginSession;
use App\Models\Material;
use App\Models\MaterialRequest;
use App\Models\MaterialRequestItem;
use App\Models\Order;
use App\Models\PickupRequest;
use App\Models\Plan;
use App\Models\PlanPrice;
use App\Models\RefusedReason;
use App\Models\Shipper;
use App\Models\ShipperCollection;
use App\Models\ShipperCollectionOrder;
use App\Models\ShipperReturn;
use App\Models\ShipperReturnOrder;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class FakeDataSeeder extends Seeder
{
    public function run(): void
    {
        // Prevent duplicated huge datasets on repeated seeding in dev.
        if (Order::query()->exists()) {
            return;
        }

        $faker = fake();
        $admin = User::query()->where('username', 'admin')->firstOrFail();

        $shipperUsers = collect();
        for ($i = 1; $i <= 6; $i++) {
            $user = User::query()->updateOrCreate(
                ['username' => "shipper{$i}"],
                [
                    'name' => "Shipper {$i}",
                    'phone' => '015000000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                    'password' => Hash::make('12345678'),
                    'is_blocked' => false,
                ]
            );

            $user->assignRole('operations-manager');

            Shipper::query()->updateOrCreate(
                ['user_id' => $user->id],
                ['commission_rate' => $faker->randomFloat(2, 5, 20)]
            );

            $shipperUsers->push($user);
        }

        $contents = collect();
        foreach (['Documents', 'Electronics', 'Clothes', 'Cosmetics', 'Accessories'] as $contentName) {
            $contents->push(Content::query()->firstOrCreate(['name' => $contentName]));
        }

        $governorates = collect();
        foreach (['Cairo', 'Giza', 'Alexandria', 'Dakahlia'] as $idx => $govName) {
            $gov = Governorate::query()->updateOrCreate(
                ['name' => $govName],
                [
                    'follow_up_hours' => $faker->numberBetween(6, 72),
                    'default_shipper_user_id' => $shipperUsers[$idx % $shipperUsers->count()]->id,
                ]
            );

            for ($c = 1; $c <= 3; $c++) {
                City::query()->firstOrCreate([
                    'name' => "{$govName} City {$c}",
                    'governorate_id' => $gov->id,
                ]);
            }

            $governorates->push($gov);
        }

        $plans = collect();
        foreach ([['Starter', 50], ['Growth', 200], ['Enterprise', 1000]] as [$name, $count]) {
            $plans->push(Plan::query()->updateOrCreate(
                ['name' => $name],
                ['order_count' => $count]
            ));
        }

        $planPrices = collect();
        foreach ($plans as $plan) {
            foreach ($governorates as $gov) {
                $planPrices->push(PlanPrice::query()->updateOrCreate(
                    [
                        'plan_id' => $plan->id,
                        'governorate_id' => $gov->id,
                    ],
                    [
                        'price' => $faker->randomFloat(2, 20, 120),
                    ]
                ));
            }
        }

        $clientUsers = collect();
        for ($i = 1; $i <= 12; $i++) {
            $user = User::query()->updateOrCreate(
                ['username' => "client{$i}"],
                [
                    'name' => "Client {$i}",
                    'phone' => '010000000'.str_pad((string) $i, 2, '0', STR_PAD_LEFT),
                    'password' => Hash::make('12345678'),
                    'is_blocked' => false,
                ]
            );

            $user->assignRole('account-manager');

            Client::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'address' => $faker->streetAddress(),
                    'plan_id' => $plans->random()->id,
                    'shipping_content_id' => $contents->random()->id,
                ]
            );

            $clientUsers->push($user);
        }

        foreach (['Supervisor', 'Accounting', 'Dispatch'] as $idx => $name) {
            $user = User::query()->updateOrCreate(
                ['username' => strtolower($name)],
                [
                    'name' => $name,
                    'phone' => '011111111'.$idx,
                    'password' => Hash::make('12345678'),
                    'is_blocked' => false,
                ]
            );

            $user->assignRole('expense-manager');
        }

        $expenseCategories = collect();
        foreach (['Fuel', 'Office', 'Maintenance', 'Salaries', 'Marketing'] as $name) {
            $expenseCategories->push(ExpenseCategory::query()->updateOrCreate(
                ['name' => $name],
                [
                    'notes' => $faker->sentence(),
                    'is_active' => true,
                ]
            ));
        }

        foreach (range(1, 40) as $i) {
            $status = collect(['PENDING', 'APPROVED', 'REJECTED', 'PAID', 'CANCELLED'])->random();

            Expense::query()->create([
                'code' => 'EXP-'.str_pad((string) $i, 5, '0', STR_PAD_LEFT),
                'category_id' => $expenseCategories->random()->id,
                'amount' => $faker->randomFloat(2, 50, 5000),
                'expense_date' => Carbon::now()->subDays($faker->numberBetween(0, 90))->toDateString(),
                'title' => $faker->sentence(3),
                'notes' => $faker->optional()->sentence(),
                'created_by' => $admin->id,
                'approved_by' => in_array($status, ['APPROVED', 'PAID'], true) ? $admin->id : null,
                'status' => $status,
                'approved_at' => in_array($status, ['APPROVED', 'PAID'], true) ? Carbon::now()->subDays($faker->numberBetween(0, 20)) : null,
                'paid_at' => $status === 'PAID' ? Carbon::now()->subDays($faker->numberBetween(0, 10)) : null,
            ]);
        }

        foreach ([
            ['Customer unreachable', 'OUT_FOR_DELIVERY'],
            ['Wrong address', 'OUT_FOR_DELIVERY'],
            ['Rejected by receiver', 'UNDELIVERED'],
            ['No cash', 'HOLD'],
            ['Rescheduled request', 'HOLD'],
        ] as [$reason, $status]) {
            RefusedReason::query()->updateOrCreate(
                ['reason' => $reason],
                [
                    'status' => $status,
                    'is_active' => true,
                    'is_clear' => $faker->boolean(20),
                    'is_edit_amount' => $faker->boolean(25),
                ]
            );
        }

        $materials = collect();
        foreach (range(1, 18) as $i) {
            $materials->push(Material::query()->updateOrCreate(
                ['code' => 'MAT-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT)],
                [
                    'name' => 'Material '.$i,
                    'cost_price' => $faker->randomFloat(2, 5, 300),
                    'sale_price' => $faker->randomFloat(2, 10, 500),
                    'stock' => $faker->numberBetween(10, 300),
                    'is_active' => true,
                    'notes' => $faker->optional()->sentence(),
                ]
            ));
        }

        $materialRequests = collect();
        foreach (range(1, 24) as $_) {
            $client = $clientUsers->random();
            $approval = collect(['PENDING', 'APPROVED', 'REJECTED'])->random();
            $status = collect(['PENDING', 'PROCESSING', 'COMPLETED', 'CANCELLED'])->random();

            $request = MaterialRequest::query()->create([
                'client_id' => $client->id,
                'delivery_type' => collect(['PICKUP', 'DELIVERY'])->random(),
                'combined_visit' => $faker->boolean(35),
                'materials_total' => 0,
                'shipping_cost' => $faker->randomFloat(2, 0, 90),
                'status' => $status,
                'approval_status' => $approval,
                'created_by' => $admin->id,
                'approved_by' => $approval === 'APPROVED' ? $admin->id : null,
                'approved_at' => $approval === 'APPROVED' ? Carbon::now()->subDays($faker->numberBetween(0, 30)) : null,
                'rejected_by' => $approval === 'REJECTED' ? $admin->id : null,
                'rejected_at' => $approval === 'REJECTED' ? Carbon::now()->subDays($faker->numberBetween(0, 30)) : null,
                'approval_note' => $faker->optional()->sentence(),
            ]);

            $total = 0.0;
            foreach (range(1, $faker->numberBetween(1, 4)) as $_item) {
                $material = $materials->random();
                $qty = $faker->numberBetween(1, 8);
                $price = (float) $material->sale_price;
                $lineTotal = round($qty * $price, 2);

                MaterialRequestItem::query()->create([
                    'material_request_id' => $request->id,
                    'material_id' => $material->id,
                    'quantity' => $qty,
                    'price' => $price,
                    'total' => $lineTotal,
                ]);

                $total += $lineTotal;
            }

            $request->update(['materials_total' => round($total, 2)]);
            $materialRequests->push($request);
        }

        $pickupRequests = collect();
        foreach (range(1, 30) as $_) {
            $client = $clientUsers->random();
            $shipper = $shipperUsers->random();
            $approval = collect(['PENDING', 'APPROVED', 'REJECTED'])->random();
            $status = collect(['PENDING', 'ASSIGNED', 'COMPLETED', 'CANCELLED'])->random();

            $pickupRequests->push(PickupRequest::query()->create([
                'client_id' => $client->id,
                'shipper_id' => $status === 'PENDING' ? null : $shipper->id,
                'pickup_date' => Carbon::now()->subDays($faker->numberBetween(0, 20))->toDateString(),
                'combined_with_material' => $faker->boolean(30),
                'pickup_cost' => $faker->randomFloat(2, 10, 120),
                'status' => $status,
                'approval_status' => $approval,
                'created_by' => $admin->id,
                'approved_by' => $approval === 'APPROVED' ? $admin->id : null,
                'approved_at' => $approval === 'APPROVED' ? Carbon::now()->subDays($faker->numberBetween(0, 20)) : null,
                'rejected_by' => $approval === 'REJECTED' ? $admin->id : null,
                'rejected_at' => $approval === 'REJECTED' ? Carbon::now()->subDays($faker->numberBetween(0, 20)) : null,
                'approval_note' => $faker->optional()->sentence(),
                'notes' => $faker->optional()->sentence(),
            ]));
        }

        $cities = City::query()->with('governorate')->get();
        $statuses = ['OUT_FOR_DELIVERY', 'DELIVERED', 'HOLD', 'UNDELIVERED'];

        foreach (range(1, 150) as $i) {
            $client = $clientUsers->random();
            $shipper = $shipperUsers->random();
            $city = $cities->random();
            $status = $statuses[array_rand($statuses)];
            $approval = collect(['PENDING', 'APPROVED', 'REJECTED'])->random();

            $baseAmount = $faker->randomFloat(2, 80, 1500);
            $shippingFee = $faker->randomFloat(2, 20, 120);
            $commission = round($baseAmount * ($faker->randomFloat(2, 5, 15) / 100), 2);
            $companyAmount = round($baseAmount * ($faker->randomFloat(2, 3, 12) / 100), 2);
            $codAmount = max(0, round($baseAmount - $commission, 2));

            $isDeliveredLike = in_array($status, ['DELIVERED', 'UNDELIVERED'], true);
            $shipperCollected = $isDeliveredLike && $faker->boolean(55);
            $shipperReturned = $isDeliveredLike && $faker->boolean(40);
            $clientSettled = $shipperCollected && $faker->boolean(45);
            $clientReturned = $shipperReturned && $faker->boolean(35);
            $hasReturn = $clientReturned || $shipperReturned;
            $hasReturnAt = $clientReturned
                ? Carbon::now()->subDays($faker->numberBetween(0, 15))->toDateString()
                : ($shipperReturned ? Carbon::now()->subDays($faker->numberBetween(0, 20))->toDateString() : null);

            Order::query()->create([
                'code' => 'ORD-'.str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'external_code' => 'EXT-'.$faker->bothify('###??'),
                'registered_at' => Carbon::now()->subDays($faker->numberBetween(0, 60)),
                'captain_date' => Carbon::now()->addDays($faker->numberBetween(-7, 10))->toDateString(),
                'receiver_name' => $faker->name(),
                'phone' => $faker->numerify('01#########'),
                'phone_2' => $faker->optional()->numerify('01#########'),
                'address' => $faker->address(),
                'governorate_id' => $city->governorate_id,
                'city_id' => $city->id,
                'total_amount' => $baseAmount,
                'shipping_fee' => $shippingFee,
                'commission_amount' => $commission,
                'company_amount' => $companyAmount,
                'cod_amount' => $codAmount,
                'status' => $status,
                'latest_status_note' => $faker->optional()->sentence(),
                'order_note' => $faker->optional()->sentence(),
                'shipper_user_id' => $shipper->id,
                'client_user_id' => $client->id,
                'allow_open' => $faker->boolean(70),
                'shipping_content_id' => $contents->random()->id,
                'approval_status' => $approval,
                'created_by' => $admin->id,
                'approved_by' => $approval === 'APPROVED' ? $admin->id : null,
                'approved_at' => $approval === 'APPROVED' ? Carbon::now()->subDays($faker->numberBetween(0, 40)) : null,
                'rejected_by' => $approval === 'REJECTED' ? $admin->id : null,
                'rejected_at' => $approval === 'REJECTED' ? Carbon::now()->subDays($faker->numberBetween(0, 40)) : null,
                'approval_note' => $faker->optional()->sentence(),
                'is_in_shipper_collection' => $shipperCollected,
                'is_shipper_collected' => $shipperCollected,
                'shipper_collected_at' => $shipperCollected ? Carbon::now()->subDays($faker->numberBetween(0, 30))->toDateString() : null,
                'is_in_client_settlement' => $clientSettled,
                'is_client_settled' => $clientSettled,
                'client_settled_at' => $clientSettled ? Carbon::now()->subDays($faker->numberBetween(0, 25))->toDateString() : null,
                'is_in_shipper_return' => $shipperReturned,
                'is_shipper_returned' => $shipperReturned,
                'shipper_returned_at' => $shipperReturned ? Carbon::now()->subDays($faker->numberBetween(0, 20))->toDateString() : null,
                'is_in_client_return' => $clientReturned,
                'is_client_returned' => $clientReturned,
                'has_return' => $hasReturn,
                'has_return_at' => $hasReturnAt,
                'client_returned_at' => $clientReturned ? $hasReturnAt : null,
                'shipper_date' => Carbon::now()->subDays($faker->numberBetween(0, 10))->toDateString(),
            ]);
        }

        $eligibleForCollection = Order::query()
            ->whereIn('status', ['DELIVERED', 'UNDELIVERED'])
            ->whereNotNull('shipper_user_id')
            ->inRandomOrder()
            ->take(40)
            ->get()
            ->groupBy('shipper_user_id');

        foreach ($eligibleForCollection as $shipperId => $orders) {
            $collection = ShipperCollection::query()->create([
                'shipper_user_id' => $shipperId,
                'collection_date' => Carbon::now()->subDays(rand(0, 8))->toDateString(),
                'total_amount' => round((float) $orders->sum('total_amount'), 2),
                'number_of_orders' => $orders->count(),
                'shipper_fees' => round((float) $orders->sum('commission_amount'), 2),
                'net_amount' => round((float) $orders->sum('cod_amount'), 2),
                'status' => 'COMPLETED',
                'approval_status' => 'APPROVED',
                'created_by' => $admin->id,
                'approved_by' => $admin->id,
                'approved_at' => Carbon::now()->subDays(rand(0, 5)),
            ]);

            foreach ($orders as $order) {
                ShipperCollectionOrder::query()->create([
                    'shipper_collection_id' => $collection->id,
                    'order_id' => $order->id,
                    'order_amount' => $order->total_amount,
                    'shipper_fee' => $order->commission_amount,
                    'net_amount' => $order->cod_amount,
                ]);
            }
        }

        $eligibleForShipperReturn = Order::query()
            ->whereIn('status', ['DELIVERED', 'UNDELIVERED'])
            ->whereNotNull('shipper_user_id')
            ->inRandomOrder()
            ->take(24)
            ->get()
            ->groupBy('shipper_user_id');

        foreach ($eligibleForShipperReturn as $shipperId => $orders) {
            $return = ShipperReturn::query()->create([
                'shipper_user_id' => $shipperId,
                'return_date' => Carbon::now()->subDays(rand(0, 8))->toDateString(),
                'number_of_orders' => $orders->count(),
                'notes' => $faker->optional()->sentence(),
                'status' => 'COMPLETED',
                'approval_status' => 'APPROVED',
                'created_by' => $admin->id,
                'approved_by' => $admin->id,
                'approved_at' => Carbon::now()->subDays(rand(0, 5)),
            ]);

            foreach ($orders as $order) {
                ShipperReturnOrder::query()->create([
                    'shipper_return_id' => $return->id,
                    'order_id' => $order->id,
                ]);
            }
        }

        $eligibleForClientSettlement = Order::query()
            ->whereIn('status', ['DELIVERED', 'UNDELIVERED'])
            ->inRandomOrder()
            ->take(40)
            ->get()
            ->groupBy('client_user_id');

        foreach ($eligibleForClientSettlement as $clientUserId => $orders) {
            $settlement = ClientSettlement::query()->create([
                'client_user_id' => $clientUserId,
                'settlement_date' => Carbon::now()->subDays(rand(0, 10))->toDateString(),
                'total_amount' => round((float) $orders->sum('total_amount'), 2),
                'number_of_orders' => $orders->count(),
                'fees' => round((float) $orders->sum('shipping_fee'), 2),
                'net_amount' => round((float) $orders->sum('cod_amount'), 2),
                'status' => 'COMPLETED',
                'approval_status' => 'APPROVED',
                'created_by' => $admin->id,
                'approved_by' => $admin->id,
                'approved_at' => Carbon::now()->subDays(rand(0, 5)),
            ]);

            foreach ($orders as $order) {
                ClientSettlementOrder::query()->create([
                    'client_settlement_id' => $settlement->id,
                    'order_id' => $order->id,
                    'order_amount' => $order->total_amount,
                    'fee' => $order->shipping_fee,
                    'net_amount' => $order->cod_amount,
                ]);
            }
        }

        $eligibleForClientReturn = Order::query()
            ->whereIn('status', ['DELIVERED', 'UNDELIVERED'])
            ->inRandomOrder()
            ->take(24)
            ->get()
            ->groupBy('client_user_id');

        foreach ($eligibleForClientReturn as $clientUserId => $orders) {
            $return = ClientReturn::query()->create([
                'client_user_id' => $clientUserId,
                'return_date' => Carbon::now()->subDays(rand(0, 8))->toDateString(),
                'number_of_orders' => $orders->count(),
                'notes' => $faker->optional()->sentence(),
                'status' => 'COMPLETED',
                'approval_status' => 'APPROVED',
                'created_by' => $admin->id,
                'approved_by' => $admin->id,
                'approved_at' => Carbon::now()->subDays(rand(0, 4)),
            ]);

            foreach ($orders as $order) {
                ClientReturnOrder::query()->create([
                    'client_return_id' => $return->id,
                    'order_id' => $order->id,
                ]);
            }
        }

        $allPickups = PickupRequest::query()->whereNotNull('shipper_id')->get();
        foreach ($allPickups->take(20) as $pickup) {
            Visit::query()->create([
                'shipper_id' => $pickup->shipper_id,
                'client_id' => $pickup->client_id,
                'pickup_request_id' => $pickup->id,
                'material_request_id' => null,
                'visit_cost' => $faker->randomFloat(2, 20, 120),
            ]);
        }

        foreach ($materialRequests->where('approval_status', 'APPROVED')->take(16) as $materialRequest) {
            Visit::query()->create([
                'shipper_id' => $shipperUsers->random()->id,
                'client_id' => $materialRequest->client_id,
                'pickup_request_id' => null,
                'material_request_id' => $materialRequest->id,
                'visit_cost' => $faker->randomFloat(2, 15, 90),
            ]);
        }

        $users = User::query()->get();
        foreach ($users as $user) {
            LoginSession::query()->create([
                'user_id' => $user->id,
                'session_id' => (string) $faker->unique()->numerify('##########'),
                'ip_address' => $faker->ipv4(),
                'user_agent' => $faker->userAgent(),
                'device_name' => collect(['Chrome on Windows', 'Edge on Windows', 'Safari on iPhone'])->random(),
                'device_type' => collect(['desktop', 'mobile', 'tablet'])->random(),
                'browser' => collect(['Chrome', 'Edge', 'Safari'])->random(),
                'platform' => collect(['Windows', 'Android', 'iOS'])->random(),
                'country' => $faker->country(),
                'city' => $faker->city(),
                'login_at' => Carbon::now()->subDays(rand(0, 20)),
                'last_seen_at' => Carbon::now()->subDays(rand(0, 3)),
                'logout_at' => null,
                'is_active' => true,
                'is_current' => $user->id === $admin->id,
            ]);
        }

        $entityCandidates = Order::query()->take(80)->pluck('id')->all();
        foreach (range(1, 120) as $_) {
            $eventType = collect(['order', 'expense', 'collection', 'settlement', 'material'])->random();
            $entityId = $entityCandidates[array_rand($entityCandidates)] ?? null;
            $actor = $users->random();
            $session = LoginSession::query()->where('user_id', $actor->id)->inRandomOrder()->first();

            ActivityLog::query()->create([
                'user_id' => $actor->id,
                'login_session_id' => $session?->id,
                'event_type' => $eventType,
                'entity_type' => Order::class,
                'entity_id' => $entityId,
                'action' => collect(['created', 'updated', 'approved', 'rejected', 'status_changed'])->random(),
                'label' => $faker->sentence(3),
                'old_values' => ['status' => 'PENDING'],
                'new_values' => ['status' => 'APPROVED'],
                'meta' => ['source' => 'fake-seeder'],
                'ip_address' => $faker->ipv4(),
                'user_agent' => $faker->userAgent(),
            ]);
        }
    }
}
