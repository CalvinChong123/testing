<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Resources\Json\JsonResource;
use Pylon\JsonResourceKit\Traits\BaseJsonResource;

class NotificationResource extends JsonResource
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
			'notification_type' => $this->notification_type,
			'type' => $this->notification_type,
			'message' => $this->message,
			'created_at_date_time' => $this->created_at_date_time,
			'is_read' => $this->is_read,
			'audit_id' => $this->audit_id,
			'audit' => $this->whenLoaded('audit'),
		];

		return $data;
	}
}
