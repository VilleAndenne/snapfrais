<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DepartmentUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_updates_department_and_syncs_members(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $department = Department::factory()->create(['name' => 'Ancien Nom']);

        $kept = User::factory()->create();
        $removed = User::factory()->create();
        $added = User::factory()->create();

        $department->users()->attach([
            $kept->id => ['is_head' => false],
            $removed->id => ['is_head' => true],
        ]);

        $this->actingAs($admin)
            ->put(route('departments.update', $department->id), [
                'name' => 'Nouveau Nom',
                'parent_id' => null,
                'users' => [
                    ['id' => $kept->id, 'is_head' => true],
                    ['id' => $added->id, 'is_head' => false],
                ],
            ])
            ->assertRedirect(route('departments.index'));

        $department->refresh();
        $this->assertSame('Nouveau Nom', $department->name);

        $this->assertDatabaseHas('department_user', [
            'department_id' => $department->id,
            'user_id' => $kept->id,
            'is_head' => true,
        ]);
        $this->assertDatabaseHas('department_user', [
            'department_id' => $department->id,
            'user_id' => $added->id,
            'is_head' => false,
        ]);
        $this->assertDatabaseMissing('department_user', [
            'department_id' => $department->id,
            'user_id' => $removed->id,
        ]);
    }

    public function test_non_admin_cannot_update_department(): void
    {
        $viewer = User::factory()->create(['is_admin' => false]);
        $department = Department::factory()->create(['name' => 'Service']);

        $this->actingAs($viewer)
            ->put(route('departments.update', $department->id), [
                'name' => 'Modifié',
                'users' => [],
            ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('departments', ['id' => $department->id, 'name' => 'Service']);
    }
}
