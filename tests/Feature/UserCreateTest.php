<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use App\Notifications\UserCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class UserCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_user_and_reset_link_is_sent(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Jean Dupont',
                'email' => 'jean@example.com',
                'is_admin' => false,
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $created = User::where('email', 'jean@example.com')->firstOrFail();

        Notification::assertSentTo($created, UserCreated::class);
    }

    public function test_admin_creates_user_attached_to_departments_with_head_flag(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $member = Department::factory()->create();
        $managed = Department::factory()->create();

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Jean Dupont',
                'email' => 'jean@example.com',
                'is_admin' => false,
                'departments' => [
                    ['id' => $member->id, 'is_head' => false],
                    ['id' => $managed->id, 'is_head' => true],
                ],
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $created = User::where('email', 'jean@example.com')->firstOrFail();

        $this->assertDatabaseHas('department_user', [
            'user_id' => $created->id,
            'department_id' => $member->id,
            'is_head' => false,
        ]);
        $this->assertDatabaseHas('department_user', [
            'user_id' => $created->id,
            'department_id' => $managed->id,
            'is_head' => true,
        ]);
    }

    public function test_create_user_rejects_unknown_department(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('users.store'), [
                'name' => 'Jean Dupont',
                'email' => 'jean@example.com',
                'departments' => [
                    ['id' => 999999, 'is_head' => false],
                ],
            ])
            ->assertSessionHasErrors('departments.0.id');

        $this->assertDatabaseMissing('users', ['email' => 'jean@example.com']);
    }

    public function test_non_admin_cannot_create_user(): void
    {
        Notification::fake();

        $viewer = User::factory()->create(['is_admin' => false]);

        $this->actingAs($viewer)
            ->post(route('users.store'), [
                'name' => 'Jean Dupont',
                'email' => 'jean@example.com',
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('users', ['email' => 'jean@example.com']);
        Notification::assertNothingSent();
    }
}
