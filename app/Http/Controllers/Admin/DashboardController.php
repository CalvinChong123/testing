<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Dashboard\GetDashboardFormRequest;
use App\Library\PermissionTag;
use App\Models\User;
use App\Models\Transaction;
use App\Models\CashFloat;


class DashboardController extends Controller
{

    public function getDashboard(GetDashboardFormRequest $request)
    {
        $lastEndShift = $this->getLastEndShift();

        $todayTotalCreditTopUp = $this->getTodayTransactionAmount(Transaction::TYPE_TOPUP, $lastEndShift) ?? 0;
        $todayTotalWithdrawal = $this->getTodayTransactionAmount(Transaction::TYPE_WITHDRAWAL, $lastEndShift) ?? 0;
        $todayCashFloats = $this->getTodayCashFloats($lastEndShift);
        $cashFloatReplenishmentTotal = $todayCashFloats->sum('total_cash_replenishment') ?? 0;
        // $todayTotalUserCount = $this->getTotalUserCount($lastEndShift) ?? 0;
        // $totalUserCount = User::role('user')->count();

        $response['today'] = now()->format('J-n-Y');
        $response['data'] = [
            [
                'name' => 'Today Sales',
                'value' => 'RM' . $todayTotalCreditTopUp,
                'route' => 'topup.list',
                'mdi' => 'mdi-cash',
            ],
            [
                'name' => 'Today Withdrawal',
                'value' => 'RM' . $todayTotalWithdrawal,
                'route' => 'withdrawal.list',
                'mdi' => 'mdi-cash-refund',
            ],
            [
                'name' => 'Total Cash Float Replenishment',
                'value' => 'RM' . $cashFloatReplenishmentTotal,
                'route' => 'cash-float.list',
                'mdi' => 'mdi-cash-multiple',
            ],
            // [
            //     'name' => 'Today Total User Count',
            //     'value' => $todayTotalUserCount,
            //     'route' => 'user.list',
            //     'mdi' => 'mdi-account-group',
            // ],
            // [
            //     'name' => 'Total User Count',
            //     'value' => $totalUserCount,
            //     'route' => 'user.list',
            //     'mdi' => 'mdi-account-group',
            // ],

        ];

        return self::successResponse('Success', $response);
    }

    private function getLastEndShift()
    {
        return CashFloat::query()
            ->where('end_shift_today', true)
            ->orderBy('id', 'desc')
            ->first();
    }

    private function getTodayTransactionAmount($type, $lastEndShift)
    {
        $query = Transaction::query()->where('type', $type);

        if ($lastEndShift) {
            $query->where('created_at', '>', $lastEndShift->end_time);
        }

        return $query->sum('credit');
    }

    private function getTodayCashFloats($lastEndShift)
    {
        $query = CashFloat::query()->with('cashReplenishments');

        if ($lastEndShift) {
            $query->where('created_at', '>', $lastEndShift->end_time);
        }

        return $query->get();
    }

    // private function getTotalUserCount($lastEndShift)
    // {
    //     $query = User::query();

    //     if ($lastEndShift) {
    //         $query->where('created_at', '>', $lastEndShift->end_time);
    //     }

    //     return $query->count();
    // }
}
