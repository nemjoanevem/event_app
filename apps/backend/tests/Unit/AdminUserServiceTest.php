<?php

namespace Tests\Unit;

use App\Enums\RoleEnum;
use App\Models\User;
use App\Services\AdminUserService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class AdminUserServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(RoleEnum $role, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => $role->value,
            'enabled' => true,
        ], $overrides));
    }

    public function test_cannot_disable_self(): void
    {
        $svc = new AdminUserService();
        $admin = $this->makeUser(RoleEnum::ADMIN);

        $this->expectException(ValidationException::class);
        $svc->setEnabled($admin, $admin, false);
    }

    public function test_cannot_disable_last_enabled_admin(): void
    {
        $svc = new AdminUserService();
        $admin = $this->makeUser(RoleEnum::ADMIN);
        $otherAdmin = $this->makeUser(RoleEnum::ADMIN, ['enabled' => false]);

        $this->expectException(ValidationException::class);
        $svc->setEnabled($admin, $admin, false);
    }

    public function test_can_disable_non_admin_user(): void
    {
        $svc = new AdminUserService();
        $admin = $this->makeUser(RoleEnum::ADMIN);
        $user = $this->makeUser(RoleEnum::USER);

        $updated = $svc->setEnabled($admin, $user, false);

        $this->assertFalse($updated->enabled);
    }
}
