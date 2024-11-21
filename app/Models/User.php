<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Http\Services\UserService;


class User extends Model
{
    use HasFactory;

    // protected $connection = 'hq_mysql';

    // protected $table = 'members';

    // protected $primaryKey = 'id';


    protected $fillable = [
        'id',
        'outlet_id',
        'first_name',
        'last_name',
        'email',
        'ic',
        'phone_no',
        'dob',
        'member_no',
        'member_category',
        'member_tier',
        'outlet',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $hidden = [];

    protected $casts = [];

    protected $appends = ['referral_count', 'status', 'status_log', 'name'];


    //## Accessors

    public function getNameAttribute()
    {
        return "{$this->last_name} {$this->first_name}";
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

    public function getReferralCountAttribute()
    {
        return $this->referrals()->count();
    }

    //## Relationships

    public function entityStatus()
    {
        return $this->morphMany(EntityStatus::class, 'modelable');
    }

    public function avatar()
    {
        return $this->morphOne(ModelableFile::class, 'modelable')->withDefault(ModelableFile::defaultImage('v1.png', 'default/avatar'));
    }

    public function referrer()
    {
        return $this->hasOne(Referral::class, 'user_id')->with('referrer');
    }

    public function referrals()
    {
        return $this->hasMany(Referral::class, 'referrer_user_id')->with('user');
    }

    public function userPointBalance()
    {
        return $this->hasMany(UserPointBalance::class)->latest();
    }

    public function userPromotionCreditBalance()
    {
        return $this->hasMany(UserPromotionCreditBalance::class)->latest();
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function currentMerchant()
    {
        $latestWithdrawTransaction = $this->transactions()
            ->where('type', Transaction::TYPE_WITHDRAWAL)
            ->latest()
            ->first();

        if ($latestWithdrawTransaction) {
            $currentMerchantTransactions = $this->transactions()
                ->where('created_at', '>', $latestWithdrawTransaction->created_at)
                ->with('merchant')
                ->latest()
                ->get();
        } else {
            $currentMerchantTransactions = $this->transactions()
                ->with('merchant')
                ->latest()
                ->get();
        }

        if ($currentMerchantTransactions->isNotEmpty()) {
            $totalCreditTopup = $currentMerchantTransactions
                ->where('type', Transaction::TYPE_TOPUP)
                ->sum('credit');

            $totalPromotionCreditTopup = $currentMerchantTransactions
                ->where('type', Transaction::TYPE_TOPUP)
                ->sum('promotion_credit');

            $merchant = $currentMerchantTransactions->first()->merchant;
            return [
                'total_credit_topup' => $totalCreditTopup,
                'total_promotion_credit_topup' => $totalPromotionCreditTopup,
                'merchant' => $merchant,
            ];
        } else {
            return null;
        }
    }

    //## Methods

    private static function extractDobFromIc($ic)
    {
        $year = substr($ic, 0, 2);
        $month = substr($ic, 2, 2);
        $day = substr($ic, 4, 2);

        $currentYear = date('Y') % 100;
        $century = $year > $currentYear ? '19' : '20';

        return "{$century}{$year}-{$month}-{$day}";
    }

    public static function isValidIc($ic)
    {
        $dob = self::extractDobFromIc($ic);
        $date = \DateTime::createFromFormat('Y-m-d', $dob);
        return $date && $date->format('Y-m-d') === $dob;
    }

    public function assignReferrer(int $referrerUserId): void
    {
        Referral::create([
            'user_id' => $this->id,
            'referrer_user_id' => $referrerUserId,
            'expired_at' => Referral::calculateExpiryDate(now()),
        ]);
    }

    public function getUserPointBalanceTransactions()
    {
        return $this->hasMany(UserPointBalance::class)->orderBy('created_at', 'desc');
    }

    public function getUserPromotionCreditBalanceTransactions()
    {
        return $this->hasMany(UserPromotionCreditBalance::class)->orderBy('created_at', 'desc');
    }

    public function getTotalPointBalance()
    {
        $latestBalance = $this->hasMany(UserPointBalance::class)->latest()->first();

        if ($latestBalance) {
            return ($latestBalance->point_balance_after_activity ?? 0) + ($latestBalance->referral_point_balance_after_activity ?? 0);
        }
        return 0;
    }

    public function getTotalPromotionCreditBalance()
    {
        $latestBalance = $this->hasMany(UserPromotionCreditBalance::class)->latest()->first();

        if ($latestBalance) {
            return $latestBalance->promotion_credit_balance_after_activity ?? 0;
        }

        return 0;
    }

    //## Static Methods

    // Notifiable

    // Auditable 
    public function updateAuditWithRole(string $role): void
    {
        $audit = Audit::where('auditable_type', User::class)
            ->where('auditable_id', $this->id)
            ->where('event', 'created')
            ->latest()
            ->first();

        if ($audit) {
            $newValues = $audit->new_values;
            $newValues['role'] = $role;

            $audit->update([
                'module' => $this->hasRole('User') ? 'User' : 'Admin',
                'new_values' => $newValues,
            ]);
        }
    }

    public function onlyRoleChangingAuditRecord(string $event, User $user, array $oldValues = [], array $newValues = []): void
    {
        Audit::create([
            'user_type' => get_class($this),
            'user_id' => Auth::id(),
            'user_name' => Auth::user()->name,
            'event' => $event,
            'auditable_type' => User::class,
            'auditable_id' => $this->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'module' => 'Admin',
        ]);

        $message = $user->generateNotificationMessage('updated');
        // $user->notificationBlast($message);
    }

    //## Auto Notification
    public function generateNotificationMessage($event)
    {
        $authUser = auth()->user()->name ?? 'System';
        $user = $this->name;
        $role = $this->hasRole('User') ? 'User' : 'Admin';

        switch ($event) {
            case 'created':
                return "$authUser created $role $user.";
            case 'updated':
                return "$authUser updated $role $user.";
            case 'deleted':
                return "$authUser deleted $role $user.";
            default:
                return "An unknown event occurred for $role $user.";
        }
    }

    public function notificationBlast($message)
    {
        $notificationType = preg_replace('/(?<!^)([A-Z])/', ' $1', class_basename($this));
        $module = $this->hasRole('User') ? 'User' : 'Admin';
        $whoCanSee = Notification::whoCanSee(\App\Library\PermissionTag::NOTIFICATION_USER_MODULE);
        $auditId = Audit::getLatestAuditId();
        foreach ($whoCanSee as $user) {
            Notification::create([
                'admin_id' => $user->id,
                'message' => $message,
                'notification_type' => $module,
                'audit_id' => $auditId,
            ]);
        }
    }

    // protected static function boot()
    // {
    //     parent::boot();

    //     // create no need auto notification cuz will miss role assigned, do it at user service
    //     static::created(function ($item) {
    //         // $message = $item->generateNotificationMessage('created');
    //         // $item->notificationBlast($message);
    //     });

    //     static::updated(function ($item) {
    //         $message = $item->generateNotificationMessage('updated');
    //         $item->notificationBlast($message);
    //     });

    //     static::deleted(function ($item) {
    //         $message = $item->generateNotificationMessage('deleted');
    //         $item->notificationBlast($message);
    //     });
    // }
}
