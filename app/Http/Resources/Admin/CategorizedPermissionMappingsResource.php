<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Pylon\JsonResourceKit\Traits\BaseJsonResource;

class CategorizedPermissionMappingsResource extends JsonResource
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
        $permissionCategoryMapping = $this->resource->groupBy('category');

        $data = $permissionCategoryMapping->map(function ($item, $key) {
            return $item->map(function ($permission) {
                return new SptPermissionResource($permission);
            });
        });

        return $data;
    }
}
