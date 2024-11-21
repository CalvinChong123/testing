<?php

namespace App\Http\Services;

use App\Exceptions\BadRequestException;
use App\Library\RoleTag;
use App\Models\ModelableFile;
use App\Models\Admin;
use App\Models\UserPointBalance;
use App\Notifications\VerifyAccountNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Swift_TransportException;
use App\Models\CashFloat;
use App\Models\Config;
use App\Models\PromotionCreditApprovalReport;
use App\Models\UserPromotionCreditBalance;
use App\Models\Transaction;

class AdminService
{
    public static function create(array $payload): Admin
    {
        if (Admin::withTrashed()->where('email', $payload['email'])->exists()) {
            throw new BadRequestException('Email has been taken');
        }


        $result = DB::transaction(function () use ($payload) {
            $user = new Admin;

            $user = $user->create([
                'name' => $payload['name'],
                'email' => $payload['email'],
                'password' => isset($payload['password']) ? Hash::make($payload['password']) : 'p@ssw0rd',
                'email_verified_at' => $payload['email_verified_at'] ?? null,
            ]);

            $user->assignRole($payload['role']);

            $user->updateAuditWithRole($user->roles->pluck('name')->first());
            $message = $user->generateNotificationMessage('created');

            $user->notificationBlast($message);

            return $user;
        });

        return $result;
    }

    public static function update(Admin $user, array $payload): Admin
    {
        $originalRole = $user->roles->pluck('name')->first();

        $result = DB::transaction(function () use ($user, $payload, $originalRole) {
            $user->update([
                'name' => $payload['name'] ?? $user->name,
                'email' => $payload['email'] ?? $user->email,
                'password' => $payload['password'] ? Hash::make($payload['password']) : $user->password,
            ]);

            $userChanged = $user->isDirty();

            if (isset($payload['role'])) {
                $user->syncRoles($payload['role']);
                $newRole = $user->roles->pluck('name')->first();
                if (!$userChanged && $originalRole != $newRole) {
                    $user->onlyRoleChangingAuditRecord('updated', $user, ['role' => $originalRole], ['role' => $newRole]);
                }
            }
            $user->updateAuditWithRole($payload['role']);
            return $user;
        });

        return $result;
    }

    public static function uploadAvatar(Admin $user, $payload): ModelableFile
    {
        return $user->syncResizedImageFor('avatar', $payload['image'], ModelableFile::MODULE_PATH_USER_AVATAR, 800);
    }

    public static function delete(Admin $user)
    {
        $user->restoreOrDelete();

        return $user;
    }
}
