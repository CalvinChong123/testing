<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Config\UpdateFormRequest as ConfigUpdateFormRequest;
use App\Http\Requests\Admin\Config\InfoFormRequest as ConfigInfoFormRequest;
use App\Http\Resources\Admin\ConfigResource;
use App\Models\Config;
use Illuminate\Support\Facades\DB;
use App\Http\Services\ApprovalService;
use App\Models\ApprovalActivity;

class ConfigController extends Controller
{
    public function list()
    {

        $configs = Config::all();

        $result = ConfigResource::collection($configs);

        $response = [
            'configs' => $result,
        ];

        return self::successResponse('Success', $response);
    }

    public function info(ConfigInfoFormRequest $request)
    {
        $payload = $request->validated();

        $config = Config::where('id', $payload['id'])
            ->firstOrThrowError();

        $result = new ConfigResource($config);

        $response = [
            'config' => $result,
        ];

        return self::successResponse('Success', $response);
    }

    // public function update(ConfigUpdateFormRequest $request)
    // {
    //     $payload = $request->validated();
    //     $config = Config::where('id', $payload['id'])
    //         ->firstOrThrowError();

    //     $errors = [];

    //     if ($config->name == Config::NAME_REFERRAL_VALIDITY_PERIOD && $payload['months'] == null) {
    //         $errors['months'] = 'The months field is required.';
    //     }
    //     if ($config->name == 'Data Auto Purge Period (number of days)' && $payload['days'] == null) {
    //         $errors['days'] = 'The days field is required.';
    //     }
    //     if ($config->name == 'Point To Credit Value' || $config->name == 'Spending To Referral Point Value') {
    //         if ($payload['credits'] == null) {
    //             $errors['credits'] = 'The credits field is required.';
    //         }
    //         if ($payload['points'] == null) {
    //             $errors['points'] = 'The points field is required.';
    //         }
    //     }
    //     if ($config->name == 'Outlet Name' && $payload['value'] == null) {
    //         $errors['value'] = 'The value field is required.';
    //     }

    //     if (!empty($errors)) {
    //         return self::customValidationException($errors);
    //     }

    //     $result = DB::transaction(function () use ($payload) {
    //         $config = Config::where('id', $payload['id'])
    //             ->firstOrThrowError();
    //         $config->update([
    //             'months' => $payload['months'],
    //             'days' => $payload['days'],
    //             'credits' => $payload['credits'],
    //             'points' => $payload['points'],
    //         ]);
    //         return $config;
    //     });

    //     return self::successResponse('Success', $result);
    // }

    public function update(ConfigUpdateFormRequest $request)
    {
        $payload = $request->validated();
        $config = Config::where('id', $payload['id'])->firstOrThrowError();

        // Validation logic
        $errors = [];

        if ($config->name == Config::NAME_REFERRAL_VALIDITY_PERIOD && $payload['months'] == null) {
            $errors['months'] = 'The months field is required.';
        }
        if ($config->name == Config::NAME_DATA_AUTO_PURGE_PERIOD && $payload['days'] == null) {
            $errors['days'] = 'The days field is required.';
        }
        if ($config->name == Config::NAME_POINT_TO_CREDIT_VALUE || $config->name == Config::NAME_SPENDING_TO_REFERRAL_POINT_VALUE) {
            if ($payload['credits'] == null) {
                $errors['credits'] = 'The credits field is required.';
            }
            if ($payload['points'] == null) {
                $errors['points'] = 'The points field is required.';
            }
        }
        if ($config->name == Config::NAME_OUTLET_NAME && $payload['outlet_name'] == null) {
            $errors['outlet_name'] = 'The outlet name field is required.';
        }

        if (!empty($errors)) {
            return self::customValidationException($errors);
        }

        $approvalRequest = ApprovalService::createApprovalRequest($config, $payload, ApprovalActivity::NAME_CONFIG_SETTING);

        return self::successResponse('Approval request created successfully', $approvalRequest);
    }
}
