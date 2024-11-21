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


class ApprovalRequest extends Model implements AuditableContract
{
    use HasFactory;
    use HasFactory;
    use HasModelableFile;
    use HasModelKit;
    use HasSoftDeletesActivity;
    use HasSortColumn;
    use SoftDeletes;
    use Auditable;

    const STATUS_PENDING = 'Pending';

    const STATUS_APPROVED = 'Approved';

    const STATUS_REJECTED = 'Rejected';

    const STATUS = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ];

    protected $fillable = [
        'approval_activity_id',
        'request_by_admin_id',
        'data',
        'status',
        'remark',
    ];

    // #Relationship
    public function logs()
    {
        return $this->hasMany(ApprovalLog::class);
    }

    public function activity()
    {
        return $this->belongsTo(ApprovalActivity::class, 'approval_activity_id');
    }

    public function requestBy()
    {
        return $this->belongsTo(Admin::class, 'request_by_admin_id');
    }



    // #Method
    public static function getPendingApprovalRequests()
    {
        return self::where('status', ApprovalRequest::STATUS_PENDING)
            ->with('logs')
            ->get();
    }

    //## Auto Notification
    public function generateNotificationMessage($event)
    {
        $authUser = auth()->user()->name ?? 'System';

        switch ($event) {
            case 'created':
                return "$authUser request for approval on $this->data['message']";
            case 'updated':
                return "Request for approval on $this->data['message'] has been " . strtolower($this->status);
            default:
                return "An unknown event occurred {$this->activity->name}.";
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
