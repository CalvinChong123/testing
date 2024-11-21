<?php

namespace App\Http\Services;

use App\Jobs\ExportHubWorker;
use App\Models\ExportHub;

class ExportHubService
{
    public static function createAndDispatch($reportInstance, array $payload): void
    {
        $exportHub = ExportHub::create($payload);
        ExportHubWorker::dispatch($exportHub->id, $reportInstance);
    }

    public static function expireRecordHander(int $expired_days = 14): int
    {
        $expired_at = now()->subDays($expired_days)->startOfDay();
        $expiredExports = ExportHub::where('created_at', '<', $expired_at)
            ->whereIn('status_key', [ExportHub::STATUS_PENDING, ExportHub::STATUS_SUCCESS])
            ->with(['file'])
            ->get();

        $expiredExports->each(function (ExportHub $exportHub) {
            if ($exportHub->file) {
                $exportHub->file->pruneDirectory();
            }

            $exportHub->update([
                'status_key' => ExportHub::STATUS_EXPIRED,
            ]);
        });

        return $expiredExports->count();
    }
}
