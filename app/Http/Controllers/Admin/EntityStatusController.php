<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EntityStatus\CreateFormRequest as EntityStatusCreateFormRequest;
use App\Http\Requests\Admin\EntityStatus\DeleteFormRequest as EntityStatusDeleteFormRequest;
use App\Models\EntityStatus;
use Illuminate\Support\Facades\Auth;

class EntityStatusController extends Controller
{
    public function create(EntityStatusCreateFormRequest $request)
    {
        $payload = $request->validated();

        $modelableType = 'App\\Models\\' . $payload['modelable'];
        $modelableId = $payload['id'];
        $status = $payload['status'];
        $remark = $payload['remark'];

        $result = EntityStatus::create([
            'modelable_type' => $modelableType,
            'modelable_id' => $modelableId,
            'status' => $status,
            'remarks' => $remark,
            'created_by_admin_id' => Auth::id(),
        ]);

        return self::successResponse('Success', $result);
    }

    public function delete(EntityStatusDeleteFormRequest $request)
    {
        $payload = $request->validated();

        $entityStatus = EntityStatus::where('id', $payload['id'])
            ->withTrashed()
            ->firstOrThrowError();

        $entityStatus->deleted_by_admin_id = Auth::id();
        $entityStatus->save();

        $result = $entityStatus->restoreOrDelete();

        return self::successResponse('Success', $result);
    }
}
