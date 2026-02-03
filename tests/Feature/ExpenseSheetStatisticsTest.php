<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExpenseSheet;
use App\Models\ExpenseSheetCost;
use App\Models\Form;
use App\Models\FormCost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseSheetStatisticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_access_statistics(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/expense-sheet/statistics');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'eurosByCategory',
            'kmByCategory',
            'totalEuros',
            'totalKm',
            'selectedYear',
            'availableYears',
        ]);
    }

    public function test_statistics_defaults_to_current_year(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/expense-sheet/statistics');

        $response->assertStatus(200);
        $response->assertJson([
            'selectedYear' => now()->year,
        ]);
    }

    public function test_statistics_can_filter_by_year(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create();
        $department->users()->attach($user->id);

        $form = Form::factory()->create();
        $formCost = FormCost::factory()->create([
            'form_id' => $form->id,
            'name' => 'Frais de repas',
            'type' => 'fixed',
        ]);

        // Create expense sheet for 2024
        $expenseSheet2024 = ExpenseSheet::factory()->create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'approved' => true,
            'is_draft' => false,
            'total' => 100.00,
            'created_at' => '2024-06-15',
        ]);
        ExpenseSheetCost::factory()->create([
            'expense_sheet_id' => $expenseSheet2024->id,
            'form_cost_id' => $formCost->id,
            'type' => 'fixed',
            'total' => 100.00,
        ]);

        // Create expense sheet for current year
        $expenseSheetCurrentYear = ExpenseSheet::factory()->create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'approved' => true,
            'is_draft' => false,
            'total' => 200.00,
            'created_at' => now(),
        ]);
        ExpenseSheetCost::factory()->create([
            'expense_sheet_id' => $expenseSheetCurrentYear->id,
            'form_cost_id' => $formCost->id,
            'type' => 'fixed',
            'total' => 200.00,
        ]);

        // Filter by 2024
        $response2024 = $this->actingAs($user)->get('/expense-sheet/statistics?year=2024');
        $response2024->assertStatus(200);
        $response2024->assertJson([
            'totalEuros' => 100.00,
            'selectedYear' => 2024,
        ]);

        // Filter by current year
        $responseCurrentYear = $this->actingAs($user)->get('/expense-sheet/statistics?year=' . now()->year);
        $responseCurrentYear->assertStatus(200);
        $responseCurrentYear->assertJson([
            'totalEuros' => 200.00,
            'selectedYear' => now()->year,
        ]);
    }

    public function test_guest_cannot_access_statistics(): void
    {
        $response = $this->get('/expense-sheet/statistics');

        $response->assertRedirect('/login');
    }

    public function test_statistics_returns_correct_data_for_approved_expense_sheets(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create();
        $department->users()->attach($user->id);

        $form = Form::factory()->create();
        $formCostKm = FormCost::factory()->create([
            'form_id' => $form->id,
            'name' => 'Indemnité kilométrique',
            'type' => 'km',
        ]);
        $formCostFixed = FormCost::factory()->create([
            'form_id' => $form->id,
            'name' => 'Frais de repas',
            'type' => 'fixed',
        ]);

        // Create approved expense sheet
        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'approved' => true,
            'is_draft' => false,
            'total' => 150.00,
        ]);

        // Create costs
        ExpenseSheetCost::factory()->create([
            'expense_sheet_id' => $expenseSheet->id,
            'form_cost_id' => $formCostKm->id,
            'type' => 'km',
            'distance' => 100,
            'total' => 50.00,
        ]);
        ExpenseSheetCost::factory()->create([
            'expense_sheet_id' => $expenseSheet->id,
            'form_cost_id' => $formCostFixed->id,
            'type' => 'fixed',
            'total' => 100.00,
        ]);

        $response = $this->actingAs($user)->get('/expense-sheet/statistics');

        $response->assertStatus(200);
        $response->assertJson([
            'eurosByCategory' => [
                'Indemnité kilométrique' => 50.00,
                'Frais de repas' => 100.00,
            ],
            'kmByCategory' => [
                'Indemnité kilométrique' => 100,
            ],
            'totalEuros' => 150.00,
            'totalKm' => 100,
        ]);
    }

    public function test_statistics_excludes_non_approved_expense_sheets(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create();
        $department->users()->attach($user->id);

        $form = Form::factory()->create();
        $formCost = FormCost::factory()->create([
            'form_id' => $form->id,
            'name' => 'Frais de repas',
            'type' => 'fixed',
        ]);

        // Create pending expense sheet (not approved)
        $pendingSheet = ExpenseSheet::factory()->create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'approved' => null,
            'is_draft' => false,
            'total' => 50.00,
        ]);
        ExpenseSheetCost::factory()->create([
            'expense_sheet_id' => $pendingSheet->id,
            'form_cost_id' => $formCost->id,
            'type' => 'fixed',
            'total' => 50.00,
        ]);

        // Create rejected expense sheet
        $rejectedSheet = ExpenseSheet::factory()->create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'approved' => false,
            'is_draft' => false,
            'total' => 75.00,
        ]);
        ExpenseSheetCost::factory()->create([
            'expense_sheet_id' => $rejectedSheet->id,
            'form_cost_id' => $formCost->id,
            'type' => 'fixed',
            'total' => 75.00,
        ]);

        $response = $this->actingAs($user)->get('/expense-sheet/statistics');

        $response->assertStatus(200);
        $response->assertJson([
            'eurosByCategory' => [],
            'kmByCategory' => [],
            'totalEuros' => 0,
            'totalKm' => 0,
        ]);
    }

    public function test_statistics_only_shows_own_user_data(): void
    {
        $department = Department::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $department->users()->attach([$user1->id, $user2->id]);

        $form = Form::factory()->create();
        $formCost = FormCost::factory()->create([
            'form_id' => $form->id,
            'name' => 'Frais de repas',
            'type' => 'fixed',
        ]);

        // Create expense sheet for user 2
        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $user2->id,
            'created_by' => $user2->id,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'approved' => true,
            'is_draft' => false,
            'total' => 100.00,
        ]);
        ExpenseSheetCost::factory()->create([
            'expense_sheet_id' => $expenseSheet->id,
            'form_cost_id' => $formCost->id,
            'type' => 'fixed',
            'total' => 100.00,
        ]);

        // User 1 should not see user 2's data
        $response = $this->actingAs($user1)->get('/expense-sheet/statistics');

        $response->assertStatus(200);
        $response->assertJson([
            'eurosByCategory' => [],
            'kmByCategory' => [],
            'totalEuros' => 0,
            'totalKm' => 0,
        ]);
    }
}
