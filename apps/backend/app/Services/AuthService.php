<?php

namespace App\Services;

use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Enums\RoleEnum;

class AuthService
{
    protected function guard(): StatefulGuard
    {
        return Auth::guard('web');
    }
    
    /**
     * Attempts to log in a user with the provided email and password.
     *
     * @param string $email The user's email address.
     * @param string $password The user's password.
     * @throws \Exception If authentication fails or an error occurs during login.
     * @return void
     */
    public function login(string $email, string $password): void
    {
        if (! $this->guard()->attempt(['email' => $email, 'password' => $password], remember: true)) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.invalid_credentials')],
            ]);
        }

        request()->session()->regenerate();
    }

    
    /**
     * Logs out the currently authenticated user.
     *
     * This method handles the process of terminating the user's session
     * and performing any necessary cleanup during logout.
     *
     * @return void
     */
    public function logout(): void
    {
        $this->guard()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
    }

    
    /**
     * Registers a new user with the provided data.
     *
     * @param array $data The registration data for the new user.
     * @return User The newly registered user instance.
     */
    public function register(array $data): User
    {
        return DB::transaction(function () use ($data) {
            // Create user
            $user = User::create([
                'name'     => $data['name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'role'     => RoleEnum::USER,
            ]);

            Auth::login($user);

            return $user;
        });
    }

   
    /**
     * Retrieves the currently authenticated user.
     *
     * @return User|null Returns the current user instance if authenticated, or null if not authenticated.
     */
    public function currentUser()
    {
        return Auth::user();
    }
}
