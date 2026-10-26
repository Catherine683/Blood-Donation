<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class HeartbeatCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'donorlink:heartbeat';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Record a scheduler heartbeat timestamp in the cache to monitor operational liveness';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        Cache::put('donorlink:heartbeat:scheduler', now()->timestamp);

        $this->info('Scheduler heartbeat recorded at '.now()->toIso8601String());

        return self::SUCCESS;
    }
}
