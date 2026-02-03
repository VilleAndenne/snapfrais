<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mirror\Facades\Mirror;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_impersonate_regular_user(): void
    {
        $superAdmin = User::factory()->create(['super_admin' => true]);
        $regularUser = User::factory()->create(['super_admin' => false]);

        $this->actingAs($superAdmin)
            ->post(route('impersonate.start', $regularUser))
            ->assertRedirect(route('dashboard'));

        $this->assertTrue(Mirror::isImpersonating());
        $this->assertEquals($regularUser->id, auth()->id());
    }

    public function test_super_admin_cannot_impersonate_another_super_admin(): void
    {
        $superAdmin1 = User::factory()->create(['super_admin' => true]);
        $superAdmin2 = User::factory()->create(['super_admin' => true]);

        $this->actingAs($superAdmin1)
            ->post(route('impersonate.start', $superAdmin2))
            ->assertStatus(403);
    }

    public function test_regular_user_cannot_impersonate(): void
    {
        $regularUser1 = User::factory()->create(['super_admin' => false]);
        $regularUser2 = User::factory()->create(['super_admin' => false]);

        $this->actingAs($regularUser1)
            ->post(route('impersonate.start', $regularUser2))
            ->assertStatus(403);
    }

    public function test_mirror_can_start_and_stop_impersonating(): void
    {
        $superAdmin = User::factory()->create(['super_admin' => true]);
        $regularUser = User::factory()->create(['super_admin' => false]);

        $this->actingAs($superAdmin);

        Mirror::start($regularUser);
        $this->assertTrue(Mirror::isImpersonating());
        $this->assertEquals($regularUser->id, auth()->id());

        Mirror::stop();
        $this->assertFalse(Mirror::isImpersonating());
        $this->assertEquals($superAdmin->id, auth()->id());
    }

    public function test_super_admin_can_impersonate_returns_true(): void
    {
        $superAdmin = User::factory()->create(['super_admin' => true]);

        $this->assertTrue($superAdmin->canImpersonate());
    }

    public function test_regular_user_can_impersonate_returns_false(): void
    {
        $regularUser = User::factory()->create(['super_admin' => false]);

        $this->assertFalse($regularUser->canImpersonate());
    }

    public function test_super_admin_can_be_impersonated_returns_false(): void
    {
        $superAdmin = User::factory()->create(['super_admin' => true]);

        $this->assertFalse($superAdmin->canBeImpersonated());
    }

    public function test_regular_user_can_be_impersonated_returns_true(): void
    {
        $regularUser = User::factory()->create(['super_admin' => false]);

        $this->assertTrue($regularUser->canBeImpersonated());
    }
}
