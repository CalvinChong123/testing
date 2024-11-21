<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = ['admin_id', 'notification_type', 'message', 'is_read', 'audit_id'];

    protected $hidden = [];

    protected $appends = ['created_at_date_time'];

    protected $casts = [
        'is_read' => 'boolean',
    ];

    //## Accessors
    public function getCreatedAtDateTimeAttribute()
    {
        return $this->created_at->format('d-n-Y h:iA');
    }

    //## Relationships
    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    public function audit()
    {
        return $this->belongsTo(Audit::class);
    }


    //## Extend relationships

    //## Methods
    public function markAsRead()
    {
        $this->is_read = true;
        $this->save();
    }



    //## Static Methods
    static public function whoCanSee($permissionName)
    {
        $permission = SptPermission::where('name', $permissionName)->first();

        if (!$permission) {
            return collect();
        } else {
        }

        $rolesWithPermission = sptRole::whereHas('permissions', function ($query) use ($permission) {
            $query->where('id', $permission->id);
        })->get();

        $usersWhoCanSee = Admin::whereHas('roles', function ($query) use ($rolesWithPermission) {
            $query->whereIn('id', $rolesWithPermission->pluck('id'));
        })->get();

        return $usersWhoCanSee;
    }

    static function filterByOptions()
    {
        $options =
            self::distinct()
            ->where('notification_type', '!=', 'System')
            ->pluck('notification_type')
            ->toArray();

        array_unshift($options, 'All');
        return $options;
    }
}
