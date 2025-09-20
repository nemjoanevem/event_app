<?php

namespace Tests\Feature;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        $this->actingAs($user, 'web')
            ->get('/admin/users')
            ->assertStatus(403);

        $target = $this->makeUser(RoleEnum::USER);
        $this->actingAs($user, 'web')
            ->patch("/admin/users/{$target->id}/enabled", ['enabled' => false])
            ->assertStatus(403);
    }

    public function test_admin_can_list_users_with_pagination_and_filters(): void
    {
        $admin = $this->makeUser(RoleEnum::ADMIN);
        $u1 = $this->makeUser(RoleEnum::USER, ['name' => 'Alice', 'email' => 'alice@example.com']);
        $u2 = $this->makeUser(RoleEnum::ORGANIZER, ['name' => 'Bob', 'email' => 'bob@example.com', 'enabled' => false]);

        $this->actingAs($admin, 'web')
            ->get('/admin/users?per_page=10&q=ali')
            ->assertOk()
            ->assertJsonFragment(['email' => 'alice@example.com'])
            ->assertJsonMissing(['email' => 'bob@example.com']);

        $this->actingAs($admin, 'web')
            ->get('/admin/users?enabled=0')
            ->assertOk()
            ->assertJsonFragment(['email' => 'bob@example.com']);
    }

    public function test_admin_can_enable_and_disable_a_user(): void
    {
        $admin = $this->makeUser(RoleEnum::ADMIN);
        $user = $this->makeUser(RoleEnum::USER);

        // Disable
        $this->actingAs($admin, 'web')
            ->patch("/admin/users/{$user->id}/enabled", ['enabled' => false])
            ->assertOk()
            ->assertJsonFragment(['enabled' => false]);

        // Enable back
        $this->actingAs($admin, 'web')
            ->patch("/admin/users/{$user->id}/enabled", ['enabled' => true])
            ->assertOk()
            ->assertJsonFragment(['enabled' => true]);
    }

    public function test_admin_cannot_disable_self(): void
    {
        $admin = $this->makeUser(RoleEnum::ADMIN);

        $this->actingAs($admin, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->patch("/admin/users/{$admin->id}/enabled", ['enabled' => false])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['enabled']]);
    }

    public function test_admin_cannot_disable_last_enabled_admin(): void
    {
        $admin = $this->makeUser(RoleEnum::ADMIN);
        $otherAdmin = $this->makeUser(RoleEnum::ADMIN, ['enabled' => false]);

        // Only one enabled admin remains -> disabling should fail
        $this->actingAs($admin, 'web')
            ->withHeaders(['Accept' => 'application/json'])
            ->patch("/admin/users/{$admin->id}/enabled", ['enabled' => false])
            ->assertStatus(422)
            ->assertJsonStructure(['errors' => ['enabled']]);
    }
}
