<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Pylon\JsonResourceKit\Traits\BaseJsonResource;

class AdminResource extends JsonResource
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
            'email' => $this->email,
            'ic' => $this->ic,
            'phone_no' => $this->phone_no,
            'member_category' => $this->member_category,
            'member_tier' => $this->member_tier,
            'dob' => $this->dob,
            'created_at_date' => $this->created_at_date,
            'created_at_time' => $this->created_at_time,
            'first_time_login' => $this->first_time_login,
            'status' => $this->status,
            'status_log' => $this->status_log,
            'permissions' => $this->whenLoaded('permissions'),
            'permission_names' => $this->when(isset($this->permission_names), $this->permission_names, []),
            'roles' => $this->whenLoaded('roles'),
        ];

        return $data;
    }
}
