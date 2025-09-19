<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    public function __construct(private AuthService $auth) {}

    
    /**
     * Handles the user login request.
     *
     * Validates the login credentials provided in the LoginRequest,
     * authenticates the user, and returns a UserResource on successful login.
     *
     * @param  LoginRequest  $request  The request containing user login credentials.
     * @return UserResource            The authenticated user's resource representation.
     */
    public function login(LoginRequest $request): UserResource
    {
        $data = $request->validated();
        $this->auth->login($data['email'], $data['password']);
        return new UserResource($this->auth->currentUser());
    }

    
    /**
     * Logs out the currently authenticated user.
     *
     * @return \Illuminate\Http\JsonResponse JSON response indicating the result of the logout operation.
     */
    public function logout(): JsonResponse
    {
        $this->auth->logout();

        return response()->json([
            'message' => trans('auth.logged_out'),
        ]);
    }

    
    /**
     * Returns the authenticated user's information as a UserResource.
     *
     * @return UserResource The resource representation of the authenticated user.
     */
    public function user(): UserResource
    {
        return new UserResource($this->auth->currentUser());
    }
}
