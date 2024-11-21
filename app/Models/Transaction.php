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
use Spatie\Permission\Contracts\Permission;
use App\Library\PermissionTag;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Transaction extends Model implements AuditableContract
{
    use HasFactory;
    use HasModelableFile;
    use HasModelKit;
    use HasSoftDeletesActivity;
    use HasSortColumn;
    use SoftDeletes;
    use Auditable;

    const TYPE_WITHDRAWAL = 'Withdrawal';

    const TYPE_TOPUP = 'Topup';

    const TYPE = [
        self::TYPE_WITHDRAWAL,
        self::TYPE_TOPUP,
    ];

    const PAYMENT_METHOD_E_WALLET = 'E-Wallet';

    const PAYMENT_METHOD_BANK_TRANSFER = 'Online Banking';

    const PAYMENT_METHOD_CREDIT_DEBIT_CARD = 'Credit/ Debit Card';

    const PAYMENT_METHOD_OFFLINE_PAYMENT = 'Offline Payment';

    const PAYMENT_METHOD_PROMOTION_CREDIT = 'Promotion Credit';


    const PAYMENT_METHODS = [
        self::PAYMENT_METHOD_E_WALLET,
        self::PAYMENT_METHOD_BANK_TRANSFER,
        self::PAYMENT_METHOD_CREDIT_DEBIT_CARD,
        self::PAYMENT_METHOD_OFFLINE_PAYMENT,
        self::PAYMENT_METHOD_PROMOTION_CREDIT,

    ];

    const WITHDRAWAL_METHOD_E_WALLET = 'E-Wallet';

    const WITHDRAWAL_METHOD_BANK_TRANSFER = 'Online Banking';

    const WITHDRAWAL_METHOD_CREDIT_DEBIT_CARD = 'Credit/ Debit Card';

    const WITHDRAWAL_METHOD_CASH_FLOAT = 'Cash Float';

    const WITHDRAWAL_METHOD_FROM_MERCHANT = 'From Merchant';



    const WITHDRAWAL_METHODS = [
        self::WITHDRAWAL_METHOD_E_WALLET,
        self::WITHDRAWAL_METHOD_BANK_TRANSFER,
        self::WITHDRAWAL_METHOD_CREDIT_DEBIT_CARD,
        self::WITHDRAWAL_METHOD_CASH_FLOAT,
        self::WITHDRAWAL_METHOD_FROM_MERCHANT,
    ];


    protected $fillable = [
        'user_id',
        'merchant_id',
        'type',
        'credit',
        'promotion_credit',
        'payment_method',
        'withdrawal_method',
        'admin_id',
        'merchant_current_bet_total',
        'merchant_current_win_total',
        'merchant_current_game_total',
        'merchant_current_balance_total',
    ];

    protected $appends = [
        'created_at_date_time',
    ];

    // Accessors
    public function getCreatedAtDateTimeAttribute()
    {
        return $this->created_at->format('d-n-Y h:iA');
    }


    // Relationships

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    // ## Methods

    // ## Auto Notification
    public function generateNotificationMessage($event)
    {
        $authUser = auth()->user()->name ?? 'System';
        $user = $this->user->name ?? 'User';
        $merchant = $this->merchant->name ?? 'Merchant';
        $message = '';

        switch ($this->type) {
            case self::TYPE_TOPUP:
                $message = "$user topped up $this->credit amount to $merchant.";
                if ($this->credit == 0 || $this->credit == null) {
                    $message =  "$user topped up $this->promotion_credit promotion credit to $merchant.";
                }
            case self::TYPE_WITHDRAWAL:
                $message =  "$user withdrew from $merchant.";
            default:
                $message =  "An unknown event occurred for $user.";
        }

        return $message;
    }

    public function NotificationBlast($message)
    {
        $notificationType = preg_replace('/(?<!^)([A-Z])/', ' $1', class_basename($this));
        $whoCanSee = Notification::whoCanSee(\App\Library\PermissionTag::NOTIFICATION_TRANSACTION_MODULE);
        foreach ($whoCanSee as $user) {
            Notification::create([
                'admin_id' => $user->id,
                'message' => $message,
                'notification_type' => $notificationType,
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
