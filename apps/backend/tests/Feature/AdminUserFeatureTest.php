<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminUserFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function makeUser(RoleEnum $role, array $overrides = []): User
    {
        return User::factory()->create(array_merge([
            'role' => $role->value,
            'enabled' => true,
        ], $overrides));
    }

    public function test_non_admin_cannot_access_admin_endpoints(): void
    {
        $user = $this->makeUser(RoleEnum::USER);

        Sanctum::actingAs($user);

        $this->getJson('/api/admin/users')
            ->assertStatus(403);

        $target = $this->makeUser(RoleEnum::USER);

        $this->patchJson("/api/admin/users/{$target->id}/enabled", ['enabled' => false])
            ->assertStatus(403);
    }

    public function test_admin_can_list_users_with_pagination_and_filters(): void
    {
        $admin = $this->makeUser(RoleEnum::ADMIN);
        $u1 = $this->makeUser(RoleEnum::USER, ['name' => 'Alice', 'email' => 'alice@example.com']);
        $u2 = $this->makeUser(RoleEnum::ORGANIZER, ['name' => 'Bob', 'email' => 'bob@example.com', 'enabled' => false]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/admin/users?per_page=10&q=ali')
            ->assertOk()
            ->assertJsonFragment(['email' => 'alice@example.com'])
            ->assertJsonMissing(['email' => 'bob@example.com']);

        $this->getJson('/api/admin/users?enabled=0')
            ->assertOk()
            ->assertJsonFragment(['email' => 'bob@example.com']);
    }

    public function test_admin_can_enable_and_disable_a_user(): void
    {
        $admin = $this->makeUser(RoleEnum::ADMIN);
        $user = $this->makeUser(RoleEnum::USER);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/users/{$user->id}/enabled", ['enabled' => false])
            ->assertOk()
            ->assertJsonFragment(['enabled' => false]);

        $this->patchJson("/api/admin/users/{$user->id}/enabled", ['enabled' => true])
            ->assertOk()
            ->assertJsonFragment(['enabled' => true]);
    }

    public function test_admin_cannot_disable_self(): void
    {
        $admin = $this->makeUser(RoleEnum::ADMIN);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/users/{$admin->id}/enabled", ['enabled' => false])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['enabled']]);
    }

    public function test_admin_cannot_disable_last_enabled_admin(): void
    {
        $admin = $this->makeUser(RoleEnum::ADMIN);
        $otherAdmin = $this->makeUser(RoleEnum::ADMIN, ['enabled' => false]);

        Sanctum::actingAs($admin);

        $this->patchJson("/api/admin/users/{$admin->id}/enabled", ['enabled' => false])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['enabled']]);
    }
}
