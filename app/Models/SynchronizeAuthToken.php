<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SynchronizeAuthToken extends Model
{
    use HasFactory;

    protected $table = 'synchronize_auth_token';

    protected $fillable = [
        'token'
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
