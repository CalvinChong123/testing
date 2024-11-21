<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\BadRequestException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SptRoleAndPermission\CreateFormRequest as SptRoleCreateFormRequest;
use App\Http\Requests\Admin\SptRoleAndPermission\InfoFormRequest as SptRoleInfoFormRequest;
use App\Http\Requests\Admin\SptRoleAndPermission\ListFormRequest as SptRoleListFormRequest;
use App\Http\Requests\Admin\SptRoleAndPermission\PermissionListFormRequest as SptPermissionListFormRequest;
use App\Http\Requests\Admin\SptRoleAndPermission\UpdateFormRequest as SptRoleUpdateFormRequest;
use App\Http\Resources\Admin\CategorizedPermissionMappingsResource;
use App\Http\Resources\Admin\RoleResource;
use App\Models\SptPermission;
use App\Models\SptRole;
use App\Queriplex\SptPermissionQueriplex;
use App\Queriplex\SptRoleQueriplex;
use Illuminate\Support\Facades\DB;

class SptRoleAndPermissionController extends Controller
{
    public function roleList(SptRoleListFormRequest $request)
    {
        $payload = $request->validated();

        $authUser = auth()->user();
        $payload['highest_classification_level'] = $authUser->getHighestClassificationLevel();

        $query = SptRoleQueriplex::make(SptRole::query(), $payload);

        $result = $query->paginate($payload['items_per_page'] ?? 15);

        $result->load([
            'permissions',
        ]);

        $result->loadCount([
            'users' => function ($q) use ($authUser) {
                $q->where('classification_level', '<=', $authUser->getHighestClassificationLevel());
            },
        ]);

        $response = [
            'roles' => RoleResource::paginateCollection($result),
        ];

        return self::successResponse('Success', $response);
    }

    public function roleInfo(SptRoleInfoFormRequest $request)
    {

        $payload = $request->validated();
        $authUser = auth()->user();
        $payload['highest_classification_level'] = $authUser->getHighestClassificationLevel();

        $query = SptRoleQueriplex::make(SptRole::query(), $payload);
        // $queryString = $query->toSql();
        // $bindings = $query->getBindings();
        // return ['query' => $queryString, 'bindings' => $bindings];
        $result = $query->firstOrThrowError();

        $result->load([
            'permissions',
        ]);

        $result->loadCount([
            'users' => function ($q) use ($authUser) {
                $q->where('classification_level', '<=', $authUser->getHighestClassificationLevel());
            },
        ]);

        $response = [
            'role' => new RoleResource($result),
            'users_count' => $result->users_count,
        ];

        return self::successResponse('Success', $response);
    }

    public function create(SptRoleCreateFormRequest $request)
    {
        $payload = $request->validated();

        $result = DB::transaction(function () use ($payload) {
            $role = SptRole::create([
                'name' => $payload['name'],
                'guard_name' => 'web',
            ]);

            $role->syncPermissions($payload['permission_names']);

            return $role;
        });

        return self::successResponse('Success', $result);
    }

    public function update(SptRoleUpdateFormRequest $request)
    {

        $payload = $request->validated();
        $result = DB::transaction(function () use ($payload) {
            $role = SptRole::query()
                ->where('id', $payload['id'])
                ->firstOrThrowError();

            $role->update([
                'name' => $payload['name'],
            ]);

            $role->syncPermissions($payload['permission_names']);

            return $role;
        });

        return self::successResponse('Success', $result);
    }

    public function permissionList(SptPermissionListFormRequest $request)
    {
        $payload = $request->validated();
        $authUser = auth()->user();

        $payload['highest_classification_level'] = $authUser->getHighestClassificationLevel();
        $permissions = SptPermissionQueriplex::make(SptPermission::query(), $payload)
            ->get();

        $response = [
            'categorized_permission_mappings' => new CategorizedPermissionMappingsResource($permissions),
        ];

        return self::successResponse('Success', $response);
    }

    public function delete(SptRoleInfoFormRequest $request)
    {
        $payload = $request->validated();

        $response = DB::transaction(function () use ($payload) {
            $role = SptRole::query()
                ->where('id', $payload['id'])
                ->firstOrThrowError();

            $usersCount = $role->users()->count();

            if ($usersCount > 0) {
                throw new BadRequestException('Role cannot be deleted because it has associated users', 403);
            }

            // Proceed with deletion
            $role->delete();

            return true;
        });

        return self::successResponse('Success', $response);
    }
}
