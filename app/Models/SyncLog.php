<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Pylon\Models\Traits\HasModelKit;
use Pylon\Models\Traits\HasSoftDeletesActivity;

class SyncLog extends Model
{
    use HasFactory;
    use HasModelKit;
    use HasSoftDeletesActivity;
    use SoftDeletes;

    protected $fillable = [
        'model_type',
        'model_id',
        'event',
    ];

    protected $hidden = [];

    protected $casts = [];

    ### Accessors

    ### Mutators

    ### Relationships

    ### Extend relationships

    ### Methods

    ### Static Methods
}
