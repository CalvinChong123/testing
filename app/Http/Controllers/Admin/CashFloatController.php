<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CashFloat\InfoFormRequest as CashFloatInfoFormRequest;
use App\Http\Requests\Admin\CashFloat\ListFormRequest as CashFloatListFormRequest;
use App\Http\Requests\Admin\CashFloat\CheckInCheckOutFormRequest as CheckInCheckOutFormRequest;
use App\Http\Requests\Admin\CashFloat\CashReplenishmentFormRequest as CashReplenishmentFormRequest;
use App\Http\Resources\Admin\CashFloatResource;
use App\Models\CashFloat;
use App\Models\CashReplenishment;
use App\Queriplex\CashFloatQueriplex;
use Illuminate\Support\Facades\DB;
use App\Exceptions\BadRequestException;
use App\Http\Services\UserService;
use App\Http\Services\GeneralService;
use App\Models\Merchant;

class CashFloatController extends Controller
{

	public function list(CashFloatListFormRequest $request)
	{
		$payload = $request->validated();

		$payload['sort_by'] = 'created_time';

		$cashFloats = CashFloatQueriplex::make(CashFloat::query(), $payload)
			->paginate($payload['items_per_page'] ?? 15);

		$cashFloats->load(['cashInAdmin', 'cashOutAdmin', 'cashReplenishments']);

		$result = CashFloatResource::paginateCollection($cashFloats);

		$response = [
			'cash_floats' => $result,
		];

		return self::successResponse('Success', $response);
	}

	public function replenishList(CashFloatInfoFormRequest $request)
	{
		$payload = $request->validated();

		$cashFloat = CashFloat::where('id', $payload['id'])
			->withTrashed()
			->firstOrThrowError();

		$cashFloat->load(['cashInAdmin', 'cashOutAdmin', 'cashReplenishments']);

		$result = new CashFloatResource($cashFloat);

		$response = [
			'cash_float' => $result,
		];

		return self::successResponse('Success', $response);
	}

	public function checkShiftStatus()
	{
		$response = [
			'shift' =>
			GeneralService::getCurrentShift(),

		];

		return self::successResponse('Success', $response);
	}

	public function checkInCheckOut(CheckInCheckOutFormRequest $request)
	{
		$payload = $request->validated();
		$userId = auth()->user()->id;
		$currentShift = GeneralService::getCurrentShift();
		$newShift = $currentShift['new_shift'];
		$shiftNo = $currentShift['shift_no'];

		$result = DB::transaction(function () use ($payload, $userId, $shiftNo, $newShift) {
			if ($newShift) {
				return CashFloat::create([
					'shift_no' => $shiftNo,
					'cash_in' => $payload['amount'],
					'start_time' => now()->format('Y-m-d H:i:s'),
					'cash_in_admin_id' => $userId,
				]);
			} else {
				$latestCashFloat = CashFloat::orderBy('created_at', 'desc')->firstOrFail();
				$isLastShift = $payload['end_shift_today'] ?? false;
				$latestCashFloat->update([
					'cash_out' => $payload['amount'],
					'end_time' => now()->format('Y-m-d H:i:s'),
					'end_shift_today' => $isLastShift,
					'cash_out_admin_id' => $userId,
				]);

				if (!$isLastShift) {
					CashFloat::create([
						'shift_no' => $shiftNo + 1,
						'cash_in' => $payload['amount'],
						'start_time' => now()->format('Y-m-d H:i:s'),
						'cash_in_admin_id' => $userId,
					]);
				}
			}

			if ($payload['end_shift_today']) {

				GeneralService::generatePromotionCreditReport();
			}
			return $latestCashFloat;
		});

		return self::successResponse('Success', $result);
	}

	public function replenishments(CashReplenishmentFormRequest $request)
	{
		$payload = $request->validated();

		$userId = auth()->user()->id;

		$result = DB::transaction(function () use ($payload, $userId) {
			$cashFloat = CashFloat::where('id', $payload['cash_float_id'])
				->firstOrThrowError();
			if ($cashFloat->end_time) {
				throw new BadRequestException($cashFloat->end_time);
			}

			$replenishment = CashReplenishment::create([
				'cash_float_id' => $payload['cash_float_id'],
				'amount' => $payload['amount'],
				'remark' => $payload['remark'] ?? null,
				'admin_id' => $userId,
			]);

			return $replenishment;
		});
	}
}
