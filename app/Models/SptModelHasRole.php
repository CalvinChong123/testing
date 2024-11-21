<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Observers\SyncLogObserver;

class SptModelHasRole extends Model
{

    protected $table = 'spt_model_has_roles';
    protected $fillable = [
        'role_id',
        'model_type',
        'model_id',
    ];

    protected $hidden = [];

    protected $casts = [];

    // Accessors

    // Mutators

    // Relationships
    public function detailable()
    {
        return $this->morphTo();
    }

    // Extend Relationships

    // Methods

    // Static Methods

    protected static function boot()
    {
        parent::boot();
        self::observe(SyncLogObserver::class);
    }
}
