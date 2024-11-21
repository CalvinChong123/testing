<?php

namespace App\Models;

use Carbon\Carbon;
use OwenIt\Auditing\Models\Audit as BaseAudit;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\MerchantGroup;

class Audit extends BaseAudit
{
    protected static $latestAuditId;

    protected $fillable = [
        'user_type',
        'user_id',
        'user_name',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'url',
        'ip_address',
        'user_agent',
        'tags',
        'module',
        'description'
    ];

    protected $appends = [
        'created_at_date_time'
    ];

    public function getCreatedAtDateTimeAttribute()
    {
        return $this->created_at->format('d-n-Y h:iA');
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // set user name
            if (Auth::check()) {
                $model->user_name = Auth::user()->name;
            }
            // set module with spaces between camel case words
            $model->module = preg_replace('/(?<!^)([A-Z])/', ' $1', class_basename($model->auditable_type));
            if ($model->auditable_type === CashReplenishment::class) {
                $model->module = 'Cash Float';
            }
            if ($model->auditable_type === ApprovalRequest::class || $model->auditable_type === ApprovalLayer::class || $model->auditable_type === ApprovalLog::class) {
                $model->module = 'Approval Activity';
            }

            // set password as ********
            $model->new_values = $model->maskPassword($model->new_values);
            $model->old_values = $model->maskPassword($model->old_values);

            // set format dates if any
            $model->new_values = $model->formatDates($model->new_values);
            $model->old_values = $model->formatDates($model->old_values);

            // set relationship related names
            $model->new_values = $model->addRelatedNames($model->new_values);
            $model->old_values = $model->addRelatedNames($model->old_values);
        });

        static::created(function ($model) {
            self::$latestAuditId = $model->id;
        });
    }

    public static function getLatestAuditId()
    {
        return self::$latestAuditId;
    }

    public function maskPassword($values)
    {
        if (isset($values['password'])) {
            $values['password'] = '********';
        }
        return $values;
    }

    public function formatDates($values)
    {
        foreach ($values as $key => $value) {
            // temporary solution here, cuz it keep treat cashfloat->cash_in as date
            if ($key !== 'cash_in' && $key !== 'cash_out' && $key !== 'amount' && $this->isDate($value)) {
                $values[$key] = Carbon::parse($value)->format('d-m-Y h:iA');
            }
        }
        return $values;
    }

    private function isDate($value)
    {
        return strtotime($value) !== false;
    }

    public function addRelatedNames($values)
    {
        $orderedValues = [];

        foreach ($values as $key => $value) {
            if (str_ends_with($key, '_id')) {
                // Add the _id value first
                $orderedValues[$key] = $value;

                // Look for the related model and add the _name value after the _id
                $relatedModel = $this->getRelatedModel($key, $value);
                if ($relatedModel) {
                    $nameKey = str_replace('_id', '_name', $key);
                    $orderedValues[$nameKey] = $relatedModel->name ?? null;
                }
            } else {
                // If it's not an _id key, just add it
                $orderedValues[$key] = $value;
            }
        }

        return $orderedValues;
    }

    private function getRelatedModel($key, $id)
    {
        // Get the related model class based on the key (ID column)
        $relatedModelClass = $this->getRelatedModelClass($key);
        if ($relatedModelClass && class_exists($relatedModelClass)) {
            // Find the related model by the ID
            return $relatedModelClass::find($id);
        }
        return null;
    }

    private function getRelatedModelClass($key)
    {
        $mapping = [
            'user_id' => User::class,
            'referrer_user_id' => User::class,
            'cash_in_admin_id' => Admin::class,
            'cash_out_admin_id' => Admin::class,
            'merchant_group_id' => MerchantGroup::class,
            'merchant_id' => Merchant::class,
            'merchant_user_id' => User::class,
            'admin_id' => Admin::class,
            'request_by_admin_id' => Admin::class,
        ];
        return $mapping[$key] ?? null;
    }

    // Relationships
    public function user()
    {
        return $this->morphTo();
    }
}
