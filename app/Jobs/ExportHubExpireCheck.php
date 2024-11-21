<?php

namespace App\Jobs;

use App\Http\Services\ExportHubService;
use Illuminate\Bus\Queueable;
// use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ExportHubExpireCheck implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $expired_days;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(int $expired_days = 14)
    {
        $this->expired_days = $expired_days;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        ExportHubService::expireRecordHander($this->expired_days);
    }
}
