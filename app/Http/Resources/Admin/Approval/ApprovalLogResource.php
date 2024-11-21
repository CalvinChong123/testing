<?php

namespace App\Http\Resources\Admin\Approval;

use Illuminate\Http\Resources\Json\JsonResource;
use Pylon\JsonResourceKit\Traits\BaseJsonResource;

class ApprovalLogResource extends JsonResource
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
			'message' => $this->whenLoaded('approvalRequest', function () {
				$data = json_decode($this->approvalRequest->data, true);
				return $data['message'] ?? null;
			}),
			'request_by' => $this->whenLoaded('approvalRequest', function () {
				return $this->approvalRequest->requestBy->name;
			}),
			'created_at_date_time' => $this->created_at_date_time,

		];

		return $data;
	}
}
