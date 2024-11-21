<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Pylon\JsonResourceKit\Traits\BaseJsonResource;

class UserPointBalanceResource extends JsonResource
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
        $data =  [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'point' => $this->point_amount + $this->referral_point_amount,
            'total_point_balance' => $this->point_balance_after_activity + $this->referral_point_balance_after_activity,
            'point_amount' => $this->point_amount,
            'point_balance_after_activity' => $this->point_balance_after_activity,
            'referral_point_amount' => $this->referral_point_amount,
            'activity' => $this->activity,
            'type' => $this->type,
            'created_at_date_time' => $this->created_at_date_time,


        ];

        return $data;
    }
}
