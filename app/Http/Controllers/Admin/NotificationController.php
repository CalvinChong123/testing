<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Notification\CreateFormRequest as NotificationCreateFormRequest;
use App\Http\Requests\Admin\Notification\DeleteFormRequest as NotificationDeleteFormRequest;
use App\Http\Requests\Admin\Notification\InfoFormRequest as NotificationInfoFormRequest;
use App\Http\Requests\Admin\Notification\ListFormRequest as NotificationListFormRequest;
use App\Http\Requests\Admin\Notification\UpdateFormRequest as NotificationUpdateFormRequest;
use App\Http\Resources\Admin\NotificationResource;
use App\Models\Notification;
use App\Queriplex\NotificationQueriplex;
use Illuminate\Support\Facades\DB;
use Mockery\Matcher\Not;

class NotificationController extends Controller
{
	public function list(NotificationListFormRequest $request)
	{
		$payload = $request->validated();
		$payload['admin_id'] = auth()->user()->id;
		$payload['sort_by'] = 'created_time';
		$notifications = NotificationQueriplex::make(Notification::query(), $payload)
			->paginate($payload['items_per_page'] ?? 15);

		// $notifications->load(['audit']);

		$result = NotificationResource::paginateCollection($notifications);

		$response = [
			'notifications' => $result,
			'filter_options' => Notification::filterByOptions(),
		];

		return self::successResponse('Success', $response);
	}

	public function info(NotificationInfoFormRequest $request)
	{
		$payload = $request->validated();

		$notification = NotificationQueriplex::make(Notification::query(), $payload)
			->withTrashed()
			->firstOrThrowError();

		$notification->load([]);

		$result = new NotificationResource($notification);

		$response = [
			'notification' => $result,
		];

		return self::successResponse('Success', $response);
	}

	public function delete(NotificationDeleteFormRequest $request)
	{
		$payload = $request->validated();

		$notification = Notification::where('id', $payload['id'])
			->withTrashed()
			->firstOrThrowError();

		$result = $notification->restoreOrDelete();

		return self::successResponse('Success', $result);
	}
}
