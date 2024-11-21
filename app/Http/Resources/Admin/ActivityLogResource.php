<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Pylon\JsonResourceKit\Traits\BaseJsonResource;

class ActivityLogResource extends JsonResource
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
        $oldValue = $this->old_values ?? null;


        return [
            'id' => $this->id,
            'created_at' => $this->created_at_date_time,
            'user' => $this->whenLoaded('user'),
            'module' => $this->module,
            'action' => $this->event,
            'description' => $this->description,
            'properties' => [
                'old_value' => $oldValue,
                'new_value' => $this->new_values,
            ],
        ];
    }
}
