<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Pylon\Models\Traits\HasModelKit;
use Pylon\Models\Traits\HasSoftDeletesActivity;
use Pylon\Models\Traits\HasSortColumn;


class EntityStatus extends Model
{
    use HasFactory;
    use HasModelKit;
    use HasSoftDeletesActivity;
    use HasSortColumn;
    use SoftDeletes;

    protected $fillable = [
        'modelable_type',
        'modelable_id',
        'status',
        'created_by_admin_id',
        'deleted_by_admin_id',
        'remarks',
    ];

    const STATUS_DISABLED = 'Disabled';

    const STATUS_SUSPENDED = 'Suspended';

    const STATUS = [
        self::STATUS_DISABLED,
        self::STATUS_SUSPENDED,
    ];

    //## Relationships
    public function modelable()
    {
        return $this->morphTo();
    }

    public function createdByAdmin()
    {
        return $this->belongsTo(User::class, 'create_by_user_id');
    }

    public function deletedByAdmin()
    {
        return $this->belongsTo(User::class, 'delete_by_user_id');
    }
}
