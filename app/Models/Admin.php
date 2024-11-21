<?php

namespace App\Models;

use App\Library\PermissionTag;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Pylon\Models\Traits\HasModelableFile;
use Pylon\Models\Traits\HasModelKit;
use Pylon\Models\Traits\HasSoftDeletesActivity;
use Spatie\Permission\Traits\HasRoles;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Illuminate\Support\Facades\Log;
use App\Observers\SyncLogObserver;

class Admin extends Authenticatable implements AuditableContract
{
    use HasApiTokens;
    use HasFactory;
    use HasModelableFile;
    use HasModelKit;
    use HasRoles;
    use HasSoftDeletesActivity;
    use Notifiable;
    use SoftDeletes;
    use Auditable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'first_time_login',
        'remember_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = ['created_at_date', 'created_at_time', 'status', 'status_log'];

    //## Accessors
    public function getCreatedAtDateAttribute()
    {
        return Carbon::parse($this->attributes['created_at'])->format('D, F j, Y');
    }

    public function getCreatedAtTimeAttribute()
    {
        return Carbon::parse($this->attributes['created_at'])->format('h:iA');
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

    //## Relationships
    public function entityStatus()
    {
        return $this->morphMany(EntityStatus::class, 'modelable');
    }

    public function avatar()
    {
        return $this->morphOne(ModelableFile::class, 'modelable')->withDefault(ModelableFile::defaultImage('v1.png', 'default/avatar'));
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }


    //## Methods
    public function getHighestClassificationLevel()
    {
        return PermissionTag::getModelHighestClassificationLevel($this);
    }


    //## Static Methods

    // Notifiable
    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }




    // Auditable 
    public function updateAuditWithRole(string $role): void
    {
        $audit = Audit::where('auditable_type', Admin::class)
            ->where('auditable_id', $this->id)
            ->where('event', 'created')
            ->latest()
            ->first();

        if ($audit) {
            $newValues = $audit->new_values;
            $newValues['role'] = $role;

            $audit->update([
                'module' => 'Admin',
                'new_values' => $newValues,
            ]);
        }
    }

    public function onlyRoleChangingAuditRecord(string $event, Admin $user, array $oldValues = [], array $newValues = []): void
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
        $user->notificationBlast($message);
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
                'notification_type' => $notificationType,
                'audit_id' => $auditId,
            ]);
        }
    }

    protected static function boot()
    {
        parent::boot();

        // create no need auto notification cuz will miss role assigned, do it at user service
        static::created(function ($item) {
            // $message = $item->generateNotificationMessage('created');
            // $item->notificationBlast($message);
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
