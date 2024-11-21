<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Pylon\JsonResourceKit\Traits\BaseJsonResource;

class ConfigResource extends JsonResource
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
            'months' => $this->months,
            'days' => $this->days,
            'credits' => $this->credits,
            'points' => $this->points,
            'outlet_name' => $this->outlet_name,
            'outlet_id' => $this->outlet_id,
            'value' => $this->value,
            'updated_at' => $this->updated_at_date_time,

        ];

        return $data;
    }
}
