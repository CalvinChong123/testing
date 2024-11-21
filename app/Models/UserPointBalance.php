<?php

namespace App\Models;

use App\Contracts\Approvable;
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
use App\Http\Services\UserService;
use App\Exceptions\BadRequestException;
use App\Http\Controllers\Controller;

class UserPointBalance extends Model implements AuditableContract, Approvable
{
    use HasFactory;
    use HasModelableFile;
    use HasModelKit;
    use HasSoftDeletesActivity;
    use HasSortColumn;
    use SoftDeletes;
    use Auditable;

    const TYPE_SELF_EARN = 'Self Earn';

    const TYPE_REDEEM = 'Redeem';

    const TYPE_ADD = 'Add';

    const TYPE_DEDUCT = 'Deduct';

    const TYPE_REFERRAL = 'Referral';

    // const TYPE_EXPIRED = 'Expired';


    const TYPE = [
        self::TYPE_SELF_EARN,
        self::TYPE_REDEEM,
        self::TYPE_ADD,
        self::TYPE_DEDUCT,
        self::TYPE_REFERRAL,
        // self::TYPE_EXPIRED

    ];

    protected $fillable = [
        'user_id',
        'type',
        'game_id',
        'referral_id',
        'credit_earned',
        'remark',
        'point_amount',
        'referral_point_amount',
        'point_balance_before_activity',
        'point_balance_after_activity',
        // 'referral_point_balance_before_activity',
        // 'referral_point_balance_after_activity',
        'total_point_balance_this_year',
    ];

    protected $appends = ['created_at_date_time', 'activity'];

    //## Accessors
    public function getCreatedAtDateTimeAttribute()
    {
        return $this->created_at->format('d-n-Y h:iA');
    }
    public function getActivityAttribute()
    {
        switch ($this->type) {
            case self::TYPE_SELF_EARN:
                return "Earn {$this->point_amount} points from {$this->game->merchant->name}";
            case self::TYPE_REFERRAL:
                return "Earn {$this->point_amount} points from {$this->referral->user->name}";
            case self::TYPE_ADD:
            case self::TYPE_DEDUCT:
                return "{$this->type} {$this->point_amount} points";
            case self::TYPE_REDEEM:
                return "Redeem {$this->credit_earned} credit using {$this->point_amount} points";
            default:
                return "Unknown activity";
        }
    }
    // public static function getExpiringReferralPoints($userId)
    // {
    //     return self::where('user_id', $userId)
    //         ->where('created_at', '<=', Carbon::now()->addDays(30))
    //         ->where('referral_point_amount', '>', 0)
    //         ->get();
    // }


    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function game()
    {
        return $this->belongsTo(Game::class);
    }
    public function referral()
    {
        return $this->belongsTo(Referral::class);
    }


    # Method
    // public static function handleExpiredReferralPoints($userId)
    // {
    //     $currentDate = Carbon::now();

    //     // figure out referral points that are expired
    //     $referralPointBalances = self::where('user_id', $userId)
    //         ->where('type', 'earn')
    //         ->where('referral_point_amount', '>', 0)
    //         ->orderBy('created_at', 'asc')
    //         ->get();

    //     $lastBalance = self::where('user_id', $userId)
    //         ->latest()
    //         ->first();

    //     $config = Config::where('key', 'referral_point_expired_days')->first();

    //     foreach ($referralPointBalances as $balance) {
    //         if ($currentDate->diffInDays($balance->created_at) > $config) {
    //             if ($balance->referral_point_amount > 0) {
    //                 self::create([
    //                     'user_id' => $userId,
    //                     'type' => 'expired',
    //                     'game_id' => null,
    //                     'remark' => 'Referral point expired',
    //                     'referral_point_amount' => -$balance->referral_point_amount,
    //                     'referral_point_balance_before_activity' => $balance->referral_point_balance_after_activity,
    //                     'referral_point_balance_after_activity' => $balance->referral_point_balance_after_activity - $balance->referral_point_amount,
    //                     'point_amount' => $lastBalance->point_amount,
    //                     'point_balance_before_activity' => $lastBalance->point_balance_after_activity,
    //                     'point_balance_after_activity' => $lastBalance->point_balance_after_activity,
    //                     'total_point_balance_this_year' => $lastBalance->total_point_balance_this_year,
    //                     'created_at' => $currentDate,
    //                     'updated_at' => $currentDate,
    //                 ]);

    //                 $balance->referral_point_amount = 0;
    //                 $balance->save();
    //             }
    //         }
    //     }
    // }

    // # Approvable
    public function applyApproval(array $data): void
    {
        $userPointBalance = self::where('user_id', $data['user_id'])
            ->latest()
            ->first();

        if (($data['type'] == self::TYPE_DEDUCT || $data['type'] == self::TYPE_REDEEM) && (!isset($userPointBalance) || ($userPointBalance->point_balance_after_activity < $data['point']))) {
            $errors['point'] = ['User does not have enough point balance'];
        }

        if (!empty($errors)) {
            Controller::customValidationException($errors);
        } else {
            $result = UserService::updateUserPointBalance($userPointBalance, $data);
        }
    }

    public function rejectApproval(): void {}

    public function getApprovalData(): array
    {
        return [
            'user_id' => $this->user_id,
            'point_amount' => $this->point_amount,
            'credit_earned' => $this->credit_earned,
            'type' => $this->type,
            'message' => $this->generateNotificationMessage('created'),
        ];
    }


    //## Auto Notification
    public function generateNotificationMessage($event)
    {
        $authUser = auth()->user()->name ?? 'System';
        $name = $this->user->name;

        switch ($event) {
            case 'created':
                if ($this->type == 'Add' || $this->type == 'Deduct' || $this->type == 'Redeem') {
                    return "$authUser " . strtolower($this->type) . " user $name $this->point_amount points.";
                }
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
            if ($item->type == 'Add' || $item->type == 'Deduct' || $item->type == 'Redeem') {
                $message = $item->generateNotificationMessage('created');
                $item->notificationBlast($message);
            }
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
