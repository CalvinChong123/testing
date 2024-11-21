<?php

namespace App\Jobs;

use App\Models\ExportHub;
use App\Models\ModelableFile;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class ExportHubWorker implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    protected $export_vault_id;

    protected $report_instance;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($export_vault_id, $report_instance)
    {
        $this->export_vault_id = $export_vault_id;
        $this->report_instance = $report_instance;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $exportHub = ExportHub::findOrFail($this->export_vault_id);
            $path = 'export-hub';
            $timestamp = now()->format('Ymd');
            $file_module_name = Str::replace(' ', '-', $exportHub->type);
            $file_name = "{$timestamp}-{$file_module_name}.xlsx";

            Excel::store($this->report_instance, "/{$path}/$exportHub->id/{$file_name}");
            $exportHub->status_key = ExportHub::STATUS_SUCCESS;
            $exportHub->save();

            $exportHub->file()->create([
                'name' => $file_name,
                'module_path' => $path,
                'disk' => config('filesystems.default'),
                'file_type_key' => ModelableFile::FILE_TYPE_SPREADSHEET,
            ]);
        } catch (Exception $e) {
            $exportHub->status_key = ExportHub::STATUS_FAILED;
            $exportHub->save();
            throw ($e);
        }

        return null;
    }
}
