<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Pylon\Models\Traits\HasModelableFile;
use Pylon\Models\Traits\HasModelKit;
use Pylon\Models\Traits\HasSoftDeletesActivity;
use Pylon\Models\Traits\HasSortColumn;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use App\Models\Config;

class Referral extends Model implements AuditableContract
{
    use HasModelableFile;
    use HasModelKit;
    use HasSoftDeletesActivity;
    use HasSortColumn;
    use SoftDeletes;
    use HasFactory;
    use Auditable;

    protected $fillable = [
        'user_id',
        'referrer_user_id',
        'expired_at',
    ];

    protected $appends = [
        'created_at_date',
        'created_at_time',
        'expired_at_date',
        'month_left',
        'credit_spend_before_referrer_expired',
        'point_earned_for_referrer',
    ];


    static public function calculateExpiryDate($createdAt)
    {
        $validityPeriod = Config::query()
            ->where('name', Config::NAME_REFERRAL_VALIDITY_PERIOD)
            ->value('months');

        $validityPeriod = $validityPeriod - 1 ?? 3;

        return Carbon::parse($createdAt)->addMonths($validityPeriod)->endOfMonth();
    }

    public function getCreditSpendBeforeReferrerExpiredAttribute()
    {
        return $this->games()
            ->whereBetween('created_at', [$this->created_at, $this->expired_at])
            ->sum('credit_bet_amount');
    }

    public function getPointEarnedForReferrerAttribute()
    {
        $config = Config::where('name', 'Spending To Referral Point Value')->first();
        $spendingCreditsReferralConfig = $config->credits ?? 0;
        $earningReferralPointsConfig = $config->points ?? 0;
        $creditSpend = $this->credit_spend_before_referrer_expired;
        $referralPointGains = 0;
        if ($creditSpend > 0) {
            $referralPointGains = floor($creditSpend / $spendingCreditsReferralConfig) * $earningReferralPointsConfig;
        }
        return $referralPointGains;
    }

    public function getCreatedAtDateAttribute()
    {
        return Carbon::parse($this->attributes['created_at'])->format('d-m-Y');
    }

    public function getCreatedAtTimeAttribute()
    {
        return Carbon::parse($this->attributes['created_at'])->format('h:iA');
    }

    public function getExpiredAtDateAttribute()
    {
        return Carbon::parse($this->attributes['expired_at'])->format('d-m-Y');
    }

    public function getMonthLeftAttribute()
    {
        $expiredAt = Carbon::parse($this->attributes['expired_at']);
        $today = Carbon::today();

        if ($expiredAt->isPast()) {
            return 0;
        }

        return ceil($today->floatDiffInMonths($expiredAt));
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function referrer()
    {
        return $this->belongsTo(User::class, 'referrer_user_id');
    }


    public function games()
    {
        return $this->hasMany(Game::class, 'user_id', 'user_id');
    }


    //## Auto Notification
    public function generateNotificationMessage($event)
    {
        $authUser = auth()->user()->name ?? 'System';
        $referrer = $this->referrer->name;
        $user = $this->user->name;

        switch ($event) {
            case 'created':
                return "$referrer invited $user.";
            default:
                return "An unknown event occurred.";
        }
    }

    public function notificationBlast($message)
    {
        $notificationType = preg_replace('/(?<!^)([A-Z])/', ' $1', class_basename($this));
        $auditId = Audit::getLatestAuditId();
        $whoCanSee = Notification::whoCanSee(\App\Library\PermissionTag::NOTIFICATION_MERCHANT_MODULE);
        foreach ($whoCanSee as $user) {
            Notification::create([
                'admin_id' => $user->id,
                'message' => $message,
                'notification_type' => $notificationType,
                'audit_id' => $auditId,
            ]);
        }
    }

    protected static function boot()
    {
        parent::boot();

        static::created(function ($item) {
            $message = $item->generateNotificationMessage('created');
            $item->notificationBlast($message);
        });

        // static::updated(function ($item) {
        //     $message = $item->generateNotificationMessage('updated');
        //     $item->notificationBlast($message);
        // });

        // static::deleted(function ($item) {
        //     $message = $item->generateNotificationMessage('deleted');
        //     $item->notificationBlast($message);
        // });
    }
}
