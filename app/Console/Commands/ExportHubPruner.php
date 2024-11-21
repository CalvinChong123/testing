<?php

namespace App\Console\Commands;

use App\Http\Services\ExportHubService;
use Illuminate\Console\Command;

class ExportHubPruner extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export-hub:prune {--days=14 : The number of days to retain ExportHub data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'EXPIRY and REMOVE Export Hub Files';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $numberOfRecords = ExportHubService::expireRecordHander($this->option('days'));

        $this->info($numberOfRecords);

        return self::SUCCESS;
    }
}
