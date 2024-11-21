<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Pylon\JsonResourceKit\Traits\BaseJsonResource;

class MerchantResource extends JsonResource
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
        $currentMerchantUser = $this->currentMerchantUser();

        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'merchant_group_id' => $this->merchant_group_id,
            'status' => $this->status,
            'status_log' => $this->status_log,
            'cid0' => $this->cid0,
            'cid1' => $this->cid1,
            'cid2' => $this->cid2,
            'current_merchant_user' => $currentMerchantUser,
            'created_at_date_time' => $this->created_at_date_time,
            'asset_no' => $this->asset_no,
            'ip_address' => $this->ip_address,
            'image' => new ModelableFileResource($this->whenLoaded('image')),
        ];

        return $data;
    }
}
