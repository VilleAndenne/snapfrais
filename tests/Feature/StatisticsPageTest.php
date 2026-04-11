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

class StatisticsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_statistics_page(): void
    {
        $response = $this->get('/admin/statistics');

        $response->assertRedirect('/login');
    }

    public function test_non_admin_user_cannot_access_statistics_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user)->get('/admin/statistics');

        $response->assertStatus(403);
    }

    public function test_admin_can_access_statistics_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin)->get('/admin/statistics');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('admin/Statistics/Index')
            ->has('kpis')
            ->has('monthlyStats', 12)
            ->has('availableYears')
            ->has('byDepartment')
            ->has('byForm')
            ->has('topUsers')
        );
    }

    public function test_statistics_kpis_reflect_expense_sheets(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $department = Department::factory()->create();
        $form = Form::factory()->create();
        $user = User::factory()->create();

        ExpenseSheet::factory()->create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'approved' => true,
            'is_draft' => false,
            'total' => 200.00,
            'created_at' => now(),
        ]);

        ExpenseSheet::factory()->create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'approved' => true,
            'is_draft' => false,
            'total' => 100.00,
            'created_at' => now(),
        ]);

        ExpenseSheet::factory()->create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'approved' => false,
            'is_draft' => false,
            'total' => 50.00,
            'created_at' => now(),
        ]);

        ExpenseSheet::factory()->create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'approved' => null,
            'is_draft' => false,
            'total' => 75.00,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->get('/admin/statistics');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('admin/Statistics/Index')
            ->where('kpis.totalSheets', 4)
            ->where('kpis.approvedSheets', 2)
            ->where('kpis.refusedSheets', 1)
            ->where('kpis.pendingSheets', 1)
            ->where('kpis.totalApprovedAmount', 300)
            ->where('kpis.averageApprovedAmount', 150)
            ->where('kpis.approvalRate', 50)
        );
    }

    public function test_statistics_km_are_grouped_by_transport(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $department = Department::factory()->create();
        $form = Form::factory()->create();
        $formCost = FormCost::factory()->create(['form_id' => $form->id]);
        $user = User::factory()->create();

        $approvedSheet = ExpenseSheet::factory()->create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'approved' => true,
            'is_draft' => false,
            'total' => 120.00,
            'created_at' => now(),
        ]);

        ExpenseSheetCost::factory()->create([
            'expense_sheet_id' => $approvedSheet->id,
            'form_cost_id' => $formCost->id,
            'type' => 'km',
            'distance' => 80,
            'total' => 30.00,
            'requirements' => ['transport' => 'car'],
        ]);

        ExpenseSheetCost::factory()->create([
            'expense_sheet_id' => $approvedSheet->id,
            'form_cost_id' => $formCost->id,
            'type' => 'km',
            'distance' => 15,
            'total' => 5.00,
            'requirements' => ['transport' => 'bike'],
        ]);

        $rejectedSheet = ExpenseSheet::factory()->create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'approved' => false,
            'is_draft' => false,
            'total' => 50.00,
            'created_at' => now(),
        ]);

        ExpenseSheetCost::factory()->create([
            'expense_sheet_id' => $rejectedSheet->id,
            'form_cost_id' => $formCost->id,
            'type' => 'km',
            'distance' => 999,
            'total' => 100.00,
            'requirements' => ['transport' => 'car'],
        ]);

        $response = $this->actingAs($admin)->get('/admin/statistics');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('kpis.totalApprovedKm', 95)
            ->where('kpis.kmByTransport.car', 80)
            ->where('kpis.kmByTransport.bike', 15)
        );
    }

    public function test_statistics_filter_by_year(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $department = Department::factory()->create();
        $form = Form::factory()->create();
        $user = User::factory()->create();

        ExpenseSheet::factory()->create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'approved' => true,
            'is_draft' => false,
            'total' => 500.00,
            'created_at' => '2024-06-15',
        ]);

        ExpenseSheet::factory()->create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'approved' => true,
            'is_draft' => false,
            'total' => 250.00,
            'created_at' => '2025-03-10',
        ]);

        $response = $this->actingAs($admin)->get('/admin/statistics?year=2024');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->where('selectedYear', 2024)
            ->where('kpis.totalApprovedAmount', 500)
            ->where('kpis.approvedSheets', 1)
        );
    }
}
