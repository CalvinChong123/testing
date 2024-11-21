<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserPointBalance\CreateFormRequest as UserPointBalanceCreateFormRequest;
use App\Http\Requests\Admin\UserPointBalance\DeleteFormRequest as UserPointBalanceDeleteFormRequest;
use App\Http\Requests\Admin\UserPointBalance\InfoFormRequest as UserPointBalanceInfoFormRequest;
use App\Http\Requests\Admin\UserPointBalance\ListFormRequest as UserPointBalanceListFormRequest;
use App\Http\Resources\Admin\UserPointBalanceResource;
use App\Models\UserPointBalance;
use App\Queriplex\UserPointBalanceQueriplex;
use Illuminate\Support\Facades\DB;
use App\Http\Services\UserService;
use App\Http\Services\GeneralService;
use App\Http\Services\ApprovalService;
use App\Models\ApprovalActivity;

class UserPointBalanceController extends Controller
{
	public function list(UserPointBalanceListFormRequest $request)
	{
		$payload = $request->validated();

		$payload['sort_by'] = 'created_time';

		$userPointBalances = UserPointBalanceQueriplex::make(UserPointBalance::query(), $payload)
			->paginate($payload['items_per_page'] ?? 15);


		$userPointBalances->load(['game', 'referral.user']);

		$result = UserPointBalanceResource::paginateCollection($userPointBalances);

		$response = [
			'user_point_balances' => $result,
		];

		return self::successResponse('Success', $response);
	}

	public function info(UserPointBalanceInfoFormRequest $request)
	{
		$payload = $request->validated();
		$userPointBalance = UserPointBalanceQueriplex::make(UserPointBalance::query(), $payload)
			->latest()
			->first();

		if (!$userPointBalance) {
			$response = [
				'user_point_balance' => null,
			];
			return self::successResponse('Success', $response);
		}

		$userPointBalance->load([]);

		$result = new UserPointBalanceResource($userPointBalance);

		$response = [
			'user_point_balance' => $result,
		];

		return self::successResponse('Success', $response);
	}

	// public function create(UserPointBalanceCreateFormRequest $request)
	// {
	// 	$payload = $request->validated();

	// 	$userPointBalance = UserPointBalance::where('user_id', $payload['user_id'])
	// 		->latest()
	// 		->first();


	// 	if (($payload['type'] == 'Deduct' || $payload['type'] == 'Redeem') && (!isset($userPointBalance) || ($userPointBalance->point_balance_after_activity < $payload['point']))) {
	// 		$error['point'] = 'User does not have enough point balance';
	// 	}

	// 	if (!empty($error)) {
	// 		return self::customValidationException($error);
	// 	}

	// 	$result = UserService::updateUserPointBalance($userPointBalance, $payload);

	// 	return self::successResponse('Success', $result);
	// }

	public function create(UserPointBalanceCreateFormRequest $request)
	{
		$payload = $request->validated();

		$userPointBalance = UserPointBalance::where('user_id', $payload['user_id'])
			->latest()
			->first();


		if (($payload['type'] == UserPointBalance::TYPE_DEDUCT || $payload['type'] == UserPointBalance::TYPE_REDEEM) && (!isset($userPointBalance) || ($userPointBalance->point_balance_after_activity < $payload['point']))) {
			$error['point'] = 'User does not have enough point balance';
		}

		if (!empty($error)) {
			return self::customValidationException($error);
		}

		$pointBalanceRequest = ApprovalService::createApprovalRequest($userPointBalance, $payload, ApprovalActivity::NAME_ADD_DEDUCT_POINTS);

		if ($payload['type'] == UserPointBalance::TYPE_REDEEM) {
			$pointBalanceRequest = ApprovalService::createApprovalRequest($userPointBalance, $payload, ApprovalActivity::NAME_REDEEM_POINTS);
		}

		// $result = UserService::updateUserPointBalance($userPointBalance, $payload);
		return self::successResponse('Approval request created successfully', $pointBalanceRequest);
	}

	public function delete(UserPointBalanceDeleteFormRequest $request)
	{
		$payload = $request->validated();

		$userPointBalance = UserPointBalance::where('id', $payload['id'])
			->withTrashed()
			->firstOrThrowError();

		$result = $userPointBalance->restoreOrDelete();

		return self::successResponse('Success', $result);
	}
}
