<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\BadRequestException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AuthPasswordUpdateFormRequest;
use App\Http\Requests\Admin\Auth\AuthProfileUpdateFormRequest;
use App\Http\Requests\Admin\Auth\ForgotPasswordRequest;
use App\Http\Requests\Admin\Auth\LoginFormRequest;
use App\Http\Requests\Admin\Auth\ResetPasswordRequest;
use App\Http\Requests\Admin\Auth\UploadAvatarFormRequest;
use App\Http\Resources\Admin\AdminResource;
use App\Http\Services\AuthService;
use App\Http\Services\UserService;
use App\Library\PermissionTag;
use App\Models\SptPermission;
use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function checkFirstTimeLogin(LoginFormRequest $request)
    {
        $payload = $request->validated();
        $payload['credentials'] = [
            'email' => $payload['email'],
            'password' => $payload['password'],
        ];

        return DB::transaction(function () use ($payload) {
            $token = AuthService::login($payload);

            // check for permission
            $authUser = auth()->user();

            // dd($authUser->getPermissionsViaRoles());

            if (!$authUser->hasPermissionTo(PermissionTag::ACCESS_ADMIN_PANEL)) {
                throw new BadRequestException('Your Access Permission is Unauthorized', 403);
            }

            return self::successResponse('Success', [
                'user' => new AdminResource($authUser),
            ]);
        });
    }

    public function login(LoginFormRequest $request)
    {
        $payload = $request->validated();

        $payload['credentials'] = [
            'email' => $payload['email'],
            'password' => $payload['password'],
        ];

        return DB::transaction(function () use ($payload) {

            $user = Admin::where('email', $payload['credentials']['email'])->first();

            if (! is_null($user->first_time_login)) {
                $token = AuthService::login($payload);
            } else {
                $token = AuthService::firstTimeLogin($payload);
            }

            // check for permission
            $authUser = auth()->user();

            if (! $authUser->hasPermissionTo(PermissionTag::ACCESS_ADMIN_PANEL)) {
                throw new BadRequestException('Your Access Permission is Unauthorized', 403);
            }

            return self::successResponse('Success', [
                'token' => $token->plainTextToken,
                'user' => new AdminResource($authUser),
            ])->header('Authorization', $token->plainTextToken);
        });
    }

    public function logout()
    {
        $result = AuthService::logout([]);

        return self::successResponse('Success', $result);
    }

    public function authUpdate(AuthProfileUpdateFormRequest $request)
    {
        $payload = $request->validated();

        $authUser = auth()->user();

        $result = UserService::updateAdmin($authUser, $payload);

        return self::successResponse('Success', $result);
    }

    public function user()
    {
        $authUser = auth()->user();

        $authUser->load([
            'avatar',
        ]);

        $authUser->permission_names = $authUser->getAllPermissions()->map(function (SptPermission $permission) {
            return $permission->name;
        });

        return self::successResponse('Success', [
            'user' => new AdminResource($authUser),
        ]);
    }

    public function updatePassword(AuthPasswordUpdateFormRequest $request)
    {
        $payload = $request->validated();

        $authUser = auth()->user();

        $errors = [];

        // check current password
        if (!Hash::check($request['current_password'], $authUser->password)) {
            $errors['current_password'] = 'The existing password field is invalid';
        }

        // see if old and new password are equal
        if (Hash::check($request['password'], $authUser->password)) {
            $errors['password'] = 'New password cannot be the same as current password';
        }

        if (!empty($errors)) {
            return self::customValidationException($errors);
        }

        $result = UserService::updateAdmin($authUser, [
            'password' => Hash::make($payload['password']),
        ]);

        return self::successResponse('Success', [
            'status' => ($result != null),
        ]);
    }

    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $payload = $request->validated();

        $result = AuthService::forgotPassword($payload['email']);

        return self::successResponse('Success', [
            'email' => $result,
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request)
    {
        $result = AuthService::resetPassword($request->validated());

        return self::successResponse('Success', $result);
    }

    public function uploadAvatar(UploadAvatarFormRequest $request)
    {
        $payload = $request->validated();

        $authUser = auth()->user();

        $result = UserService::uploadAvatar($authUser, $payload);

        return self::successResponse('Success', $result);
    }
}
