<?php

namespace App\Library;

use Illuminate\Database\Eloquent\Model;
use ReflectionClass;

class PermissionTag
{
    //################################# PERMISSIONS ##############################################

    // CLASSIFICATION_LEVEL
    public const CLASSIFICATION_LEVEL_101 = 'classification_level_101';
    public const CLASSIFICATION_LEVEL_100 = 'classification_level_100';

    // Admin Panel
    public const ACCESS_ADMIN_PANEL = 'access.admin-panel';

    // Overwatch Panel
    public const ACCESS_OVERWATCH_PANEL = 'access.overwatch-panel';
    public const ACCESS_TELESCOPE = 'access.telescope';

    // user module
    public const ACCESS_USER_MODULE = 'access.user-module';
    public const CREATE_USER_MODULE = 'create.user-module';
    public const UPDATE_USER_MODULE = 'update.user-module';
    public const DELETE_USER_MODULE = 'delete.user-module';
    public const NOTIFICATION_USER_MODULE = 'notification.user-module';

    // admin module
    public const ACCESS_ADMIN_MODULE = 'access.admin-module';
    public const CREATE_ADMIN_MODULE = 'create.admin-module';
    public const UPDATE_ADMIN_MODULE = 'update.admin-module';
    public const DELETE_ADMIN_MODULE = 'delete.admin-module';
    public const NOTIFICATION_ADMIN_MODULE = 'notification.admin-module';

    // merchant module
    public const ACCESS_MERCHANT_MODULE = 'access.merchant-module';
    public const CREATE_MERCHANT_MODULE = 'create.merchant-module';
    public const UPDATE_MERCHANT_MODULE = 'update.merchant-module';
    public const DELETE_MERCHANT_MODULE = 'delete.merchant-module';
    public const ADVANCE_MERCHANT_MODULE = 'advance.merchant-module';
    public const NOTIFICATION_MERCHANT_MODULE = 'notification.merchant-module';

    // role module
    public const ACCESS_ROLE_AND_PERMISSION_MODULE = 'access.role-and-permission-module';
    public const CREATE_ROLE_AND_PERMISSION_MODULE = 'create.role-and-permission-module';
    public const UPDATE_ROLE_AND_PERMISSION_MODULE = 'update.role-and-permission-module';
    public const DELETE_ROLE_AND_PERMISSION_MODULE = 'delete.role-and-permission-module';
    public const NOTIFICATION_ROLE_AND_PERMISSION_MODULE = 'notification.role-and-permission-module';

    // promotion credit module
    public const ACCESS_PROMOTION_CREDIT_MODULE = 'access.promotion-credit-module';
    public const ADVANCE_PROMOTION_CREDIT_MODULE = 'advance.promotion-credit-module';
    public const NOTIFICATION_PROMOTION_CREDIT_MODULE = 'notification.promotion-credit-module';

    // promotion credit tier module
    public const ACCESS_PROMOTION_CREDIT_TIER_MODULE = 'access.promotion-credit-tier-module';
    public const UPDATE_PROMOTION_CREDIT_TIER_MODULE = 'update.promotion-credit-tier-module';
    public const NOTIFICATION_PROMOTION_CREDIT_TIER_MODULE = 'notification.promotion-credit-tier-module';

    // referral module
    public const ACCESS_REFERRAL_MODULE = 'access.referral-module';
    public const NOTIFICATION_REFERRAL_MODULE = 'notification.referral-module';

    // transaction module
    public const ACCESS_TRANSACTION_MODULE = 'access.transaction-module';
    public const NOTIFICATION_TRANSACTION_MODULE = 'notification.transaction-module';

    // notification module
    public const ACCESS_NOTIFICATION_MODULE = 'access.notification-module';

    // approval module
    public const ACCESS_APPROVAL_MODULE = 'access.approval-module';
    public const UPDATE_APPROVAL_MODULE = 'update.approval-module';
    public const NOTIFICATION_APPROVAL_MODULE = 'notification.approval-module';

    // cash float module
    public const ACCESS_CASH_FLOAT_MODULE = 'access.cash-float-module';
    public const CREATE_CASH_FLOAT_MODULE = 'create.cash-float-module';
    public const UPDATE_CASH_FLOAT_MODULE = 'update.cash-float-module';
    public const NOTIFICATION_CASH_FLOAT_MODULE = 'notification.cash-float-module';

    // config module
    public const ACCESS_CONFIG_MODULE = 'access.config-module';
    public const UPDATE_CONFIG_MODULE = 'update.config-module';

    // activity log module
    public const ACCESS_ACTIVITY_LOG_MODULE = 'access.activity-log-module';
    public const ADVANCE_ACTIVITY_LOG_MODULE = 'advance.activity-log-module';


    public static function getAllPermissions()
    {
        $reflection = new ReflectionClass(__CLASS__);
        $constants = $reflection->getConstants();

        return array_values($constants);
    }

    public static function getModelHighestClassificationLevel(Model $model)
    {
        $classificationLevels = [
            self::CLASSIFICATION_LEVEL_101 => 101,
            self::CLASSIFICATION_LEVEL_100 => 100,
            // ...
        ];

        foreach ($classificationLevels as $classificationLevel => $level) {
            if ($model->hasPermissionTo($classificationLevel)) {
                return $level;
            }
        }
        $fallbackClassificationLevel = 1;

        return $fallbackClassificationLevel;
    }
}
