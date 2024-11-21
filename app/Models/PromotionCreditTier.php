<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Pylon\Models\Traits\HasModelableFile;
use Pylon\Models\Traits\HasModelKit;
use Pylon\Models\Traits\HasSortColumn;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;


class PromotionCreditTier extends Model implements AuditableContract
{
    use HasFactory;
    use HasModelableFile;
    use HasModelKit;
    use HasSortColumn;
    use Auditable;

    protected $fillable = [
        'name',
        'total_bet',
        'promotion_credit_earn',
    ];


    // #Method
    public static function getPromotionCreditEarnAndTierByTotalBet($totalBet)
    {
        $tier = self::where('total_bet', '<=', $totalBet)
            ->orderBy('total_bet', 'desc')
            ->first();

        if ($tier) {
            return [
                'promotion_credit_earn' => $tier->promotion_credit_earn,
                'id' => $tier->id,
            ];
        }

        return [
            'promotion_credit_earn' => 0,
            'id' => '',
        ];
    }
}
