<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Pylon\JsonResourceKit\Traits\BaseJsonResource;

class PromotionCreditApprovalReportResource extends JsonResource
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
			'game_ids' => $this->game_ids,
			'total_credit_bet_amount' => $this->total_credit_bet_amount,
			'promotion_credit_tier_name' => $this->whenLoaded('promotionCreditTier', function () {
				return $this->promotionCreditTier->name ?? null;
			}),
			'promotion_credit_gains' => $this->promotion_credit_gains,
			'status' => $this->status,
			'remark' => $this->remark,
			'admin_id' => $this->admin_id,
			'admin_name' => $this->whenLoaded('admin', function () {
				return $this->admin->name ?? null;
			}),
			'created_at_date_time' => $this->created_at_date_time,

		];

		return $data;
	}
}
