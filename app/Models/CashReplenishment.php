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


class CashReplenishment extends Model implements AuditableContract
{
    use HasFactory;
    use HasModelableFile;
    use HasModelKit;
    use HasSoftDeletesActivity;
    use HasSortColumn;
    use SoftDeletes;
    use Auditable;

    protected $fillable = [
        'admin_id',
        'cash_float_id',
        'amount',
        'remark',
    ];

    protected $appends = [
        'created_at_date_time',
    ];

    public function getCreatedAtDateTimeAttribute()
    {
        return $this->created_at->format('d-n-Y h:iA');
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function cashFloat()
    {
        return $this->belongsTo(CashFloat::class);
    }


    //## Auto Notification
    public function generateNotificationMessage($event)
    {
        $authUser = auth()->user()->name ?? 'System';
        $shiftNo = $this->cashFloat ? $this->cashFloat->shift_no : 'Unknown';

        switch ($event) {
            case 'created':
                return "$authUser replenish cash RM$this->amount for shift $shiftNo.";

            default:
                return "An unknown event occurred $this->shift_no.";
        }
    }

    public function notificationBlast($message)
    {
        $notificationType = preg_replace('/(?<!^)([A-Z])/', ' $1', class_basename($this));
        $auditId = Audit::getLatestAuditId();
        $whoCanSee = Notification::whoCanSee(\App\Library\PermissionTag::NOTIFICATION_CASH_FLOAT_MODULE);
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

        static::updated(function ($item) {
            $message = $item->generateNotificationMessage('updated');
            $item->notificationBlast($message);
        });

        static::deleted(function ($item) {
            $message = $item->generateNotificationMessage('deleted');
            $item->notificationBlast($message);
        });
    }
}
