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

class CashFloat extends Model implements AuditableContract
{
    use HasFactory;
    use HasModelableFile;
    use HasModelKit;
    use HasSoftDeletesActivity;
    use HasSortColumn;
    use SoftDeletes;
    use Auditable;

    protected $fillable = [
        'shift_no',
        'cash_in',
        'cash_out',
        'cash_in_admin_id',
        'cash_out_admin_id',
        'remark',
        'start_time',
        'end_time',
        'end_shift_today',
    ];

    protected $appends = [
        'created_at_date_time',
        'total_cash_replenishment',
    ];


    public function getCreatedAtDateTimeAttribute()
    {
        return $this->created_at->format('d-n-Y h:iA');
    }

    public function getTotalCashReplenishmentAttribute()
    {
        return $this->cashReplenishments()->sum('amount');
    }

    public function cashInAdmin()
    {
        return $this->belongsTo(User::class, 'cash_in_admin_id');
    }

    public function cashOutAdmin()
    {
        return $this->belongsTo(User::class, 'cash_out_admin_id');
    }

    public function cashReplenishments()
    {
        return $this->hasMany(CashReplenishment::class);
    }

    //## Methods
    public static function latestShiftStartEndTime()
    {
        $lastTwoEndShifts = self::where('end_shift_today', true)
            ->orderBy('created_at', 'desc')
            ->take(2)
            ->get();

        if ($lastTwoEndShifts->count() == 2) {
            $startFrom = $lastTwoEndShifts->last()->end_time;
            $endAt = $lastTwoEndShifts->first()->start_time;


            $latestShift = self::whereBetween('created_at', [$startFrom, $endAt])
                ->orderBy('created_at', 'asc')
                ->get();

            $startTime = $latestShift->first()->start_time;
            $endTime = $latestShift->last()->end_time;

            return [
                'start_time' => $startTime,
                'end_time' => $endTime,
            ];
        }

        $latestShift = self::orderBy('created_at', 'asc')->get();
        $startTime = $latestShift->first()->start_time;
        $endTime = $latestShift->last()->end_time;
        return [
            'start_time' => $startTime,
            'end_time' => $endTime,
        ];
    }

    //## Auto Notification
    public function generateNotificationMessage($event)
    {
        $authUser = auth()->user()->name ?? 'System';
        $cashInAdminName = $this->cashInAdmin ? $this->cashInAdmin->name : 'Unknown';
        $cashOutAdminName = $this->cashOutAdmin ? $this->cashOutAdmin->name : 'Unknown';

        switch ($event) {
            case 'created':
                return "$authUser start the shift $this->shift_no with opening amount RM$this->cash_in.";
            case 'updated':
                return "$authUser end the shift $this->shift_no with closing amount RM$this->cash_out.";
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
