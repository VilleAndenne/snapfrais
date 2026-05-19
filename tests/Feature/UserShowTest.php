<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\AdminInitiatedPasswordReset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $user = User::factory()->create();

        $this->get(route('users.show', $user->id))
            ->assertRedirect('/login');
    }

    public function test_non_admin_cannot_view_user(): void
    {
        $viewer = User::factory()->create(['is_admin' => false]);
        $target = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('users.show', $target->id))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');
    }

    public function test_admin_can_view_user(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->get(route('users.show', $target->id))
            ->assertStatus(200)
            ->assertInertia(fn ($page) => $page
                ->component('users/Show')
                ->where('user.id', $target->id)
                ->has('stats')
                ->has('expenseSheets')
            );
    }

    public function test_show_returns_404_when_user_does_not_exist(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('users.show', 999999))
            ->assertNotFound();
    }

    public function test_admin_can_send_password_reset_link(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true, 'name' => 'Alice Admin']);
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('users.send-password-reset', $target->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        Notification::assertSentTo($target, AdminInitiatedPasswordReset::class);
    }

    public function test_non_admin_cannot_send_password_reset_link(): void
    {
        Notification::fake();

        $viewer = User::factory()->create(['is_admin' => false]);
        $target = User::factory()->create();

        $this->actingAs($viewer)
            ->post(route('users.send-password-reset', $target->id))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');

        Notification::assertNothingSent();
    }
}
