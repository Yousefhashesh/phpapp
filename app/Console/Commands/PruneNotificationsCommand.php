<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneNotificationsCommand extends Command
{
    protected $signature = 'notifications:prune
                            {--days=30 : Delete notifications older than this many days}
                            {--read-only : Only delete read notifications}
                            {--type= : Only delete notifications of this data type (e.g. order.status.changed)}
                            {--force : Run without confirmation}';

    protected $description = 'Prune old notifications to keep the table performant';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        $query = DB::table('notifications')->where('created_at', '<', $cutoff);

        if ($this->option('read-only')) {
            $query->whereNotNull('read_at');
        }

        if ($type = $this->option('type')) {
            $query->where('data', 'like', '%"type":"'.$type.'"%');
        }

        $count = (clone $query)->count();

        if ($count === 0) {
            $this->info('No notifications to prune.');

            return self::SUCCESS;
        }

        if (! $this->option('force') && ! $this->confirm("Delete {$count} notification(s) older than {$days} day(s)?", true)) {
            $this->warn('Cancelled.');

            return self::SUCCESS;
        }

        $deleted = $query->delete();
        $this->info("Deleted {$deleted} notification(s).");

        return self::SUCCESS;
    }
}
