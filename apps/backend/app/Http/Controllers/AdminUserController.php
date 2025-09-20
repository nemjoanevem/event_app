<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\UserIndexRequest;
use App\Http\Requests\Admin\UserToggleEnabledRequest;
use App\Http\Resources\UserAdminResource;
use App\Models\User;
use App\Services\AdminUserService;

class AdminUserController extends Controller
{
    public function __construct(private AdminUserService $service)
    {
        //
    }

    /**
     * List users (paginated) with optional filters.
     */
    public function index(UserIndexRequest $request)
    {
        $users = $this->service->list($request->validated());
        return UserAdminResource::collection($users);
    }

    /**
     * Enable/disable a user.
     */
    public function setEnabled(UserToggleEnabledRequest $request, User $user)
    {
        $updated = $this->service->setEnabled(
            actingUser: $request->user(),
            target: $user,
            enabled: (bool) $request->validated('enabled')
        );

        return (new UserAdminResource($updated))
            ->additional(['message' => __('users.updated')]);
    }
}
