<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExpenseSheet;
use App\Models\ExpenseSheetCost;
use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_creation_is_logged(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'user',
            'description' => 'Utilisateur créé',
            'subject_type' => User::class,
            'subject_id' => $user->id,
        ]);

        $activity = Activity::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals('created', $activity->event);
    }

    public function test_user_update_is_logged(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'is_admin' => false,
        ]);

        $user->update([
            'name' => 'Jane Doe',
            'is_admin' => true,
        ]);

        $activity = Activity::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->where('event', 'updated')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals('Utilisateur modifié', $activity->description);
        $this->assertEquals('John Doe', $activity->properties['old']['name']);
        $this->assertEquals('Jane Doe', $activity->properties['attributes']['name']);
        $this->assertEquals(false, $activity->properties['old']['is_admin']);
        $this->assertEquals(true, $activity->properties['attributes']['is_admin']);
    }

    public function test_user_deletion_is_logged(): void
    {
        $user = User::factory()->create();

        $userId = $user->id;
        $user->delete();

        $activity = Activity::where('subject_type', User::class)
            ->where('subject_id', $userId)
            ->where('event', 'deleted')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals('Utilisateur supprimé', $activity->description);
    }

    public function test_expense_sheet_creation_is_logged(): void
    {
        $expenseSheet = ExpenseSheet::factory()->create();

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'expense_sheet',
            'description' => 'Feuille de frais créée',
            'subject_type' => ExpenseSheet::class,
            'subject_id' => $expenseSheet->id,
        ]);
    }

    public function test_expense_sheet_update_is_logged(): void
    {
        $user = User::factory()->create();
        $expenseSheet = ExpenseSheet::factory()->create([
            'status' => 'En attente',
        ]);

        $expenseSheet->update([
            'status' => 'Approuvé',
            'validated_by' => $user->id,
            'validated_at' => now(),
        ]);

        $activity = Activity::where('subject_type', ExpenseSheet::class)
            ->where('subject_id', $expenseSheet->id)
            ->where('event', 'updated')
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals('Feuille de frais modifiée', $activity->description);
        $this->assertEquals('En attente', $activity->properties['old']['status']);
        $this->assertEquals('Approuvé', $activity->properties['attributes']['status']);
    }

    public function test_expense_sheet_cost_creation_is_logged(): void
    {
        $cost = ExpenseSheetCost::factory()->create();

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'expense_sheet_cost',
            'description' => 'Coût de feuille de frais créé',
            'subject_type' => ExpenseSheetCost::class,
            'subject_id' => $cost->id,
        ]);
    }

    public function test_form_creation_is_logged(): void
    {
        $form = Form::create([
            'name' => 'Test Form',
            'description' => 'Test Description',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'form',
            'description' => 'Formulaire créé',
            'subject_type' => Form::class,
            'subject_id' => $form->id,
        ]);
    }

    public function test_department_creation_is_logged(): void
    {
        $department = Department::create([
            'name' => 'IT Department',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'log_name' => 'department',
            'description' => 'Département créé',
            'subject_type' => Department::class,
            'subject_id' => $department->id,
        ]);
    }

    public function test_only_dirty_attributes_are_logged(): void
    {
        $user = User::factory()->create([
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        // Clear previous activities
        Activity::query()->delete();

        // Update without changing anything
        $user->update([
            'name' => 'John Doe',
        ]);

        // No activity should be logged since nothing changed
        $count = Activity::where('subject_type', User::class)
            ->where('subject_id', $user->id)
            ->count();

        $this->assertEquals(0, $count);
    }

    public function test_authenticated_user_is_logged_as_causer(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $form = Form::create([
            'name' => 'Test Form',
            'description' => 'Test Description',
        ]);

        $activity = Activity::where('subject_type', Form::class)
            ->where('subject_id', $form->id)
            ->first();

        $this->assertNotNull($activity);
        $this->assertEquals($admin->id, $activity->causer_id);
        $this->assertEquals(User::class, $activity->causer_type);
    }
}
