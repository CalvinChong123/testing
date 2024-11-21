<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

class SptPermission extends SpatiePermission
{

    protected $fillable = [
        'name',
        'guard_name',
        'category',
        'action',
        'action_level',
        'classification_level',
        'display_name',
    ];

    protected $hidden = [];

    protected $casts = [];

    //## Accessors

    //## Mutators

    //## Relationships

    //## Extend relationships

    //## Methods

    //## Static Methods
}
