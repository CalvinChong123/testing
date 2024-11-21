<?php

namespace App\Http\Resources\Admin\Approval;

use Illuminate\Http\Resources\Json\JsonResource;
use Pylon\JsonResourceKit\Traits\BaseJsonResource;

class ApprovalRequestResource extends JsonResource
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
		$data = [];

		return $data;
	}
}
