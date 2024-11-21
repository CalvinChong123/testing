<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Pylon\Models\Traits\HasModelableFile;
use Pylon\Models\Traits\HasModelKit;
use Pylon\Models\Traits\HasSoftDeletesActivity;
use Pylon\Models\Traits\HasSortColumn;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class UserPromotionCreditBalance extends Model implements AuditableContract
{
    use HasFactory;
    use HasModelableFile;
    use HasModelKit;
    use HasSoftDeletesActivity;
    use HasSortColumn;
    use SoftDeletes;
    use Auditable;

    protected $fillable = [
        'user_id',
        'type',
        'modelable_type',
        'modelable_id',
        'remark',
        'promotion_credit_amount',
        'promotion_credit_balance_before_activity',
        'promotion_credit_balance_after_activity',
    ];

    const TYPE_EARN = 'Earn';

    const TYPE_TOPUP = 'Topup';




    const TYPE = [
        self::TYPE_EARN,
        self::TYPE_TOPUP,

    ];

    protected $appends = [
        'created_at_date_time',
        'type',
        'activity'
    ];

    // #Append
    public function getCreatedAtDateTimeAttribute()
    {
        return $this->created_at->format('d-n-Y h:iA');
    }

    public function getTypeAttribute()
    {
        return $this->modelable_type === Transaction::class ? self::TYPE_TOPUP : self::TYPE_EARN;
    }

    public function getActivityAttribute()
    {
        switch ($this->type) {
            case self::TYPE_EARN:
                return "Earned {$this->promotion_credit_amount} credits from promotion bonus";
            case self::TYPE_TOPUP:
                $merchantName = $this->modelable->merchant->name ?? null;
                return "Topup {$this->promotion_credit_amount} credits to merchant $merchantName";
            default:
                return null;
        }
    }



    // Relationships

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function modelable()
    {
        return $this->morphTo();
    }
    //## Auto Notification
    public function generateNotificationMessage($event)
    {
        $authUser = auth()->user()->name ?? 'System';
        $name = $this->user->name;

        switch ($event) {
            case 'created':
                return "$name " . strtolower($this->type) . " $this->promotion_credit_amount promotion credits.";
            default:
                return "An unknown event occurred for $name's point balance .";
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
