<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\ShipperReturn;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillClientReturnsCommand extends Command
{
    protected $signature = 'client-returns:backfill
                            {--client= : Limit to a single client_user_id}
                            {--dry-run : Show what would be fixed without writing}
                            {--force : Run without confirmation}';

    protected $description = 'Sync completed shipper-return orders so they appear on unreturn client';

    public function handle(): int
    {
        $clientId = $this->option('client') !== null ? (int) $this->option('client') : null;
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $shipperReturns = ShipperReturn::query()
            ->where('status', 'COMPLETED')
            ->with(['orders:id,client_user_id,status,has_return,is_shipper_returned,is_client_returned'])
            ->orderBy('id')
            ->get();

        if ($shipperReturns->isEmpty()) {
            $this->info('No completed shipper returns found.');

            return self::SUCCESS;
        }

        $orderIds = $shipperReturns
            ->flatMap(fn (ShipperReturn $return) => $return->orders
                ->when($clientId, fn ($orders) => $orders->where('client_user_id', $clientId))
                ->pluck('id'))
            ->unique()
            ->values()
            ->all();

        if ($orderIds === []) {
            $this->info('No orders found in completed shipper returns.');

            return self::SUCCESS;
        }

        $missingShipperReturned = Order::query()
            ->whereIn('id', $orderIds)
            ->where('is_shipper_returned', false)
            ->count();

        $missingHasReturn = Order::query()
            ->whereIn('id', $orderIds)
            ->where('status', 'UNDELIVERED')
            ->where('has_return', false)
            ->count();

        $eligibleBefore = Order::query()
            ->whereIn('id', $orderIds)
            ->eligibleForClientReturn()
            ->count();

        $this->table(
            ['Metric', 'Count'],
            [
                ['Completed shipper returns', $shipperReturns->count()],
                ['Orders in those returns', count($orderIds)],
                ['Missing is_shipper_returned', $missingShipperReturned],
                ['UNDELIVERED missing has_return', $missingHasReturn],
                ['Eligible for unreturn client (before)', $eligibleBefore],
            ]
        );

        if ($missingShipperReturned === 0 && $missingHasReturn === 0 && $eligibleBefore > 0) {
            $this->info('Orders are already synced. Open /apps/orders/unreturnclient to verify.');

            return self::SUCCESS;
        }

        if ($dryRun) {
            $this->warn('Dry run only. No records were updated.');

            return self::SUCCESS;
        }

        if (! $force && ! $this->confirm('Sync these orders for unreturn client?', true)) {
            $this->info('Cancelled.');

            return self::SUCCESS;
        }

        $syncedShipperReturned = 0;
        $syncedHasReturn = 0;

        DB::transaction(function () use ($shipperReturns, $clientId, &$syncedShipperReturned, &$syncedHasReturn): void {
            foreach ($shipperReturns as $shipperReturn) {
                $returnOrderIds = $shipperReturn->orders
                    ->when($clientId, fn ($orders) => $orders->where('client_user_id', $clientId))
                    ->pluck('id')
                    ->all();

                if ($returnOrderIds === []) {
                    continue;
                }

                $syncedShipperReturned += Order::query()
                    ->whereIn('id', $returnOrderIds)
                    ->where('is_shipper_returned', false)
                    ->update([
                        'is_shipper_returned' => true,
                        'shipper_returned_at' => $shipperReturn->return_date,
                    ]);

                $syncedHasReturn += Order::query()
                    ->whereIn('id', $returnOrderIds)
                    ->where('status', 'UNDELIVERED')
                    ->where('has_return', false)
                    ->update([
                        'has_return' => true,
                        'has_return_at' => now(),
                    ]);
            }
        });

        $eligibleAfter = Order::query()
            ->whereIn('id', $orderIds)
            ->eligibleForClientReturn()
            ->count();

        $this->info("Synced is_shipper_returned on {$syncedShipperReturned} order(s).");
        $this->info("Set has_return on {$syncedHasReturn} UNDELIVERED order(s).");
        $this->info("Eligible for unreturn client now: {$eligibleAfter}.");

        return self::SUCCESS;
    }
}
