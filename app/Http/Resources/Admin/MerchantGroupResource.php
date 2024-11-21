<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Pylon\JsonResourceKit\Traits\BaseJsonResource;

class MerchantGroupResource extends JsonResource
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
            'name' => $this->name,
            'status' => $this->status,
            'status_log' => $this->status_log,
            'merchants' => MerchantResource::collection($this->whenLoaded('merchants')),
            'spending_credits' => $this->spending_credits,
            'earning_points' => $this->earning_points,
            'created_at_date_time' => $this->created_at_date_time,
            'merchant_count' => $this->merchant_count,

        ];

        return $data;
    }
}
