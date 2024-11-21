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
use App\Contracts\Approvable;

class Config extends Model implements AuditableContract, Approvable
{
    use HasFactory;
    use HasModelableFile;
    use HasModelKit;
    use HasSoftDeletesActivity;
    use HasSortColumn;
    use Auditable;

    const NAME_OUTLET_NAME = 'Outlet Name';
    const NAME_REFERRAL_VALIDITY_PERIOD = 'Referral Validity Period (number of month)';
    const NAME_SPENDING_TO_REFERRAL_POINT_VALUE = 'Spending To Referral Point Value';
    const NAME_DATA_AUTO_PURGE_PERIOD = 'Data Auto Purge Period (number of days)';
    const NAME_POINT_TO_CREDIT_VALUE = 'Point To Credit Value';


    const NAME = [
        self::NAME_OUTLET_NAME,
        self::NAME_REFERRAL_VALIDITY_PERIOD,
        self::NAME_DATA_AUTO_PURGE_PERIOD,
        self::NAME_POINT_TO_CREDIT_VALUE,
        self::NAME_SPENDING_TO_REFERRAL_POINT_VALUE,
    ];
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'months',
        'days',
        'credits',
        'points',
        'outlet_name',
        'outlet_id',
    ];

    protected $appends = [
        'value',
        'updated_at_date_time'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */

    //## Accessors

    public function getUpdatedAtDateTimeAttribute()
    {
        return Carbon::parse($this->attributes['updated_at'])->format('j-n-Y h:iA');
    }

    public function getValueAttribute()
    {
        switch ($this->name) {
            case 'Outlet Name':
                return "{$this->outlet_name}";
            case self::NAME_REFERRAL_VALIDITY_PERIOD:
                return "{$this->months} Month";
            case self::NAME_DATA_AUTO_PURGE_PERIOD:
                return "{$this->days} Days";
            case self::NAME_POINT_TO_CREDIT_VALUE:
                return "{$this->points} Points = {$this->credits} Credits Earns";
            case self::NAME_SPENDING_TO_REFERRAL_POINT_VALUE:
                return "{$this->credits} Credits = {$this->points} Referral Points";
            default:
                return null;
        }
    }
    //## Static Methods


    // # Approvable
    public function applyApproval(array $data): void
    {
        $config = self::find($data['id']);
        if ($config) {
            $config->update([
                'outlet_name' => $data['outlet_name'] ?? null,
                'months' => $data['months'] ?? null,
                'days' => $data['days'] ?? null,
                'credits' => $data['credits'] ?? null,
                'points' => $data['points'] ?? null,
            ]);
        }
    }

    public function rejectApproval(): void {}

    public function getApprovalData(): array
    {
        return [
            'id' => $this->id,
            'outlet_name' => $this->outlet_name,
            'months' => $this->months,
            'days' => $this->days,
            'credits' => $this->credits,
            'points' => $this->points,
            'message' => $this->generateNotificationMessage('updated'),
        ];
    }





    // ## Auto Notification
    public function generateNotificationMessage($event)
    {
        $authUser = auth()->user()->name ?? 'System';
        $name = $this->name;

        $message = [];
        if (isset($this->attributes['outlet_name'])) {
            $message[] = "$authUser change $this->name's to {$this->attributes['name']}.";
        }
        if (isset($this->attributes['months'])) {
            $message[] = "$authUser change $this->name's months to {$this->attributes['months']}.";
        }
        if (isset($this->attributes['days'])) {
            $message[] = "$authUser change $this->name's days to {$this->attributes['days']}.";
        }
        if (isset($this->attributes['credits'])) {
            $message[] = "$authUser change $this->name's credits to {$this->attributes['credits']}.";
        }
        if (isset($this->attributes['points'])) {
            $message[] = "$authUser change $this->name's points to {$this->attributes['points']}.";
        }
        $message = implode(' ', $message);

        switch ($event) {
            case 'updated':
                return $message;
            default:
                return "An unknown event occurred for  $name.";
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
