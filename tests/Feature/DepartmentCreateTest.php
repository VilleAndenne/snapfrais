<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_department_with_members_and_head(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $member = User::factory()->create();
        $head = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('departments.store'), [
                'name' => 'Service Informatique',
                'parent_id' => null,
                'users' => [
                    ['id' => $member->id, 'is_head' => false],
                    ['id' => $head->id, 'is_head' => true],
                ],
            ])
            ->assertRedirect(route('departments.index'));

        $department = Department::where('name', 'Service Informatique')->firstOrFail();

        $this->assertDatabaseHas('department_user', [
            'department_id' => $department->id,
            'user_id' => $member->id,
            'is_head' => false,
        ]);
        $this->assertDatabaseHas('department_user', [
            'department_id' => $department->id,
            'user_id' => $head->id,
            'is_head' => true,
        ]);
    }

    public function test_create_department_requires_name(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->post(route('departments.store'), [
                'name' => '',
                'users' => [],
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_non_admin_cannot_create_department(): void
    {
        $viewer = User::factory()->create(['is_admin' => false]);

        $this->actingAs($viewer)
            ->post(route('departments.store'), [
                'name' => 'Service Informatique',
                'users' => [],
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('departments', ['name' => 'Service Informatique']);
    }
}
