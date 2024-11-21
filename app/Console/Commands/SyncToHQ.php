<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\SyncLog;
use App\Http\Services\SyncService;
use Illuminate\Support\Facades\Log;

class SyncToHQ extends Command
{
    protected $signature = 'sync:hq';
    protected $description = 'Sync local changes to HQ';

    public function handle()
    {
        $syncLogs = SyncLog::all();

        $createdOrUpdated = [];
        $deleted = [];

        foreach ($syncLogs as $log) {
            $model = $log->model_type::withTrashed()->find($log->model_id);

            if ($log->event == 'created' || $log->event == 'updated') {
                if (strtolower(class_basename($model)) === 'admin') {
                    $model->load('roles');
                }
                if (strtolower(class_basename($model)) === 'merchant') {
                    $model->load('merchantGroup');
                    $createdOrUpdated[strtolower(class_basename($model))][$log->model_id] = $model->toArray();
                }
            } elseif ($log->event == 'deleted') {
                $deleted[strtolower(class_basename($model))][] = $log->model_id;
            }

            // $log->delete();
        }

        foreach ($createdOrUpdated as $modelType => $models) {
            $data = array_values($models);
            Log::info($modelType, $data);
            SyncService::request('post', '/api/sync/' . $modelType, ['data' => $data]);
        }

        foreach ($deleted as $modelType => $ids) {
            Log::info($modelType, $ids);
            SyncService::request('delete', '/api/sync/' . $modelType, ['ids' => $ids]);
        }

        $this->info('Sync completed successfully.');
    }
}
