<?php

namespace App\Services;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class AdminUserService
{
    /**
     * List users with filters and pagination.
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $q = User::query()->orderByDesc('created_at');

        if (!empty($filters['q'])) {
            $term = $filters['q'];
            $q->where(function ($w) use ($term) {
                $w->where('name', 'ilike', "%{$term}%")
                  ->orWhere('email', 'ilike', "%{$term}%");
            });
        }

        return $q->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Enable or disable a user with safety checks.
     * - Cannot disable self.
     * - Cannot disable the last enabled admin.
     */
    public function setEnabled(User $actingUser, User $target, bool $enabled): User
    {
        // Prevent disabling self
        if ($actingUser->id === $target->id && $enabled === false) {
            throw ValidationException::withMessages([
                'enabled' => __('users.cannot_disable_self'),
            ]);
        }

        // Prevent disabling the last enabled admin
        if ($target->role === RoleEnum::ADMIN && $target->enabled && $enabled === false) {
            $otherEnabledAdmins = User::where('role', RoleEnum::ADMIN->value)
                ->where('enabled', true)
                ->where('id', '!=', $target->id)
                ->count();

            if ($otherEnabledAdmins === 0) {
                throw ValidationException::withMessages([
                    'enabled' => __('users.cannot_disable_last_admin'),
                ]);
            }
        }

        $target->enabled = $enabled;
        $target->save();

        return $target->fresh();
    }
}
