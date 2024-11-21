<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PromotionCreditApprovalReport\InfoFormRequest as PromotionCreditApprovalReportInfoFormRequest;
use App\Http\Requests\Admin\PromotionCreditApprovalReport\ListFormRequest as PromotionCreditApprovalReportListFormRequest;
use App\Http\Requests\Admin\PromotionCreditApprovalReport\UpdateFormRequest as PromotionCreditApprovalReportUpdateFormRequest;
use App\Http\Requests\Admin\PromotionCreditApprovalReport\UpdateStatusFormRequest as PromotionCreditApprovalReportUpdateStatusFormRequest;
use App\Http\Resources\Admin\PromotionCreditApprovalReportResource;
use App\Models\PromotionCreditApprovalReport;
use App\Queriplex\PromotionCreditApprovalReportQueriplex;
use Illuminate\Support\Facades\DB;
use App\Http\Services\UserService;
use App\Models\UserPromotionCreditBalance;

class PromotionCreditApprovalReportController extends Controller
{
	public function list(PromotionCreditApprovalReportListFormRequest $request)
	{
		$payload = $request->validated();
		$payload['sort_by'] = 'created_time';
		$promotionCreditApprovalReports = PromotionCreditApprovalReportQueriplex::make(PromotionCreditApprovalReport::query(), $payload)
			->paginate($payload['items_per_page'] ?? 15);

		$promotionCreditApprovalReports->load(['user', 'approvedByUser', 'promotionCreditTier']);

		$result = PromotionCreditApprovalReportResource::paginateCollection($promotionCreditApprovalReports);

		$response = [
			'promotion_credit_approval_reports' => $result,
		];

		return self::successResponse('Success', $response);
	}

	public function info(PromotionCreditApprovalReportInfoFormRequest $request)
	{
		$payload = $request->validated();

		$promotionCreditApprovalReport = PromotionCreditApprovalReportQueriplex::make(PromotionCreditApprovalReport::query(), $payload)
			->withTrashed()
			->firstOrThrowError();

		$promotionCreditApprovalReport->load(['user', 'approvedByUser', 'promotionCreditTier']);

		$result = new PromotionCreditApprovalReportResource($promotionCreditApprovalReport);

		$response = [
			'promotion_credit_approval_report' => $result,
		];

		return self::successResponse('Success', $response);
	}



	public function update(PromotionCreditApprovalReportUpdateFormRequest $request)
	{
		$payload = $request->validated();


		$result = DB::transaction(function () use ($payload) {

			$promotionCreditApprovalReport = PromotionCreditApprovalReport::withTrashed()->findOrFail($payload['id']);
			$promotionCreditApprovalReport->update([
				'promotion_credit_gains' => $payload['promotion_credit_gains'],
				'remark' => $payload['remark'],
				'approved_by' => auth()->user()->id,
				'status' => PromotionCreditApprovalReport::STATUS_APPROVED,
			]);

			return $promotionCreditApprovalReport;
		});

		UserService::updateUserPromotionCreditBalance(UserPromotionCreditBalance::TYPE_EARN, $result);

		return self::successResponse('Success', $result);
	}

	public function statusUpdate(PromotionCreditApprovalReportUpdateStatusFormRequest $request)
	{
		$payload = $request->validated();


		$result = DB::transaction(function () use ($payload) {

			foreach ($payload['ids'] as $id) {
				$promotionCreditApprovalReport = PromotionCreditApprovalReport::withTrashed()->findOrFail($id);
				$promotionCreditApprovalReport->update([
					'status' => $payload['status'],
					'approved_by' => auth()->user()->id,
				]);

				if ($payload['status'] == PromotionCreditApprovalReport::STATUS_APPROVED) {
					UserService::updateUserPromotionCreditBalance(UserPromotionCreditBalance::TYPE_EARN, $promotionCreditApprovalReport);
				}
			};
		});

		return self::successResponse('Success', $result);
	}
}
