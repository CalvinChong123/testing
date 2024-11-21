<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MerchantReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'merchant_id',
        'transaction_id',
        'command',
        'event_name',
        'response',
        'user_id'
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
