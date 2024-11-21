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


class MerchantService
{
    public static function findMerchant($asset_no)
    {
        $broadcastAddress = '255.255.255.255';
        $timeout = 10;
        $port = 30624;
        $command = '{"cmd":"browse"}';

        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => 1, 'usec' => 0]); // 1-second receive timeout per loop

        $socketOpen = true;

        try {
            socket_bind($socket, '0.0.0.0', $port);

            $startTime = time();
            $response = '';
            $from = '';
            $remotePort = 0;

            while ((time() - $startTime) < $timeout) {
                socket_sendto($socket, $command, strlen($command), 0, $broadcastAddress, $port);

                $recvResult = socket_recvfrom($socket, $response, 2048, 0, $from, $remotePort);

                if ($recvResult === false) {
                    continue;
                }

                // Decode the response as JSON
                $merchantData = json_decode($response, true);

                // Check if the response contains the asset number we're looking for
                if (isset($merchantData['asset']) && $merchantData['asset'] == $asset_no) {
                    if ($socketOpen) {
                        socket_close($socket); // Close socket if it's still open
                        $socketOpen = false;
                    }
                    return $merchantData;
                }
            }


            return null;
        } catch (Exception $e) {
            Log::error('Error: ' . $e->getMessage());
            throw $e;
        } finally {
            // Ensure socket is closed only if it's still open
            if ($socketOpen) {
                socket_close($socket);
            }
        }
    }




    public static function sendCommand($id, $cmd, $cid0, $cid1, $cid2, $cash = null)
    {
        // merchant command need interval atleast 0.5 second to avoid merchant data return not accurate
        usleep(600000);  // 0.6 second

        $broadcastAddress = '255.255.255.255';

        $timeout = 10;

        $port = 30624;

        $command = json_encode(array_filter([
            'cmd' => $cmd,
            'cid0' => $cid0,
            'cid1' => $cid1,
            'cid2' => $cid2,
            'amount' => $cash,
        ], function ($value) {
            return $value !== null;
        }));


        $merchant = Merchant::find($id);
        $currentUserId = $merchant->currentMerchantUser()['user']['id'] ?? null;
        $whoCanSeeNotification = Notification::whoCanSee(\App\Library\PermissionTag::NOTIFICATION_MERCHANT_MODULE);
        $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
        socket_set_option($socket, SOL_SOCKET, SO_BROADCAST, 1);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $timeout, 'usec' => 0]);

        try {
            socket_bind($socket, '0.0.0.0', $port);
            socket_sendto($socket, $command, strlen($command), 0, $broadcastAddress, $port);

            $response = '';
            $from = '';
            $remotePort = 0;
            $receiveCount = 0;

            while (true) {
                $recvResult = socket_recvfrom($socket, $response, 2048, 0, $from, $remotePort);

                if ($recvResult === false) {
                    throw new BadRequestException('Error receiving response');
                }

                $receiveCount++;

                if ($receiveCount == 2) {
                    $response = json_decode($response, true);

                    // if ($cmd === MerchantCommandTag::CASH_IN || $cmd === MerchantCommandTag::CASH_OUT) {
                    //     self::createMerchantReport($id, $id, $cmd, $response);
                    // } else {
                    //     self::createMerchantReport(null, $id, $cmd, $response);
                    // }
                    self::createMerchantReport($currentUserId, $id, $cmd, $response);

                    foreach ($whoCanSeeNotification as $user) {
                        Notification::create([
                            'admin_id' => $user->id,
                            'message' => "Merchant $merchant->name success call $cmd",
                            'notification_type' => 'System',
                        ]);
                    }

                    return $response;
                }
            }
        } catch (Exception $e) {
            foreach ($whoCanSeeNotification as $user) {
                Notification::create([
                    'admin_id' => $user->id,
                    'message' => "Merchant $merchant failed call $cmd",
                    'notification_type' => 'System',
                ]);
            }
            Log::error('Error: ' . $e->getMessage());
            throw $e;
        } finally {
            socket_close($socket);
        }
    }

    public static function createTopupTransaction($payload)
    {

        $lastTransaction = Transaction::where('merchant_id', $payload['merchant_id'])
            ->latest()
            ->first();

        return DB::transaction(function () use ($payload, $lastTransaction) {

            if ($lastTransaction && $lastTransaction->type != Transaction::TYPE_WITHDRAWAL && $lastTransaction->user_id != $payload['user_id']) {
                $autoWithdrawPayload = $payload;
                $autoWithdrawPayload['user_id'] = $lastTransaction->user_id;
                self::createWithdrawalTransaction($autoWithdrawPayload);
            }

            $isPromotionCredit = $payload['payment_method'] == Transaction::PAYMENT_METHOD_PROMOTION_CREDIT;
            $amount = $payload['amount'];

            $transaction = Transaction::create([
                'user_id' => $payload['user_id'],
                'merchant_id' => $payload['merchant_id'],
                'type' => Transaction::TYPE_TOPUP,
                'payment_method' => $payload['payment_method'],
                'admin_id' => Auth::id() ?? null,
                'credit' => $isPromotionCredit ? 0 : $amount,
                'promotion_credit' => $isPromotionCredit ? $amount : 0,
            ]);

            if ($isPromotionCredit) {
                UserService::updateUserPromotionCreditBalance(UserPromotionCreditBalance::TYPE_TOPUP, null, $transaction);
            }

            $cashIn = self::sendCommand($payload['merchant_id'], MerchantCommandTag::CASH_IN, $payload['cid0'], $payload['cid1'], $payload['cid2'], $payload['amount'] * 100);

            if (!$cashIn) {
                throw new BadRequestException('Machine Top Up Fail');
            }

            $cashIn['in_machine_balance'] /= 100;

            // SEND METER COMMAND, TO KNOW THE CURRENT BALANCE AND BET
            $meterResult = self::sendCommand($payload['merchant_id'], MerchantCommandTag::METERS, $payload['cid0'], $payload['cid1'], $payload['cid2']);
            if (!$meterResult || $meterResult['in_machine_balance'] == 0) {
                throw new BadRequestException('Top Up Failed');
            }


            $meterResult['bet'] /= 100;
            $meterResult['win'] /= 100;

            $transaction->update([
                'merchant_current_balance_total' => $cashIn['in_machine_balance'],
                'merchant_current_bet_total' => $meterResult['bet'],
                'merchant_current_game_total' => $meterResult['games'],
                'merchant_current_win_total' => $meterResult['win'],
            ]);

            return $meterResult;
        });
    }

    public static function createWithdrawalTransaction($payload)
    {
        $lastWithdraw = Transaction::where('merchant_id', $payload['merchant_id'])
            ->where('type', Transaction::TYPE_WITHDRAWAL)
            ->latest()
            ->first();

        $topupTransactions = Transaction::where('user_id', $payload['user_id'])
            ->where('merchant_id', $payload['merchant_id'])
            ->where('type', Transaction::TYPE_TOPUP)
            ->when($lastWithdraw, function ($query) use ($lastWithdraw) {
                return $query->where('created_at', '>', $lastWithdraw->created_at);
            })
            ->get();

        return DB::transaction(function () use ($payload, $topupTransactions) {
            // SEND METER COMMAND, TO KNOW THE CURRENT BALANCE AND BET, ALSO THIS REFRESH THE METER TO AVOID CASH OUT API FAILED
            $meterResult = self::sendCommand($payload['merchant_id'], MerchantCommandTag::METERS, $payload['cid0'], $payload['cid1'], $payload['cid2']);
            if (!$meterResult) {
                throw new BadRequestException('Machine Connection Fail');
            }

            $meterResult['bet'] /=  100;
            $meterResult['in_machine_balance'] /= 100;
            $meterResult['win'] /= 100;

            $transaction = Transaction::create([
                'user_id' => $payload['user_id'],
                'merchant_id' => $payload['merchant_id'],
                'type' => Transaction::TYPE_WITHDRAWAL,
                'credit' => $meterResult['in_machine_balance'],
                'promotion_credit' => 0,
                'withdrawal_method' => $payload['withdrawal_method'] ?? Transaction::WITHDRAWAL_METHOD_CASH_FLOAT,
                'merchant_current_balance_total' => $meterResult['in_machine_balance'],
                'merchant_current_bet_total' => $meterResult['bet'],
                'merchant_current_game_total' => $meterResult['games'],
                'merchant_current_win_total' => $meterResult['win'],
                'admin_id' => Auth::id() ?? null,
            ]);

            // $transactions = $topupTransactions->clone();
            // $transactions->push($transaction);
            $totalCreditTopup = $topupTransactions->sum('credit');
            $totalPromotionCreditTopup = $topupTransactions->sum('promotion_credit');
            $initialBet = $topupTransactions->first()->merchant_current_bet_total ?? 0;
            $initialWin = $topupTransactions->first()->merchant_current_win_total ?? 0;
            $initialGame = $topupTransactions->first()->merchant_current_game_total ?? 0;
            $totalBet = $meterResult['bet'] - $initialBet;
            $totalWin = $meterResult['win'] - $initialWin;
            $totalGames =  $meterResult['games'] - $initialWin;

            // POINT GAIN CALCULATION
            $merchant = Merchant::find($payload['merchant_id']);
            $merchantGroup = $merchant->merchantGroup;
            $spendingCreditsConfig = $merchantGroup->spending_credits ?? 0;
            $earningPointsConfig = $merchantGroup->earning_points ?? 0;
            $pointGains = $totalBet > 0 ? floor($totalBet / $spendingCreditsConfig) * $earningPointsConfig : 0;

            // REFERRAL POINT GAIN CALCULATION
            $config = Config::where('name', 'Spending To Referral Point Value')->first();
            $spendingCreditsReferralConfig = $config->credits ?? 0;
            $earningReferralPointsConfig = $config->points ?? 0;
            $referralPointGains = $totalBet > 0 ? floor($totalBet / $spendingCreditsReferralConfig) * $earningReferralPointsConfig : 0;

            // CREATE GAME INFO
            $game = Game::create([
                'user_id' => $payload['user_id'],
                'top_up_transaction_ids' => json_encode($topupTransactions->pluck('id')->toArray()),
                'withdraw_transaction_id' => $transaction->id,
                'credit_bet_amount' => $totalBet,
                'promotion_credit_bet_amount' => 0,
                'total_credit_top_up' => $totalCreditTopup,
                'total_promotion_credit_top_up' => $totalPromotionCreditTopup,
                'withdrawal_amount' => $meterResult['in_machine_balance'],
                'point_gains' => $pointGains,
                'referral_point_gains' => $referralPointGains,
                'total_win' => $totalWin,
                'total_game' => $totalGames,
            ]);

            //  CREATE USER POINT BALANCE
            if ($pointGains > 0) {
                $lastUserPointBalance = UserPointBalance::where('user_id', $payload['user_id'])->latest()->first();
                $pointBalanceBefore = $lastUserPointBalance ? $lastUserPointBalance->point_balance_after_activity : 0;
                $referralPointBalanceBefore = $lastUserPointBalance ? $lastUserPointBalance->referral_point_balance_after_activity : 0;
                $totalPointBalanceThisYear = $lastUserPointBalance ? $lastUserPointBalance->total_point_balance_this_year + $pointGains : $pointGains;

                UserPointBalance::create([
                    'user_id' => $payload['user_id'],
                    'type' => UserPointBalance::TYPE_SELF_EARN,
                    'game_id' => $game->id,
                    'point_balance_before_activity' => $pointBalanceBefore,
                    'point_amount' => $pointGains,
                    'referral_point_amount' => 0,
                    'point_balance_after_activity' => $pointBalanceBefore + $pointGains,
                    'total_point_balance_this_year' => $totalPointBalanceThisYear,
                ]);
            }

            //  CREATE REFERRER POINT BALANCE
            if ($referralPointGains > 0) {
                $referrer = Referral::where('user_id', $payload['user_id'])->first()->referrer;
                if ($referrer) {
                    $lastUserPointBalance = UserPointBalance::where('user_id', $referrer->id)->latest()->first();
                    $pointBalanceBefore = $lastUserPointBalance ? $lastUserPointBalance->point_balance_after_activity : 0;
                    $referralPointBalanceBefore = $lastUserPointBalance ? $lastUserPointBalance->referral_point_balance_after_activity : 0;
                    $totalPointBalanceThisYear = $lastUserPointBalance ? $lastUserPointBalance->total_point_balance_this_year + $referralPointGains : $referralPointGains;

                    UserPointBalance::create([
                        'user_id' => $referrer->id,
                        'type' => UserPointBalance::TYPE_REFERRAL,
                        'game_id' => $game->id,
                        'point_balance_before_activity' => $pointBalanceBefore,
                        'point_amount' => 0,
                        'referral_point_amount' => $referralPointGains,
                        'point_balance_after_activity' => $pointBalanceBefore  + $referralPointGains,
                        'total_point_balance_this_year' => $totalPointBalanceThisYear,
                    ]);
                }
            }

            // CASH OUT COMMAND
            $withdraw = self::sendCommand($payload['merchant_id'], MerchantCommandTag::CASH_OUT, $payload['cid0'], $payload['cid1'], $payload['cid2']);
            if (!$withdraw) {
                throw new BadRequestException('Machine Withdrawal Fail');
            }

            $meterResult2 = self::sendCommand(
                $payload['merchant_id'],
                MerchantCommandTag::METERS,
                $payload['cid0'],
                $payload['cid1'],
                $payload['cid2']
            );
            if (!$meterResult2 || $meterResult['in_machine_balance'] != 0) {
                throw new BadRequestException('Machine Connection Fail');
            }

            return $game;
        });
    }



    public static function createMerchantReport($userId, $merchantId, $command, $response)
    {
        MerchantReport::create([
            'merchant_id' => $merchantId,
            'cuser_id' => $userId,
            'command' => $command,
            'response' => json_encode($response),
        ]);
    }

    public static function generateInvoice()
    {
        $data = [
            'company_name' => 'Succeo',
            'date_time' => now()->format('Y-m-d H:i:s'),
            'top_up_price' => 100,
        ];

        $pdf = Pdf::loadView('invoices.topup', $data);

        return $pdf->download('invoice.pdf');
    }
}
