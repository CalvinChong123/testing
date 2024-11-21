<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Pylon\Models\Traits\HasModelableFile;
use Pylon\Models\Traits\HasModelKit;
use Pylon\Models\Traits\HasSoftDeletesActivity;
use Pylon\Models\Traits\HasSortColumn;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;


class PromotionCreditApprovalReport extends Model
{
    use HasFactory;
    use HasModelableFile;
    use HasModelKit;
    use HasSoftDeletesActivity;
    use HasSortColumn;
    use SoftDeletes;

    const STATUS_PENDING = 'Pending';

    const STATUS_REJECTED = 'Rejected';

    const STATUS_APPROVED = 'Approved';



    const STATUS = [
        self::STATUS_PENDING,
        self::STATUS_REJECTED,
        self::STATUS_APPROVED,
    ];

    protected $fillable = [
        'user_id',
        'game_ids',
        'promotion_credit_tier_id',
        'promotion_credit_gains',
        'total_credit_bet_amount',
        'status',
        'remark',
        'admin_id',
    ];

    protected $appends = [
        'created_at_date_time'
    ];

    // #Append
    public function getCreatedAtDateTimeAttribute()
    {
        return $this->created_at->format('d-n-Y h:iA');
    }


    // Relationships
    public function userPromotionCreditBalances()
    {
        return $this->belongTo(UserPromotionCreditBalance::class, 'modelable_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function promotionCreditTier()
    {
        return $this->belongsTo(PromotionCreditTier::class);
    }
}
