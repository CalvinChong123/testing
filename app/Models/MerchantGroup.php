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
use App\Library\PermissionTag;

class MerchantGroup extends Model implements AuditableContract
{
    use HasFactory;
    use HasModelableFile;
    use HasModelKit;
    use HasSoftDeletesActivity;
    use HasSortColumn;
    use SoftDeletes;
    use Auditable;

    protected $fillable = ['name', 'description', 'spending_credits', 'earning_points'];

    protected $hidden = [];

    protected $appends = ['created_at_date_time', 'merchant_count'];

    protected $casts = [];

    //## Accessors
    public function getCreatedAtDateTimeAttribute()
    {
        return $this->created_at->format('d-n-Y h:iA');
    }


    public function getStatusAttribute()
    {
        $entityStatus = $this->entityStatus()->latest()->first();

        return $entityStatus ? $entityStatus->status : null;
    }

    public function getStatusLogAttribute()
    {
        return $this->entityStatus()->with(['createdByAdmin', 'deletedByAdmin'])->get()->map(function ($status) {
            return [
                'id' => $status->id,
                'status' => $status->status,
                'remarks' => $status->remarks,
                'created_by' => $status->createdByAdmin ? $status->createdByAdmin->name : null,
                'deleted_by' => $status->deletedByAdmin ? $status->deletedByAdmin->name : null,
                'created_at' => $status->created_at->format('D, F j, Y h:iA'),
            ];
        });
    }

    public function entityStatus()
    {
        return $this->morphMany(EntityStatus::class, 'modelable');
    }

    public function getMerchantCountAttribute()
    {
        return $this->merchants()->count();
    }
    //## Mutators

    //## Relationships
    public function merchants()
    {
        return $this->hasMany(Merchant::class);
    }
    //## Extend relationships

    //## Methods

    //## Static Methods

    //## Auto Notification
    public function generateNotificationMessage($event)
    {
        $authUser = auth()->user()->name ?? 'System';
        $merchantGroup = $this->name;

        switch ($event) {
            case 'created':
                return "$authUser created $merchantGroup.";
            case 'updated':
                return "$authUser updated $merchantGroup.";
            case 'deleted':
                return "$authUser deleted  $merchantGroup.";
            default:
                return "An unknown event occurred for  $merchantGroup.";
        }
    }

    public function notificationBlast($message)
    {
        $auditId = Audit::getLatestAuditId();
        $notificationType = preg_replace('/(?<!^)([A-Z])/', ' $1', class_basename($this));
        $whoCanSee = Notification::whoCanSee(PermissionTag::NOTIFICATION_MERCHANT_MODULE);
        foreach ($whoCanSee as $user) {
            Notification::create([
                'admin_id' => $user->id,
                'message' => $message,
                'notification_type' => 'Merchant',
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
