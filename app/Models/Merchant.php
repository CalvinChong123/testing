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

class Merchant extends Model implements AuditableContract
{

    use HasModelableFile;
    use HasModelKit;
    use HasSoftDeletesActivity;
    use HasSortColumn;
    use SoftDeletes;
    use HasFactory;
    use Auditable;

    protected $fillable = [
        'name',
        'merchant_group_id',
        'cid0',
        'cid1',
        'cid2',
        'ip_address',
        'status',
        'last_status_update',
        'asset_no',
    ];

    protected $appends = ['created_at_date_time'];

    protected $hidden = [];

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
    //## Mutators

    //## Relationships
    public function merchantGroup()
    {
        return $this->belongsTo(MerchantGroup::class);
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function image(): MorphOne
    {
        return $this->morphOne(ModelableFile::class, 'modelable')->withDefault(ModelableFile::defaultImage());
    }

    public function entityStatus()
    {
        return $this->morphMany(EntityStatus::class, 'modelable');
    }

    public function currentMerchantUser()
    {
        $latestWithdrawTransaction = $this->transactions()
            ->where('type', Transaction::TYPE_WITHDRAWAL)
            ->latest()
            ->first();

        if ($latestWithdrawTransaction) {
            $currentUserTransactions = $this->transactions()
                ->with('user')
                ->where('created_at', '>', $latestWithdrawTransaction->created_at)
                ->latest()
                ->get();
        } else {
            $currentUserTransactions = $this->transactions()
                ->with('user')
                ->latest()
                ->get();
        }

        if ($currentUserTransactions->isNotEmpty()) {
            $totalCreditTopup = $currentUserTransactions
                ->where('type', Transaction::TYPE_TOPUP)
                ->sum('credit');

            $totalPromotionCreditTopup = $currentUserTransactions
                ->where('type', Transaction::TYPE_TOPUP)
                ->sum('[promotion_credit');

            $user = $currentUserTransactions->first()->user;

            return [
                'total_credit_topup' => $totalCreditTopup,
                'total_promotion_credit_topup' => $totalPromotionCreditTopup,
                'user' => $user,
            ];
        } else {
            return null;
        }
    }
    //## Extend relationships

    //## Methods

    //## Static Methods

    //## Auto Notification
    public function generateNotificationMessage($event)
    {
        $authUser = auth()->user()->name ?? 'System';
        $name = $this->name;

        switch ($event) {
            case 'created':
                return "$authUser created $name.";
            case 'updated':
                return "$authUser updated $name.";
            case 'deleted':
                return "$authUser deleted  $name.";
            default:
                return "An unknown event occurred for  $name.";
        }
    }

    public function notificationBlast($message)
    {
        $notificationType = preg_replace('/(?<!^)([A-Z])/', ' $1', class_basename($this));
        $whoCanSee = Notification::whoCanSee(\App\Library\PermissionTag::NOTIFICATION_MERCHANT_MODULE);
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