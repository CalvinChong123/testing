<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Pylon\JsonResourceKit\Traits\BaseJsonResource;

class ReferralResource extends JsonResource
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
        $data = [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'user_name' => $this->whenLoaded('user', function () {
                return $this->user->name ?? null;
            }),
            'referrer_user_id' => $this->referrer_user_id,
            'referrer_user_name' => $this->whenLoaded('referrer', function () {
                return $this->referrer->name ?? null;
            }),
            'credit_spend_before_referrer_expired' => $this->credit_spend_before_referrer_expired,
            'point_earned_for_referrer' => $this->point_earned_for_referrer,
            'expired_at' => $this->expired_at,
            'created_at_date' => $this->created_at_date,
            'expired_at_date' => $this->expired_at_date,
            'month_left' => $this->month_left,

        ];

        return $data;
    }
}
