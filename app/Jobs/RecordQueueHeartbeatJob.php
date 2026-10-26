<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;

class RecordQueueHeartbeatJob implements ShouldQueue
{
    use Queueable;

    /**
     * Execute the job to record queue worker heartbeat.
     */
    public function handle(): void
    {
        Cache::put('donorlink:heartbeat:queue_worker', now()->timestamp);
    }
}
