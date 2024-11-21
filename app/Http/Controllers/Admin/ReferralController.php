<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Referral\ListFormRequest as ReferralListFormRequest;
use App\Http\Resources\Admin\ReferralResource;
use App\Models\Referral;
use App\Queriplex\ReferralQueriplex;

class ReferralController extends Controller
{
    public function list(ReferralListFormRequest $request)
    {
        $payload = $request->validated();
        $payload['sort_by'] = 'created_time';
        $referrals = ReferralQueriplex::make(Referral::query(), $payload)
            ->paginate($payload['items_per_page'] ?? 15);

        $referrals->load(['user', 'referrer']);

        $result = ReferralResource::paginateCollection($referrals);

        $response = [
            'referrals' => $result,
        ];

        return self::successResponse('Success', $response);
    }
}
