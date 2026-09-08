<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExpenseSheet;
use App\Models\Form;
use App\Models\FormCost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseSheetCostSummaryTest extends TestCase
{
    use RefreshDatabase;

    private function makeSheet(Department $department, User $user, Form $form): ExpenseSheet
    {
        return ExpenseSheet::create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'status' => 'En attente',
            'total' => 0,
            'form_id' => $form->id,
            'department_id' => $department->id,
            'is_draft' => false,
        ]);
    }

    private function makeFormCost(Form $form, string $name, string $type): FormCost
    {
        return FormCost::factory()->create([
            'form_id' => $form->id,
            'name' => $name,
            'type' => $type,
        ]);
    }

    public function test_show_exposes_the_total_and_the_breakdown_by_cost_group(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['is_admin' => false]);
        $department->users()->attach($user->id);

        $form = Form::factory()->create();
        $car = $this->makeFormCost($form, 'Déplacement en voiture', 'km');
        $parking = $this->makeFormCost($form, 'Parking', 'percentage');

        $expenseSheet = $this->makeSheet($department, $user, $form);

        $expenseSheet->costs()->create([
            'form_cost_id' => $car->id,
            'type' => 'km',
            'distance' => 30,
            'total' => 12.50,
            'date' => '2026-05-01',
        ]);
        $expenseSheet->costs()->create([
            'form_cost_id' => $car->id,
            'type' => 'km',
            'distance' => 20,
            'total' => 7.50,
            'date' => '2026-05-02',
        ]);
        $expenseSheet->costs()->create([
            'form_cost_id' => $parking->id,
            'type' => 'percentage',
            'total' => 4.20,
            'amount' => 4.20,
            'date' => '2026-05-02',
        ]);

        $expenseSheet->update(['total' => 24.20]);

        $this->actingAs($user)
            ->get("/expense-sheet/{$expenseSheet->id}")
            ->assertInertia(fn ($page) => $page
                ->where('costSummary.total', 24.2)
                ->where('costSummary.groups.0.name', 'Déplacement en voiture')
                ->where('costSummary.groups.0.total', 20)
                ->where('costSummary.groups.0.count', 2)
                ->where('costSummary.groups.0.distance', 50)
                ->where('costSummary.groups.1.name', 'Parking')
                ->where('costSummary.groups.1.total', 4.2)
                ->where('costSummary.groups.1.count', 1)
                ->where('costSummary.groups.1.distance', null)
                ->where('costSummary.period.start', '2026-05-01')
                ->where('costSummary.period.end', '2026-05-02')
            );
    }

    public function test_the_period_covers_the_first_and_last_cost_dates(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['is_admin' => false]);
        $department->users()->attach($user->id);

        $form = Form::factory()->create();
        $car = $this->makeFormCost($form, 'Déplacement en voiture', 'km');

        $expenseSheet = $this->makeSheet($department, $user, $form);

        foreach (['2026-05-14', '2026-05-02', '2026-05-28'] as $date) {
            $expenseSheet->costs()->create([
                'form_cost_id' => $car->id,
                'type' => 'km',
                'distance' => 10,
                'total' => 4.20,
                'date' => $date,
            ]);
        }

        $summary = $expenseSheet->load('costs.formCost')->costSummary();

        $this->assertSame(['start' => '2026-05-02', 'end' => '2026-05-28'], $summary['period']);
    }

    public function test_a_single_day_sheet_reports_the_same_start_and_end(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['is_admin' => false]);
        $department->users()->attach($user->id);

        $form = Form::factory()->create();
        $car = $this->makeFormCost($form, 'Déplacement en voiture', 'km');

        $expenseSheet = $this->makeSheet($department, $user, $form);

        $expenseSheet->costs()->create([
            'form_cost_id' => $car->id,
            'type' => 'km',
            'distance' => 10,
            'total' => 4.20,
            'date' => '2026-05-02',
        ]);

        $summary = $expenseSheet->load('costs.formCost')->costSummary();

        $this->assertSame(['start' => '2026-05-02', 'end' => '2026-05-02'], $summary['period']);
    }

    public function test_groups_are_sorted_by_descending_amount(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['is_admin' => false]);
        $department->users()->attach($user->id);

        $form = Form::factory()->create();
        $bike = $this->makeFormCost($form, 'Déplacement en vélo', 'km');
        $car = $this->makeFormCost($form, 'Déplacement en voiture', 'km');

        $expenseSheet = $this->makeSheet($department, $user, $form);

        $expenseSheet->costs()->create([
            'form_cost_id' => $bike->id,
            'type' => 'km',
            'distance' => 10,
            'total' => 2.90,
            'date' => '2026-05-01',
        ]);
        $expenseSheet->costs()->create([
            'form_cost_id' => $car->id,
            'type' => 'km',
            'distance' => 100,
            'total' => 42.00,
            'date' => '2026-05-01',
        ]);

        $summary = $expenseSheet->load('costs.formCost')->costSummary();

        $this->assertSame(['Déplacement en voiture', 'Déplacement en vélo'], array_column($summary['groups'], 'name'));
        $this->assertSame(44.9, $summary['total']);
    }

    public function test_a_sheet_without_cost_is_summarised_as_empty(): void
    {
        $department = Department::factory()->create();
        $user = User::factory()->create(['is_admin' => false]);
        $department->users()->attach($user->id);

        $expenseSheet = $this->makeSheet($department, $user, Form::factory()->create());

        $summary = $expenseSheet->load('costs.formCost')->costSummary();

        $this->assertSame([], $summary['groups']);
        $this->assertSame(0.0, $summary['total']);
        $this->assertNull($summary['period']);
    }
}
