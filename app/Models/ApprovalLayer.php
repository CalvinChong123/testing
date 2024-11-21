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


class ApprovalLayer extends Model implements AuditableContract
{
    use HasFactory;
    use HasFactory;
    use HasModelableFile;
    use HasModelKit;
    use HasSoftDeletesActivity;
    use HasSortColumn;
    use SoftDeletes;
    use Auditable;

    protected $fillable = ['approval_activity_id', 'layer', 'role_id'];

    public function approvalActivity()
    {
        return $this->belongsTo(ApprovalActivity::class);
    }

    public function role()
    {
        return $this->belongsTo(sptRole::class, 'role_id');
    }


    // ## Auto Notification
    public function generateNotificationMessage($event)
    {
        $authUser = auth()->user()->name ?? 'System';
        $name = $this->name;
        $role = $this->role->name;
        $approvalActivity = $this->approvalActivity->name;


        switch ($event) {
            case 'created':
                return "$authUser assign $approvalActivity's layer $this->layer to $role.";
            default:
                return "An unknown event occurred for $name.";
        }
    }

    public function NotificationBlast($message)
    {
        $notificationType = 'Approval Activity';
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
