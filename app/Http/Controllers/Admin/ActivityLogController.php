<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ActivityLog\ListFormRequest as ActivityLogListFormRequest;
use App\Http\Resources\Admin\ActivityLogResource;
use App\Models\Audit;
use App\Queriplex\ActivityLogQueriplex;

class ActivityLogController extends Controller
{
    public function list(ActivityLogListFormRequest $request)
    {
        $payload = $request->validated();

        $payload['sort_by'] = 'created_time';
        $activityLogs = ActivityLogQueriplex::make(Audit::query(), $payload)
            ->paginate($payload['items_per_page'] ?? 15);


        $activityLogs->load(['user']);

        $result = ActivityLogResource::paginateCollection($activityLogs);

        $response = [
            'activity_logs' => $result,
        ];

        return self::successResponse('Success', $response);
    }
}
