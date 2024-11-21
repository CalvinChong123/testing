<?php

namespace App\Http\Resources\Admin\Approval;

use Illuminate\Http\Resources\Json\JsonResource;
use Pylon\JsonResourceKit\Traits\BaseJsonResource;

class ApprovalActivityResource extends JsonResource
{
	use BaseJsonResource;

	/**
	 * Transform the resource into an array.
	 *
	 * @param \Illuminate\Http\Request $request
	 *
	 * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
	 */
	public function toArray($request)
	{
		$data = [
			'id' => $this->id,
			'name' => $this->name,
			'layers' => $this->whenLoaded('layers'),
			'total_layers' => $this->whenLoaded('layers', function () {
				return $this->layers->count();
			}),
			'role_names' => $this->whenLoaded('layers', function () {
				return $this->layers->map(function ($layer) {
					return $layer->role->name;
				})->implode(', ');
			}),


		];

		return $data;
	}
}
