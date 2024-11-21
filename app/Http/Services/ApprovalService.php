<?php

namespace App\Http\Services;

use App\Contracts\Approvable;
use Illuminate\Support\Facades\DB;
use App\Models\ApprovalRequest;
use App\Models\ApprovalActivity;
use App\Models\ApprovalLayer;
use App\Models\ApprovalLog;


class ApprovalService
{
    /**
     * Create a new approval request for any model implementing Approvable.
     *
     * @param Approvable $model
     * @param array $payload
     * @return mixed
     */
    public static function createApprovalRequest(Approvable $model, array $payload, $activity)
    {
        return DB::transaction(function () use ($model, $payload, $activity) {

            $approvalActivity = ApprovalActivity::where('name', $activity)->first();
            $approvalData = array_merge($model->getApprovalData(), $payload);

            $layers = ApprovalLayer::where('approval_activity_id', $approvalActivity->id)->get();
            if ($layers->isEmpty()) {
                $model->applyApproval($payload);
                return $model;
            }

            $approvalRequest = ApprovalRequest::create([
                'status' => ApprovalRequest::STATUS_PENDING,
                'data' => json_encode($approvalData),
                'request_by_admin_id' => auth()->id(),
                'approval_activity_id' => $approvalActivity->id,
            ]);


            foreach ($layers as $index => $layer) {
                ApprovalLog::create([
                    'approval_request_id' => $approvalRequest->id,
                    'role_id' => $layer->role_id,
                    'layer_no' => $layer->layer,
                    'status' => $index === 0 ? ApprovalLog::STATUS_PENDING : ApprovalLog::STATUS_QUEUE,
                ]);
            }
            return $approvalRequest;
        });
    }

    /**
     * Approve the given approval request.
     *
     * @param Approvable $model
     * @param array $data
     * @return mixed
     */
    public function approve(Approvable $model, array $data)
    {
        return DB::transaction(function () use ($model, $data) {
            // Apply the approval logic
            $model->applyApproval($data);
            return $model;
        });
    }

    /**
     * Reject the given approval request.
     *
     * @param Approvable $model
     * @return void
     */
    public function reject(Approvable $model)
    {
        $model->rejectApproval();
    }
}
