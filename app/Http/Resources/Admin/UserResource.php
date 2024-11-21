<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Pylon\JsonResourceKit\Traits\BaseJsonResource;

class UserResource extends JsonResource
{
    use BaseJsonResource;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $currentMerchant = $this->currentMerchant();
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'ic' => $this->ic,
            'phone_no' => $this->phone_no,
            'member_no' => $this->member_no,
            'member_category' => $this->member_category,
            'member_tier' => $this->member_tier,
            'dob' => $this->dob,
            'outlet' => $this->outlet,
            'created_at_date' => $this->created_at_date,
            'created_at_time' => $this->created_at_time,
            'status' => $this->status,
            'status_log' => $this->status_log,
            'created_by_user' => $this->created_by_user,
            'current_merchant' => $currentMerchant,
            'referral_count' => $this->whenLoaded('referrals', function () {
                return $this->referral_count;
            }),
            'referrer' => new UserResource(optional($this->whenLoaded('referrer'))->referrer),
            'total_point_balance' => $this->whenLoaded('userPointBalance', function () {
                return $this->getTotalPointBalance();
            }),
            'total_promotion_credit_balance' => $this->whenLoaded('userPromotionCreditBalance', function () {
                return $this->getTotalPromotionCreditBalance();
            }),
        ];

        return $data;
    }
}
