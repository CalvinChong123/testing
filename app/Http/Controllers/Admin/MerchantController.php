<?php

namespace App\Http\Controllers\Admin;

use App\Events\MerchantsBroadcastEvent;
use App\Exceptions\BadRequestException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Merchant\CallMerchantFormRequest as MerchantCallMerchantFormRequest;
use App\Http\Requests\Admin\Merchant\CreateFormRequest as MerchantCreateFormRequest;
use App\Http\Requests\Admin\Merchant\CurrentMerchantUserFormRequest;
use App\Http\Requests\Admin\Merchant\DeleteFormRequest as MerchantDeleteFormRequest;
use App\Http\Requests\Admin\Merchant\InfoFormRequest as MerchantInfoFormRequest;
use App\Http\Requests\Admin\Merchant\ListFormRequest as MerchantListFormRequest;
use App\Http\Requests\Admin\Merchant\PingMachineFormRequest as MerchantPingMachineFormRequest;
use App\Http\Requests\Admin\Merchant\TopupFormRequest as MerchantTopupFormRequest;
use App\Http\Requests\Admin\Merchant\UpdateFormRequest as MerchantUpdateFormRequest;
use App\Http\Requests\Admin\Merchant\CurrentMerchantUserFormRequest as MerchantCurrentMerchantUserFormRequest;
use App\Http\Requests\Admin\Merchant\WithdrawalFormRequest as MerchantWithdrawalFormRequest;
use App\Http\Resources\Admin\MerchantResource;
use App\Http\Services\MerchantService;
use App\Library\MerchantCommandTag;
use App\Models\Merchant;
use App\Models\UserPromotionCreditBalance;
use App\Models\ModelableFile;
use App\Queriplex\MerchantQueriplex;
use Illuminate\Support\Facades\DB;


class MerchantController extends Controller
{
    public function list(MerchantListFormRequest $request)
    {

        // return MerchantService::generateInvoice();

        $payload = $request->validated();
        $payload['sort_by'] = 'created_time';
        $merchants = MerchantQueriplex::make(Merchant::query(), $payload)
            ->paginate($payload['items_per_page'] ?? 15);

        $merchants->load(['image']);

        $result = MerchantResource::paginateCollection($merchants);

        $response = [
            'merchants' => $result,
        ];

        return self::successResponse('Success', $response);
    }

    public function info(MerchantInfoFormRequest $request)
    {
        $payload = $request->validated();

        $merchant = MerchantQueriplex::make(Merchant::query(), $payload)
            ->withTrashed()
            ->firstOrThrowError();

        $merchant->load(['image']);

        $result = new MerchantResource($merchant);

        $response = [
            'merchant' => $result,
        ];

        return self::successResponse('Success', $response);
    }

    public function create(MerchantCreateFormRequest $request)
    {
        $payload = $request->validated();

        $merchantData = MerchantService::findMerchant($payload['asset_no']);

        if (!$merchantData) {
            $error['asset_no'] = ['Machine Not Found'];
            self::customValidationException($error);
        }

        $result = DB::transaction(function () use ($payload, $merchantData) {
            $merchant = Merchant::create([
                'name' => $payload['name'],
                'merchant_group_id' => $payload['merchant_group'],
                'asset_no' => $payload['asset_no'],
                'cid0' => $merchantData['cid0'],
                'cid1' => $merchantData['cid1'],
                'cid2' => $merchantData['cid2'],
                'ip_address' => $merchantData['net_ip'],
            ]);
            $merchant->syncResizedImageFor('image', $payload['image'], ModelableFile::MODULE_PATH_MERCHANT_IMAGE, 2000);

            return $merchant;
        });

        return self::successResponse('Success', $result);
    }

