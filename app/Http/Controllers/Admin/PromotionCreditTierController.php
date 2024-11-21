<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PromotionCreditTier\UpdateFormRequest as PromotionCreditTierUpdateFormRequest;
use App\Http\Resources\Admin\PromotionCreditTierResource;
use App\Models\PromotionCreditTier;
use Illuminate\Support\Facades\DB;

class PromotionCreditTierController extends Controller
{
    public function list()
    {

        $promotions = PromotionCreditTier::query()->get();

        $result = PromotionCreditTierResource::collection($promotions);

        $response = [
            'promotions' => $result,
        ];

        return self::successResponse('Success', $response);
    }

    public function update(PromotionCreditTierUpdateFormRequest $request)
    {
        $payload = $request->validated();

        $result = DB::transaction(function () use ($payload) {
            $updatedPromotions = [];

            foreach ($payload as $tierData) {
                $promotion = PromotionCreditTier::where('id', $tierData['id'])
                    ->firstOrFail();

                $promotion->update([
                    'name' => $tierData['name'],
                    'total_bet' => $tierData['total_spend'],
                    'promotion_credit_earn' => $tierData['credit_earn'],
                ]);

                $updatedPromotions[] = $promotion;
            }

            return $updatedPromotions;
        });

        return self::successResponse('Success', $result);
    }
}
