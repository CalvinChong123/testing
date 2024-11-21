<?php

namespace App\Observers;

use App\Models\SyncLog;

class SyncLogObserver
{
    public function created($model)
    {
        SyncLog::create([
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'event' => 'created',
        ]);
    }

    public function updated($model)
    {
        SyncLog::create([
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'event' => 'updated',
        ]);
    }

    public function deleted($model)
    {
        SyncLog::create([
            'model_type' => get_class($model),
            'model_id' => $model->id,
            'event' => 'deleted',
        ]);
    }
}
