<?php

namespace App\Http\Controllers\Admin;

use App\Events\TestEvent;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Admin\CreateFormRequest as AdminCreateFormRequest;
use App\Http\Requests\Admin\Admin\DeleteFormRequest as AdminDeleteFormRequest;
use App\Http\Requests\Admin\Admin\InfoFormRequest as AdminInfoFormRequest;
use App\Http\Requests\Admin\Admin\ListFormRequest as AdminListFormRequest;
use App\Http\Requests\Admin\Admin\UpdateFormRequest as AdminUpdateFormRequest;
use App\Http\Resources\Admin\RoleResource;
use App\Http\Resources\Admin\AdminResource;
use App\Http\Services\AdminService;
use App\Models\SptPermission;
use App\Models\SptRole;
use App\Models\Admin;
use App\Queriplex\SptRoleQueriplex;
use App\Queriplex\AdminQueriplex;
use Illuminate\Support\Facades\Auth;
use App\Library\RoleTag;


class AdminController extends Controller
{
    public function list(AdminListFormRequest $request)
    {
        // $data = ['message' => 'Hello world!'];
        // broadcast(new TestEvent($data));
        $payload = $request->validated();

        $payload['sort_by'] = 'created_time';

        $authUser = auth()->user();
        $query = AdminQueriplex::make(Admin::query(), $payload);
        $query->whereHas('roles', function ($q) use ($authUser, $payload) {
            $q->where('name', '!=', 'User');
            $q->where('classification_level', '<=', $authUser->getHighestClassificationLevel());

            if (isset($payload['role_id'])) {
                $q->where('id', $payload['role_id']);
            }
        });
        $result = $query->paginate($payload['items_per_page'] ?? 15);

        $result->load([
            'roles',
            'avatar',
        ]);

        // $result->load(Admin::loadable($request));

        $response = [
            'users' => AdminResource::paginateCollection($result),
        ];

        return self::successResponse('Success', $response);
    }

    public function info(AdminInfoFormRequest $request)
    {
        $payload = $request->validated();

        $result = Admin::where('id', $payload['id'])
            ->withTrashed()
            ->firstOrThrowError();

        $result->load([
            'tokens',
            'roles',
            'avatar',
        ]);

        $result->permission_names = $result->getAllPermissions()->map(function (SptPermission $permission) {
            return $permission->name;
        });

        $response = [
            'user' => new AdminResource($result),
        ];

        return self::successResponse('Success', $response);
    }

    public function create(AdminCreateFormRequest $request)
    {
        $payload = $request->validated();

        $user = AdminService::create($payload);

        return self::successResponse('Success', $user);
    }

    public function update(AdminUpdateFormRequest $request)
    {
        $payload = $request->validated();

        $user = Admin::where('id', $payload['id'])
            ->withTrashed()
            ->firstOrThrowError();

        $result = AdminService::update($user, $payload);

        return self::successResponse('Success', $result);
    }

    public function delete(AdminDeleteFormRequest $request)
    {
        $payload = $request->validated();

        $user = Admin::where('id', $payload['id'])
            ->withTrashed()
            ->firstOrThrowError();

        $result = AdminService::delete($user);

        return self::successResponse('Success', $result);
    }

    public function rolesList()
    {
        $authUser = Auth::user();
        $payload['highest_classification_level'] = $authUser->getHighestClassificationLevel();

        $query = SptRoleQueriplex::make(SptRole::query(), $payload);

        $query->where('name', '!=', RoleTag::USER);

        $result = $query->paginate($payload['items_per_page'] ?? 15);

        $response = [
            'roles' => RoleResource::paginateCollection($result),
        ];

        return self::successResponse('Success', $response);
    }

    public function roleInfo(AdminInfoFormRequest $request)
    {

        $payload = $request->validated();
        $response = Admin::where('id', $payload['id'])
            ->withTrashed()
            ->with('roles')
            ->firstOrThrowError();

        return self::successResponse('Success', $response['roles'][0]);
    }
}
