<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\BadRequestException;
use App\Exports\Excel\UserListExport as ExcelUserListExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\User\CreateFormRequest as UserCreateFormRequest;
use App\Http\Requests\Admin\User\UpdateFormRequest as UserUpdateFormRequest;
use App\Http\Requests\Admin\User\DeleteFormRequest as UserDeleteFormRequest;
use App\Http\Requests\Admin\User\InfoFormRequest as UserInfoFormRequest;
use App\Http\Requests\Admin\User\ListFormRequest as UserListFormRequest;
use App\Http\Requests\Admin\User\PointInfoFormRequest as UserPointInfoFormRequest;
use App\Http\Requests\Admin\User\PointUpdateFormRequest as UserPointUpdateFormRequest;
use App\Http\Resources\Admin\UserResource;
use App\Http\Services\UserService;
use App\Queriplex\UserQueriplex;
use Illuminate\Http\JsonResponse;
use App\Http\Services\SyncService;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    // public function list(UserListFormRequest $request)
    // {
    //     $payload = $request->validated();
    //     $payload['sort_by'] = 'created_time';

    //     $query = UserQueriplex::make(User::query(), $payload);


    //     if (isset($payload['items_per_page'])) {
    //         $result = $query->paginate($payload['items_per_page']);
    //     } else {
    //         $result = $query->get();
    //     }

    //     $result->load([
    //         // 'userPointBalance',
    //         // 'userPromotionCreditBalance',
    //         // 'referrals',
    //         // 'referrals',
    //         // 'referrer',
    //         // 'referrer.referrer'
    //     ]);
    //     $response = [
    //         'users' => isset($payload['items_per_page']) ? UserResource::paginateCollection($result) : UserResource::collection($result),
    //     ];

    //     return self::successResponse('Success', $response);
    // }


    public function list(Request $request)
    {
        SyncService::syncUsers();
        $payload = $request->all();
        $payload['sort_by'] = 'created_time';

        $path = "/api/user/list";
        try {
            $hqResponse = SyncService::request('get', $path, $payload);


            $hqUsers = $hqResponse['users']['data'] ?? $hqResponse['users'];
            $pagination = $hqResponse['users'] ?? null;

            $usersWithRelations = collect();

            foreach ($hqUsers as $hqUser) {
                $user = new User($hqUser);

                $user->setRelation('userPointBalance', $user->userPointBalance()->get());
                $user->setRelation('userPromotionCreditBalance', $user->userPromotionCreditBalance()->get());
                $user->setRelation('referrals', $user->referrals()->get());
                // $user->setRelation('referrer', $user->referrer()->get());
                $usersWithRelations->push($user);
            }

            if (isset($payload['items_per_page'])) {
                $response = [
                    'users' => [
                        'current_page' => $pagination['current_page'],
                        'data' => UserResource::collection($usersWithRelations),
                        'from' => $pagination['from'],
                        'to' => $pagination['to'],
                        'total' => $pagination['total'],
                    ]
                ];
            } else {
                $response = [
                    'users' =>  UserResource::collection($usersWithRelations),
                ];
            }
            return self::successResponse('Success', $response);
        } catch (BadRequestException $e) {
            return new JsonResponse(json_decode($e->getMessage(), true), $e->getCode());
        }
    }


    public function info(UserInfoFormRequest $request)
    {
        $payload = $request->validated();

        $path = "/api/user/info/{$payload['id']}";

        try {
            $hqUser = UserService::info($payload['id']);
            $user = new User($hqUser);

            $user->setRelation('userPointBalance', $user->userPointBalance()->get());
            $user->setRelation('userPromotionCreditBalance', $user->userPromotionCreditBalance()->get());
            $user->setRelation('referrals', $user->referrals()->get());
            // $user->setRelation('referrer', $user->referrer()->get());
            $response = [
                'user' => $user,
            ];

            return self::successResponse('Success', $response);
        } catch (BadRequestException $e) {
            return new JsonResponse(json_decode($e->getMessage(), true), $e->getCode());
        }
    }

    public function create(Request $request)
    {
        // SyncService::syncUsers();
        $payload = $request->all();
        try {
            $user = UserService::create($payload);
            if ($payload['referrer']) {
                $userModel = new User($user);
                $userModel->assignReferrer($payload['referrer']);
            }
            return self::successResponse('Success', $user);
        } catch (BadRequestException $e) {
            return new JsonResponse(json_decode($e->getMessage(), true), $e->getCode());
        }
    }

    public function update(Request $request)
    {
        try {
            $user = UserService::update($request);
            return self::successResponse('Success', $user);
        } catch (BadRequestException $e) {
            return new JsonResponse(json_decode($e->getMessage(), true), $e->getCode());
        }
    }

    public function delete(UserDeleteFormRequest $request)
    {
        $payload = $request->validated();
        $path = "/api/user/delete";

        try {
            $data = SyncService::request('delete', $path, $payload);
            $response = [
                'user' => $data,
            ];
            return self::successResponse('Success', $response);
        } catch (BadRequestException $e) {
            return new JsonResponse(json_decode($e->getMessage(), true), $e->getCode());
        }
    }
}
