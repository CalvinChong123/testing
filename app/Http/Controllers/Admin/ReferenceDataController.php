<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReferenceData\GetInfoBundleFormRequest;
use App\Http\Resources\Admin\SptRoleResource;
use App\Models\SptRole;

class ReferenceDataController extends Controller
{
    public function getInfoBundle(GetInfoBundleFormRequest $request)
    {
        $payload = $request->validated();

        $response = [];

        if (isset($payload['role_id'])) {
            $role = SptRole::find($payload['role_id']);
            $response['role'] = new SptRoleResource($role);
        }

        return self::successResponse('Success', $response);
    }
}
