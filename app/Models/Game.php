<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Game extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'top_up_transaction_ids',
        'withdraw_transaction_id',
        'point_gains',
        'referral_point_gains',
        'credit_bet_amount',
        'promotion_credit_bet_amount',
        'total_credit_top_up',
        'total_promotion_credit_top_up',
        'withdrawal_amount',
        'total_win',
        'total_game'

    ];


    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getMerchantAttribute()
    {
        $withdrawTransaction = $this->withdrawTransaction()->with('merchant')->first();
        if ($withdrawTransaction && $withdrawTransaction->merchant) {
            return $withdrawTransaction->merchant;
        }

        $topUpTransaction = $this->topUpTransactions()->with('merchant')->first();
        if ($topUpTransaction && $topUpTransaction->merchant) {
            return $topUpTransaction->merchant;
        }

        return null;
    }

    public function topUpTransactions()
    {
        return $this->belongsToMany(Transaction::class, 'top_up_transaction_ids');
    }

    public function withdrawTransaction()
    {
        return $this->belongsTo(Transaction::class, 'withdraw_transaction_id');
    }
}
