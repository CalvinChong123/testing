<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Pylon\JsonResourceKit\Traits\BaseJsonResource;

class UserPromotionCreditBalanceResource extends JsonResource
{
	use BaseJsonResource;

	/**
	 * Transform the resource into an array.
	 *
	 * @param \Illuminate\Http\Request $request
	 *
	 * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
	 */
	public function toArray($request)
	{
		$data = [
			'id' => $this->id,
			'user_id' => $this->user_id,
			'user_name' => $this->whenLoaded('user', function () {
				return $this->user->name ?? null;
			}),
			'promotion_credit_amount' => $this->promotion_credit_amount,
			'promotion_credit_balance_before_activity' => $this->promotion_credit_balance_before_activity,
			'promotion_credit_balance_after_activity' => $this->promotion_credit_balance_after_activity,
			'created_at_date_time'	=> $this->created_at_date_time,
			'type' => $this->type,
			'activity' => $this->activity,


		];

		return $data;
	}
}
