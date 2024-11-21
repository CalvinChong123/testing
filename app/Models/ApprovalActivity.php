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


class ApprovalActivity extends Model implements AuditableContract
{
    use HasFactory;
    use HasFactory;
    use HasModelableFile;
    use HasModelKit;
    use HasSoftDeletesActivity;
    use HasSortColumn;
    use SoftDeletes;
    use Auditable;

    const NAME_CONFIG_SETTING = 'Config Setting';
    const NAME_ADD_DEDUCT_POINTS = 'Add/ Deduct Points';
    const NAME_REDEEM_POINTS = 'Redeem Points';
    const NAME_MERCHANT_CONTROL = 'Merchant Control';

    const NAME = [
        self::NAME_CONFIG_SETTING,
        self::NAME_ADD_DEDUCT_POINTS,
        self::NAME_REDEEM_POINTS,
        self::NAME_MERCHANT_CONTROL,
    ];

    protected $fillable = ['name', 'description', 'modelable_type'];


    public function layers()
    {
        return $this->hasMany(ApprovalLayer::class);
    }



    // ## Auto Notification
    public function generateNotificationMessage($event)
    {
        $authUser = auth()->user()->name ?? 'System';
        $name = $this->name;


        switch ($event) {
            case 'updated':
                return "$authUser updated $name.";
            default:
                return "An unknown event occurred for $name.";
        }
    }

    public function NotificationBlast($message)
    {
        $notificationType = preg_replace('/(?<!^)([A-Z])/', ' $1', class_basename($this));
        $whoCanSee = Notification::whoCanSee(\App\Library\PermissionTag::NOTIFICATION_TRANSACTION_MODULE);
        $auditId = Audit::getLatestAuditId();
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

        // static::created(function ($item) {
        //     $message = $item->generateNotificationMessage('created');
        //     $item->notificationBlast($message);
        // });

        static::updated(function ($item) {
            $message = $item->generateNotificationMessage('updated');
            $item->notificationBlast($message);
        });

        // static::deleted(function ($item) {
        //     $message = $item->generateNotificationMessage('deleted');
        //     $item->notificationBlast($message);
        // });
    }
}
