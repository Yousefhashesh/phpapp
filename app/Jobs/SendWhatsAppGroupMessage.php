<?php

namespace App\Jobs;

use App\Support\Services\WhatsAppService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendWhatsAppGroupMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    /**
     * @param  array<int, string>|null  $groupIds
     */
    public function __construct(
        public readonly string $message,
        public readonly ?array $groupIds = null,
    ) {
        $this->afterCommit();
    }

    public function handle(WhatsAppService $service): void
    {
        $service->sendGroupMessageNow($this->message, $this->groupIds);
    }
}
