<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExportHub\CreateFormRequest as ExportHubCreateFormRequest;
use App\Http\Requests\Admin\ExportHub\DeleteFormRequest as ExportHubDeleteFormRequest;
use App\Http\Requests\Admin\ExportHub\InfoFormRequest as ExportHubInfoFormRequest;
use App\Http\Requests\Admin\ExportHub\ListFormRequest as ExportHubListFormRequest;
use App\Http\Requests\Admin\ExportHub\UpdateFormRequest as ExportHubUpdateFormRequest;
use App\Http\Resources\Admin\ExportHubResource;
use App\Models\ExportHub;
use App\Queriplex\ExportHubQueriplex;
use Illuminate\Support\Facades\DB;

class ExportHubController extends Controller
{
    public function list(ExportHubListFormRequest $request)
    {
        $payload = $request->validated();

        $exportHubs = ExportHubQueriplex::make(ExportHub::query(), $payload)
            ->paginate($payload['items_per_page'] ?? 15);

        $exportHubs->load([
            'file',
            'creator',
        ]);

        $result = ExportHubResource::paginateCollection($exportHubs);

        $response = [
            'export_hubs' => $result,
        ];

        return self::successResponse('Success', $response);
    }

    public function info(ExportHubInfoFormRequest $request)
    {
        $payload = $request->validated();

        $exportHub = ExportHubQueriplex::make(ExportHub::query(), $payload)
            ->withTrashed()
            ->firstOrThrowError();

        $exportHub->load([]);

        $result = new ExportHubResource($exportHub);

        $response = [
            'export_hub' => $result,
        ];

        return self::successResponse('Success', $response);
    }

    // public function create(ExportHubCreateFormRequest $request)
    // {
    // 	$payload = $request->validated();

    // 	$result = DB::transaction(function () use ($payload)
    // 	{
    // 		$exportHub = ExportHub::create([
    // 			'name' => $payload['name'],
    // 		]);

    // 		return $exportHub;
    // 	});

    // 	return self::successResponse('Success', $result);
    // }

    // public function update(ExportHubUpdateFormRequest $request)
    // {
    // 	$payload = $request->validated();

    // 	$result = DB::transaction(function () use ($payload)
    // 	{
    // 		$exportHub = ExportHub::where('id', $payload['id'])
    // 			->withTrashed()
    // 			->firstOrThrowError();

    // 		$exportHub->update([
    // 			'name' => $payload['name'],
    // 		]);

    // 		return $exportHub;
    // 	});

    // 	return self::successResponse('Success', $result);
    // }

    public function delete(ExportHubDeleteFormRequest $request)
    {
        $payload = $request->validated();

        $exportHub = ExportHub::where('id', $payload['id'])
            ->withTrashed()
            ->firstOrThrowError();

        $result = DB::transaction(function () use ($exportHub) {
            $exportHub->update([
                'status_key' => ExportHub::STATUS_REMOVED,
            ]);

            $result = $exportHub->delete();

            if ($exportHub->file) {
                $exportHub->file->prune();
            }

            return $result;
        });

        return self::successResponse('Success', $result);
    }
}
