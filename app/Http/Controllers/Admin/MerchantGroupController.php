<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MerchantGroup\CreateFormRequest as MerchantGroupCreateFormRequest;
use App\Http\Requests\Admin\MerchantGroup\DeleteFormRequest as MerchantGroupDeleteFormRequest;
use App\Http\Requests\Admin\MerchantGroup\InfoFormRequest as MerchantGroupInfoFormRequest;
use App\Http\Requests\Admin\MerchantGroup\ListFormRequest as MerchantGroupListFormRequest;
use App\Http\Requests\Admin\MerchantGroup\UpdateFormRequest as MerchantGroupUpdateFormRequest;
use App\Http\Requests\Admin\MerchantGroup\MerchantListFormRequest as MerchantGroupMerchantListFormRequest;
use App\Http\Resources\Admin\MerchantGroupResource;
use App\Http\Resources\Admin\MerchantResource;
use App\Models\MerchantGroup;
use App\Models\Merchant;
use App\Queriplex\MerchantQueriplex;
use App\Queriplex\MerchantGroupQueriplex;
use Illuminate\Support\Facades\DB;

class MerchantGroupController extends Controller
{
    public function list(MerchantGroupListFormRequest $request)
    {
        $payload = $request->validated();
        $payload['sort_by'] = 'created_time';
        $merchantGroups = MerchantGroupQueriplex::make(MerchantGroup::query(), $payload)
            ->paginate($payload['items_per_page'] ?? 15);

        $merchantGroups->load(['merchants', 'merchants.image']);

        $result = MerchantGroupResource::paginateCollection($merchantGroups);

        $response = [
            'merchant_groups' => $result,
        ];

        return self::successResponse('Success', $response);
    }

    public function info(MerchantGroupInfoFormRequest $request)
    {
        $payload = $request->validated();

        $merchantGroup = MerchantGroupQueriplex::make(MerchantGroup::query(), $payload)
            ->withTrashed()
            ->firstOrThrowError();

        $merchantGroup->load(['merchants', 'merchants.image']);

        $result = new MerchantGroupResource($merchantGroup);

        $response = [
            'merchant_group' => $result,
        ];

        return self::successResponse('Success', $response);
    }

    public function create(MerchantGroupCreateFormRequest $request)
    {
        $payload = $request->validated();

        $result = DB::transaction(function () use ($payload) {
            $merchantGroup = MerchantGroup::create([
                'name' => $payload['name'],
                'spending_credits' => $payload['spending_credits'],
                'earning_points' => $payload['earning_points'],

            ]);

            return $merchantGroup;
        });

        return self::successResponse('Success', $result);
    }

    public function update(MerchantGroupUpdateFormRequest $request)
    {
        $payload = $request->validated();

        $result = DB::transaction(function () use ($payload) {
            $merchantGroup = MerchantGroup::where('id', $payload['id'])
                ->withTrashed()
                ->firstOrThrowError();

            $merchantGroup->update([
                'name' => $payload['name'],
                'spending_credits' => $payload['spending_credits'],
                'earning_points' => $payload['earning_points'],
            ]);

            return $merchantGroup;
        });

        return self::successResponse('Success', $result);
    }

    public function delete(MerchantGroupDeleteFormRequest $request)
    {
        $payload = $request->validated();

        $merchantGroup = MerchantGroup::where('id', $payload['id'])
            ->withTrashed()
            ->firstOrThrowError();

        $result = $merchantGroup->restoreOrDelete();

        return self::successResponse('Success', $result);
    }


    public function merchantList(MerchantGroupMerchantListFormRequest $request)
    {
        $payload = $request->validated();

        $merchants = MerchantQueriplex::make(Merchant::query(), $payload)
            // ->where('merchant_group_id', $payload['id'])
            ->paginate($payload['items_per_page'] ?? 15);

        $merchants->load(['image']);

        $result = MerchantResource::paginateCollection($merchants);

        $response = [
            'merchants' => $result,
        ];

        return self::successResponse('Success', $response);
    }
}
