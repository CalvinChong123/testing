<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Pylon\JsonResourceKit\Traits\BaseJsonResource;

class TransactionResource extends JsonResource
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
        return [
            'id' => $this->id,
            'user' => new UserResource($this->whenLoaded('user')),
            'type' => $this->type,
            'credit_type' => $this->credit === null ? 'Promotion Credit' : 'Credit',
            'credit' => $this->credit,
            'promotion_credit' => $this->promotion_credit,
            'payment_method' => $this->payment_method,
            'merchant' => new MerchantResource($this->whenLoaded('merchant')),
            'created_at' => $this->created_at_date_time,

        ];

        return $data;
    }
}