    public function update(MerchantUpdateFormRequest $request)
    {
        $payload = $request->validated();

        $merchantData = MerchantService::findMerchant($payload['asset_no']);

        if (!$merchantData) {
            $error['asset_no'] = ['Machine Not Found'];
            self::customValidationException($error);
        }

        $result = DB::transaction(function () use ($payload) {
            $merchant = Merchant::where('id', $payload['id'])
                ->withTrashed()
                ->firstOrThrowError();

            $merchant->update([
                'name' => $payload['name'],
                'merchant_group_id' => $payload['merchant_group'],
                'cid0' => $payload['cid0'],
                'cid1' => $payload['cid1'],
                'cid2' => $payload['cid2'],
            ]);

            $merchant->syncResizedImageFor('image', $payload['image'], ModelableFile::MODULE_PATH_MERCHANT_IMAGE, 2000);

            return $merchant;
        });

        return self::successResponse('Success', $result);
    }

    public function delete(MerchantDeleteFormRequest $request)
    {
        $payload = $request->validated();

        $merchant = Merchant::where('id', $payload['id'])
            ->withTrashed()
            ->firstOrThrowError();

        $result = $merchant->restoreOrDelete();

        return self::successResponse('Success', $result);
    }

    // public function currentMerchantUser(CurrentMerchantUserFormRequest $request)
    // {
    //     $payload = $request->validated();

    //     $merchant = Merchant::find($payload['merchant_id']);

    //     $result = $merchant->currentMerchantUser();

    //     return self::successResponse('Success', $result);
    // }

    public function pingMerchant(MerchantPingMachineFormRequest $request)
    {
        $payload = $request->validated();

        $id = Merchant::where('cid0', $payload['cid0']
            ->where('cid1', $payload['cid1'])
            ->where('cid2', $payload['cid2'])
            ->first())->id;

        $result = MerchantService::sendCommand($id, MerchantCommandTag::PING, $payload['cid0'], $payload['cid1'], $payload['cid2']);

        if ($result == null) {
            throw new BadRequestException('Connection Fail');
        }

        return self::successResponse('Connection Success', $result);
    }

    public function topup(MerchantTopupFormRequest $request)
    {
        $payload = $request->validated();
        $merchant = Merchant::find($payload['merchant_id']);

        $payload['cid0'] = $merchant->cid0;
        $payload['cid1'] = $merchant->cid1;
        $payload['cid2'] = $merchant->cid2;

        if ($payload['payment_method'] == 'Promotion Credit') {
            $userPromotionCredit = UserPromotionCreditBalance::where('user_id', $payload['user_id'])
                ->latest()
                ->first();

            if ($userPromotionCredit == null || $userPromotionCredit->promotion_credit_balance_after_activity < $payload['amount']) {
                throw new BadRequestException('Promotion Credit Balance Not Enough');
            }
        }


        $result = MerchantService::createTopupTransaction($payload);

        return self::successResponse('Success', $result);
    }

    public function withdrawal(MerchantWithdrawalFormRequest $request)
    {
        $payload = $request->validated();

        $merchant = Merchant::find($payload['merchant_id']);


        $payload['cid0'] = $merchant->cid0;
        $payload['cid1'] = $merchant->cid1;
        $payload['cid2'] = $merchant->cid2;

        $result = MerchantService::createWithdrawalTransaction($payload);

        return self::successResponse('Success', $result);
    }

    public function call(MerchantCallMerchantFormRequest $request)
    {
        $payload = $request->validated();
        $merchant = Merchant::find($payload['id']);

        $cid0 = $merchant->cid0;
        $cid1 = $merchant->cid1;
        $cid2 = $merchant->cid2;

        $amount = $payload['amount'] ?? null;
        $merchant_command = $payload['merchant_command'];

        $result = MerchantService::sendCommand($payload['id'], $merchant_command, $cid0, $cid1, $cid2, $amount);

        if ($result == null) {
            throw new BadRequestException('Machine Connection Fail');
        }

        return self::successResponse('Success', $result);
    }

    public function broadcast()
    {
        event(new MerchantsBroadcastEvent([]));
    }
}
