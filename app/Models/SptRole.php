<?php

namespace App\Models;

use Carbon\Carbon;
use Pylon\Models\Traits\HasModelKit;
use Spatie\Permission\Models\Role as SpatieRole;

class SptRole extends SpatieRole
{
    use HasModelKit;

    protected $fillable = [
        'name',
        'guard_name',
        'classification_level',
    ];

    protected $appends = ['created_at_date', 'created_at_time'];

    protected $hidden = [];

    protected $casts = [];

    //## Accessors

    public function getCreatedAtDateAttribute()
    {
        return Carbon::parse($this->attributes['created_at'])->format('d-m-Y');
    }

    public function getCreatedAtTimeAttribute()
    {
        return Carbon::parse($this->attributes['created_at'])->format('h:iA');
    }

    //## Mutators

    //## Relationships
    public function detail()
    {
        return $this->morphTo();
    }

    //## Extend relationships

    //## Methods

    //## Static Methods


}
