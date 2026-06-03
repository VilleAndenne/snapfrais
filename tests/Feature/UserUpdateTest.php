<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_updates_user_and_syncs_departments(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create(['name' => 'Ancien Nom']);

        $kept = Department::factory()->create();
        $removed = Department::factory()->create();
        $added = Department::factory()->create();

        // État initial : membre de "kept" et "removed".
        $target->departments()->attach([
            $kept->id => ['is_head' => false],
            $removed->id => ['is_head' => false],
        ]);

        $this->actingAs($admin)
            ->put(route('users.update', $target->id), [
                'name' => 'Nouveau Nom',
                'email' => $target->email,
                'is_admin' => false,
                'departments' => [
                    ['id' => $kept->id, 'is_head' => true],
                    ['id' => $added->id, 'is_head' => false],
                ],
            ])
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $target->refresh();
        $this->assertSame('Nouveau Nom', $target->name);

        // "kept" devient responsable, "added" est ajouté, "removed" est détaché.
        $this->assertDatabaseHas('department_user', [
            'user_id' => $target->id,
            'department_id' => $kept->id,
            'is_head' => true,
        ]);
        $this->assertDatabaseHas('department_user', [
            'user_id' => $target->id,
            'department_id' => $added->id,
            'is_head' => false,
        ]);
        $this->assertDatabaseMissing('department_user', [
            'user_id' => $target->id,
            'department_id' => $removed->id,
        ]);
    }

    public function test_update_without_departments_detaches_all(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $target = User::factory()->create();
        $dept = Department::factory()->create();
        $target->departments()->attach($dept->id, ['is_head' => true]);

        $this->actingAs($admin)
            ->put(route('users.update', $target->id), [
                'name' => $target->name,
                'email' => $target->email,
                'is_admin' => false,
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('department_user', [
            'user_id' => $target->id,
            'department_id' => $dept->id,
        ]);
    }
}
