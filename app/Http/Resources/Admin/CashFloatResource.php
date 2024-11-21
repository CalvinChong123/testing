<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Pylon\JsonResourceKit\Traits\BaseJsonResource;

class CashFloatResource extends JsonResource
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
            'shift_no' => $this->shift_no,
            'cash_in' => $this->cash_in,
            'cash_out' => $this->cash_out,
            'cash_in_user' => UserResource::make($this->whenLoaded('cashInAdmin')),
            'cash_out_user' => UserResource::make($this->whenLoaded('cashOutAdmin')),
            'remark' => $this->remark,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'cash_replenishments' => $this->whenLoaded('cashReplenishments'),
            'total_cash_replenishments' => $this->whenLoaded('cashReplenishments', function () {
                return $this->total_cash_replenishment;
            }),
        ];

        return $data;
    }
}
