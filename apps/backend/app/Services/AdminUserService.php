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

        // Search by name/email (PostgreSQL-friendly)
        if (!empty($filters['q'])) {
            $term = $filters['q'];
            $like = $this->likeOperator();
            $q->where(function ($w) use ($term, $like) {
                $w->where('name', $like, "%{$term}%")
                  ->orWhere('email', $like, "%{$term}%");
            });
        }

        // Filter by role
        if (!empty($filters['role'])) {
            $q->where('role', $filters['role']);
        }

        // Filter by enabled
        if (array_key_exists('enabled', $filters) && $filters['enabled'] !== null) {
            $q->where('enabled', (bool) $filters['enabled']);
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

    private function likeOperator(): string
    {
        return \DB::getDriverName() === 'pgsql' ? 'ilike' : 'like';
    }
}
