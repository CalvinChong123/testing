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
use App\Http\Services\GeneralService;
use App\Contracts\Approvable;

class ApprovalLog extends Model implements AuditableContract
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

    const STATUS_QUEUE = 'Queue';

    const STATUS = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_QUEUE,
    ];

    protected $fillable = ['approval_request_id', 'role_id', 'layer_no', 'status', 'admin_id'];

    protected $appends = ['created_at_date_time'];

    // #Attribute
    public function getCreatedAtDateTimeAttribute()
    {
        return $this->created_at->format('d-n-Y h:iA');
    }

    // #Relationship
    public function approvalRequest()
    {
        return $this->belongsTo(ApprovalRequest::class);
    }

    public function role()
    {
        return $this->belongsTo(sptRole::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }


    // #Method
    public function processApproval($isApproved)
    {
        if ($isApproved == ApprovalLog::STATUS_APPROVED) {
            $this->status = $isApproved;

            $nextLayer = ApprovalLog::where('approval_request_id', $this->approval_request_id)
                ->where('layer_no', $this->layer_no + 1)
                ->first();

            if ($nextLayer) {
                $nextLayer->status = ApprovalLog::STATUS_PENDING;
                $nextLayer->save();
            } else {
                $this->approvalRequest->status = ApprovalRequest::STATUS_APPROVED;
                $this->approvalRequest->save();

                $payload = json_decode($this->approvalRequest->data, true);

                $model = $this->approvalRequest->activity->modelable_type;
                $model = new $model;

                if ($model instanceof Approvable) {
                    $model->applyApproval($payload);
                }
            }
        } else {
            self::where('approval_request_id', $this->approval_request_id)
                ->where('layer_no', '>=', $this->layer_no)
                ->update(['status' => $isApproved]);

            $this->approvalRequest->status = ApprovalRequest::STATUS_REJECTED;
            $this->approvalRequest->save();
        }

        $this->save();
    }

    //## Auto Notification
    public function generateNotificationMessage($event)
    {
        $authUser = auth()->user()->name ?? 'System';
        $data = json_decode($this->approvalRequest->data, true);

        switch ($event) {
            case 'updated':
                return "$authUser " . strtolower($this->status) . " layer $this->layer_no request for approval on {$data['message']}";
            default:
                return "An unknown event occurred {$data['message']} layer $this->layer_no.";
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

        // static::created(function ($item) {
        //     $message = $item->generateNotificationMessage('created');
        //     $item->notificationBlast($message);
        // });

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
