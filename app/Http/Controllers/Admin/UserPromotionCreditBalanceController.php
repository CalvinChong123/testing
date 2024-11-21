<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserPromotionCreditBalance\CreateFormRequest as UserPromotionCreditBalanceCreateFormRequest;
use App\Http\Requests\Admin\UserPromotionCreditBalance\DeleteFormRequest as UserPromotionCreditBalanceDeleteFormRequest;
use App\Http\Requests\Admin\UserPromotionCreditBalance\InfoFormRequest as UserPromotionCreditBalanceInfoFormRequest;
use App\Http\Requests\Admin\UserPromotionCreditBalance\ListFormRequest as UserPromotionCreditBalanceListFormRequest;
use App\Http\Requests\Admin\UserPromotionCreditBalance\UpdateFormRequest as UserPromotionCreditBalanceUpdateFormRequest;
use App\Http\Resources\Admin\UserPromotionCreditBalanceResource;
use App\Models\UserPromotionCreditBalance;
use App\Queriplex\UserPromotionCreditBalanceQueriplex;
use Illuminate\Support\Facades\DB;
use App\Models\Transaction;

class UserPromotionCreditBalanceController extends Controller
{
	public function list(UserPromotionCreditBalanceListFormRequest $request)
	{
		$payload = $request->validated();

		$payload['sort_by'] = 'created_time';

		$userPromotionCreditBalances = UserPromotionCreditBalanceQueriplex::make(UserPromotionCreditBalance::query(), $payload)
			->paginate($payload['items_per_page'] ?? 15);

		$userPromotionCreditBalances->load([
			'modelable' => function ($query) {
				$query->when($query->getModel() instanceof Transaction, function ($q) {
					$q->with('merchant');
				});
			}
		]);

		$result = UserPromotionCreditBalanceResource::paginateCollection($userPromotionCreditBalances);

		$response = [
			'user_promotion_credit_balances' => $result,
		];

		return self::successResponse('Success', $response);
	}

	public function info(UserPromotionCreditBalanceInfoFormRequest $request)
	{
		$payload = $request->validated();

		$userPromotionCreditBalance = UserPromotionCreditBalanceQueriplex::make(UserPromotionCreditBalance::query(), $payload)
			->withTrashed()
			->firstOrThrowError();

		$userPromotionCreditBalance->load([]);

		$result = new UserPromotionCreditBalanceResource($userPromotionCreditBalance);

		$response = [
			'user_promotion_credit_balance' => $result,
		];

		return self::successResponse('Success', $response);
	}
}
