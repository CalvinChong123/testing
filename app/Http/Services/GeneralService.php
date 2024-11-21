<?php

namespace App\Http\Services;

use App\Exceptions\BadRequestException;
use App\Library\MerchantCommandTag;
use App\Models\MerchantReport;
use App\Models\Transaction;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Merchant;
use App\Models\CashFloat;
use App\Models\UserPointBalance;
use App\Models\Game;
use App\Models\Referral;
use App\Models\Config;
use App\Models\Notification;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\UserPromotionCreditBalance;
use App\Models\PromotionCreditTier;
use App\Models\PromotionCreditApprovalReport;


class GeneralService
{


    public static function getCurrentShift()
    {
        $user = auth()->user();

        $now = now()->format('Y-m-d H:i:s');


        $lastCashFlow = CashFloat::orderBy('created_at', 'desc')->first();

        $defaultShift = 1;
        $newShift = true;

        if ($lastCashFlow && is_null($lastCashFlow->end_time)) {
            $defaultShift = $lastCashFlow->shift_no;
            $newShift = false;
        } elseif ($lastCashFlow && $lastCashFlow->end_time && !$lastCashFlow->end_shift_today) {
            $defaultShift = $lastCashFlow->shift_no + 1;
        }
        return [
            'id' => $lastCashFlow->id ?? null,
            'user' => $user,
            'shift_no' => $defaultShift,
            'new_shift' => $newShift,
            'date' => $now,
            'cash_in' => !$newShift ? $lastCashFlow->cash_in : "-",
        ];
    }
    public static function generatePromotionCreditReport()
    {
        $todayShift = CashFloat::latestShiftStartEndTime();
        $games = Game::whereBetween('created_at', [$todayShift['start_time'], $todayShift['end_time']])->get();

        $usersGameGrouped = $games->groupBy('user_id');

        foreach ($usersGameGrouped as $userId => $userGames) {
            log::info($userGames);
            $totalCreditBetAmount = $userGames->sum('credit_bet_amount');
            $merchant = $userGames->first()->merchant;
            $promotionCreditTier = PromotionCreditTier::getPromotionCreditEarnAndTierByTotalBet($totalCreditBetAmount);
            if ($promotionCreditTier['promotion_credit_earn'] == 0) {
                continue;
            }

            $gameIds = json_encode($userGames->pluck('id')->toArray());
            $result = DB::transaction(function () use ($userId, $promotionCreditTier, $gameIds, $totalCreditBetAmount, $merchant) {
                $report = PromotionCreditApprovalReport::create([
                    'user_id' => $userId,
                    'game_ids' => $gameIds,
                    'promotion_credit_tier_id' => $promotionCreditTier['id'],
                    'promotion_credit_gains' => $promotionCreditTier['promotion_credit_earn'],
                    'total_credit_bet_amount' => $totalCreditBetAmount,
                    'status' => PromotionCreditApprovalReport::STATUS_PENDING,
                    'merchant_id' => $merchant->id,

                ]);
                log::info('success');
                return $report;
            });
        }
    }

    // public static function approveApprovalRequest($payload) {}
    // public static function updateConfig($payload)
    // {
    //     $config = Config::where('id', $payload['id'])
    //         ->firstOrThrowError();

    //     $result = DB::transaction(function () use ($payload) {
    //         $config = Config::where('id', $payload['id'])
    //             ->firstOrThrowError();
    //         $config->update([
    //             'months' => $payload['months'],
    //             'days' => $payload['days'],
    //             'credits' => $payload['credits'],
    //             'points' => $payload['points'],
    //             'value' => $payload['value'],
    //         ]);
    //         return $config;
    //     });
    //     return $result;
    // }
}
