<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Approval\ApprovalActivity\InfoFormRequest as ApprovalActivityInfoFormRequest;
use App\Http\Requests\Admin\Approval\ApprovalActivity\ListFormRequest as ApprovalActivityListFormRequest;
use App\Http\Requests\Admin\Approval\ApprovalLayer\UpdateFormRequest as ApprovalLayerUpdateFormRequest;
use App\Http\Requests\Admin\Approval\ApprovalLog\ListFormRequest as ApprovalLogListFormRequest;
use App\Http\Requests\Admin\Approval\ApprovalLog\UpdateFormRequest as ApprovalLogUpdateFormRequest;
use App\Http\Resources\Admin\Approval\ApprovalActivityResource;
use App\Http\Resources\Admin\Approval\ApprovalRequestResource;
use App\Http\Resources\Admin\Approval\ApprovalLogResource;
use App\Models\ApprovalActivity;
use App\Models\ApprovalLayer;
use App\Models\ApprovalRequest;
use App\Models\ApprovalLog;
use App\Queriplex\ApprovalActivityQueriplex;
use App\Queriplex\ApprovalRequestQueriplex;
use App\Queriplex\ApprovalLogQueriplex;
use Illuminate\Support\Facades\DB;

class ApprovalController extends Controller
{

	// Approval Activity
	public function list(ApprovalActivityListFormRequest $request)
	{
		$payload = $request->validated();
		$payload['sort_by'] = 'created_time';
		$approvals = ApprovalActivityQueriplex::make(ApprovalActivity::query(), $payload)
			->paginate($payload['items_per_page'] ?? 15);

		$approvals->load(['layers', 'layers.role']);

		$result = ApprovalActivityResource::paginateCollection($approvals);

		$response = [
			'approvals' => $result,
		];

		return self::successResponse('Success', $response);
	}

	public function info(ApprovalActivityInfoFormRequest $request)
	{
		$payload = $request->validated();

		$approval = ApprovalActivityQueriplex::make(ApprovalActivity::query(), $payload)
			->withTrashed()
			->firstOrThrowError();

		$approval->load(['layers', 'layers.role']);

		$result = new ApprovalActivityResource($approval);

		$response = [
			'approval' => $result,
		];

		return self::successResponse('Success', $response);
	}

	// Approval Layer
	public function updateLayer(ApprovalLayerUpdateFormRequest $request)
	{
		$payload = $request->validated();


		$result = DB::transaction(function () use ($payload) {
			ApprovalLayer::where('approval_activity_id', $payload['id'])->delete();
			foreach ($payload['layers'] as $layer) {
				ApprovalLayer::create([
					'approval_activity_id' => $payload['id'],
					'layer' => $layer['layer'],
					'role_id' => $layer['role_id'],
				]);
			}
		});

		return self::successResponse('Success', $result);
	}

	// Approval Log
	public function logList(ApprovalLogListFormRequest $request)
	{
		$payload = $request->validated();
		$payload['sort_by'] = 'created_time';
		$AuthUser = auth()->user();
		$payload['role_id'] = $AuthUser->roles->first()->id;
		$approvals = ApprovalLogQueriplex::make(ApprovalLog::query(), $payload)
			->where('status', ApprovalLog::STATUS_PENDING)
			->paginate($payload['items_per_page'] ?? 15);

		$approvals->load(['approvalRequest', 'approvalRequest.activity', 'approvalRequest.requestBy']);

		$result = ApprovalLogResource::paginateCollection($approvals);

		$response = [
			'approvals' => $result,
		];

		return self::successResponse('Success', $response);
	}

	public function updateLogStatus(ApprovalLogUpdateFormRequest $request)
	{
		$payload = $request->validated();

		$approvalLog = ApprovalLog::findOrFail($payload['id']);

		$approvalLog->processApproval($payload['status']);


		return self::successResponse('Success', $approvalLog);
	}
}
