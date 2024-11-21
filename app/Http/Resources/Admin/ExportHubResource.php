<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Pylon\JsonResourceKit\Traits\BaseJsonResource;

class ExportHubResource extends JsonResource
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
            'type' => $this->type,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
            'file' => new ModelableFileResource($this->whenLoaded('file')),
            'creator' => new UserResource($this->whenLoaded('creator')),
        ];

        return $data;
    }
}
