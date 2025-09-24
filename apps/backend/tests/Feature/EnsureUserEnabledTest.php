<?php

namespace Tests\Feature\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Laravel\Sanctum\Sanctum;

class EnsureUserEnabledTest extends TestCase
{
    use RefreshDatabase;

    public function test_disabled_user_gets_423_on_protected_endpoints()
    {
        $user = User::factory()->create([
            'enabled' => 0,
        ]);

        $this->actingAs($user);

        $resp = $this->getJson('/api/bookings');
        $resp->assertStatus(423)
             ->assertJson([
                 'message' => trans('errors.423'),
             ]);
    }

    public function test_disabled_user_can_access_allowlisted_endpoints_like_user_and_logout()
    {
        $user = User::factory()->create([
            'enabled' => 0,
        ]);

        $this->actingAs($user);

        $this->getJson('/api/user')->assertOk();

        $this->postJson('/api/logout')->assertOk();
    }

    public function test_enabled_user_can_access_protected_endpoints_normally()
    {
        $user = User::factory()->create([
            'enabled' => 1,
        ]);

        $this->actingAs($user);

        $resp = $this->getJson('/api/bookings');
        $resp->assertOk();
    }
}
