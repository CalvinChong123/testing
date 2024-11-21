<?php

namespace App\Http\Services;

use App\Exceptions\BadRequestException;
use App\Library\RoleTag;
use App\Models\ModelableFile;
use App\Models\User;
use App\Models\UserPointBalance;
use App\Notifications\VerifyAccountNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Swift_TransportException;
use App\Models\CashFloat;
use App\Models\Config;
use App\Models\PromotionCreditApprovalReport;
use App\Models\UserPromotionCreditBalance;
use App\Models\Transaction;

class UserService
{
    public static function create($payload)
    {
        $path = '/api/user/create';
        return SyncService::request('post', $path, $payload); // Return the response directly
    }

    public static function update($payload)
    {
        $path = '/api/user/update';
        return SyncService::request('post', $path, $payload);
    }

    public static function delete(User $user)
    {
        $user->restoreOrDelete();

        return $user;
    }

    public static function info($userId)
    {
        $path = "/api/user/info/{$userId}";
        $response = SyncService::request('put', $path, null);

        if ($response && isset($response['data'])) {
            return $response['data'];
        } else {
            throw new BadRequestException('Failed to fetch user details from HQ');
        }
    }

    public static function updateUserPointBalance(UserPointBalance $userPointBalance = null, array $payload)
    {
        $pointBalanceAfterActivity = $userPointBalance ? $userPointBalance->point_balance_after_activity + $payload['point'] : $payload['point'];
        $totalPointBalanceThisYear = $userPointBalance ? $userPointBalance->total_point_balance_this_year + $payload['point'] : $payload['point'];

        if ($payload['type'] == 'Deduct' || $payload['type'] == 'Redeem') {
            $pointBalanceAfterActivity = $userPointBalance->point_balance_after_activity - $payload['point'];
            $totalPointBalanceThisYear = $userPointBalance->total_point_balance_this_year;
        }

        $result = DB::transaction(function () use ($userPointBalance, $payload, $pointBalanceAfterActivity, $totalPointBalanceThisYear) {
            $newUserPointBalance = UserPointBalance::create([
                'user_id' =>  $payload['user_id'],
                'type' => $payload['type'],
                'point_amount' => $payload['point'],
                'referral_point_amount' => 0,
                'point_balance_before_activity' => $userPointBalance->point_balance_after_activity ?? 0,
                'point_balance_after_activity' => $pointBalanceAfterActivity,
                'total_point_balance_this_year' => $totalPointBalanceThisYear,
            ]);

            if ($payload['type'] == 'Redeem') {
                $config = Config::where('name', 'Point To Credit Value')->first();
                $pointConfig = $config->points ?? 0;
                $creditConfig = $config->credits ?? 0;
                $newUserPointBalance->update([
                    'credit_earned' => $payload['point'] / $pointConfig * $creditConfig,
                ]);
            }
            return $newUserPointBalance;
        });
        return $result;
    }

    public static function updateUserPromotionCreditBalance($type, PromotionCreditApprovalReport $promotionCreditApprovalReport = null, Transaction $transaction = null)
    {
        $userPromotionCreditBalance = UserPromotionCreditBalance::where('user_id', $promotionCreditApprovalReport->user_id)
            ->latest()
            ->first();

        $userPromotionCreditBalanceAfterActivity = $userPromotionCreditBalance ? $userPromotionCreditBalance->promotion_credit_balance_after_activity : 0;

        if ($type == UserPromotionCreditBalance::TYPE_EARN) {
            $result = DB::transaction(function () use ($userPromotionCreditBalanceAfterActivity, $promotionCreditApprovalReport) {
                $newUserPromotionCreditBalance = UserPromotionCreditBalance::create([
                    'user_id' =>  $promotionCreditApprovalReport->user_id,
                    'type' => UserPromotionCreditBalance::TYPE_EARN,
                    'promotion_credit_amount' => $promotionCreditApprovalReport->promotion_credit_gains,
                    'promotion_credit_balance_before_activity' => $userPromotionCreditBalanceAfterActivity,
                    'promotion_credit_balance_after_activity' => $userPromotionCreditBalanceAfterActivity + $promotionCreditApprovalReport->promotion_credit_gains,
                    'modelable_type' => PromotionCreditApprovalReport::class,
                    'modelable_id' => $promotionCreditApprovalReport->id,
                ]);

                return $newUserPromotionCreditBalance;
            });
        } else {
            $result = DB::transaction(
                function () use ($userPromotionCreditBalanceAfterActivity, $transaction) {
                    $newUserPromotionCreditBalance = UserPromotionCreditBalance::create([
                        'user_id' =>  $transaction->user_id,
                        'type' => UserPromotionCreditBalance::TYPE_TOPUP,
                        'promotion_credit_amount' => $transaction->credit,
                        'promotion_credit_balance_before_activity' => $userPromotionCreditBalanceAfterActivity,
                        'promotion_credit_balance_after_activity' => $userPromotionCreditBalanceAfterActivity + $transaction->credit,
                        'modelable_type' => Transaction::class,
                        'modelable_id' => $transaction->id,
                    ]);

                    return $newUserPromotionCreditBalance;
                }
            );
        }
    }
}
