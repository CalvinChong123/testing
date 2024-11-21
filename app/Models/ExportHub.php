<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Pylon\Models\Traits\HasModelableFile;
use Pylon\Models\Traits\HasModelKit;
use Pylon\Models\Traits\HasSoftDeletesActivity;

class ExportHub extends Model
{
    use HasFactory;
    use HasModelableFile;
    use HasModelKit;
    use HasSoftDeletesActivity;
    use SoftDeletes;

    protected $fillable = [
        'type_key',
        'status_key',
        'created_by',
    ];

    protected $hidden = [];

    protected $casts = [];

    protected $appends = [];

    // status
    public const STATUS_PENDING = 100;

    public const STATUS_SUCCESS = 101;

    public const STATUS_EXPIRED = 102;

    public const STATUS_REMOVED = 103;

    public const STATUS_FAILED = 104;

    // types
    public const TYPE_USER_LIST_REPORT = 1;

    //## Accessors
    public function getStatusAttribute()
    {
        $statusList = self::getStatusList();

        return $statusList[$this->status_key] ?? '-';
    }

    public function getTypeAttribute()
    {
        $typeList = self::getTypeList();

        return $typeList[$this->type_key] ?? '-';
    }

    //## Mutators

    //## Relationships
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function file()
    {
        return $this->morphOne(ModelableFile::class, 'modelable');
    }

    //## Extend relationships

    //## Methods

    //## Static Methods
    public static function getStatusList()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_SUCCESS => 'Success',
            self::STATUS_EXPIRED => 'Expired',
            self::STATUS_REMOVED => 'Removed',
            self::STATUS_FAILED => 'Failed',
        ];
    }

    public static function getTypeList()
    {
        return [
            self::TYPE_USER_LIST_REPORT => 'User List',
        ];
    }
}
