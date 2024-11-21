<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Pylon\JsonResourceKit\Traits\BaseJsonResource;

class RoleResource extends JsonResource
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
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_at_date' => $this->created_at_date,
            'created_at_time' => $this->created_at_time,
            'users_count' => $this->when($this->users_count, $this->users_count, 0),
            'permissions' => $this->whenLoaded('permissions'),
        ];

        return $data;
    }
}
