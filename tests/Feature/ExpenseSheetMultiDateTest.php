<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExpenseSheet;
use App\Models\ExpenseSheetCost;
use App\Models\Form;
use App\Models\FormCost;
use App\Models\FormCostRemboursiementRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseSheetMultiDateTest extends TestCase
{
    use RefreshDatabase;

    private function bootstrap(): array
    {
        Notification::fake();
        Storage::fake('public');

        $department = Department::factory()->create();
        $user = User::factory()->create(['is_admin' => false]);
        $department->users()->attach($user->id);

        $form = Form::factory()->create();
        $formCost = new FormCost;
        $formCost->name = 'Repas';
        $formCost->description = 'Indemnité repas sans prérequis';
        $formCost->type = 'fixed';
        $formCost->form_id = $form->id;
        $formCost->save();

        $rate = new FormCostRemboursiementRate;
        $rate->form_cost_id = $formCost->id;
        $rate->start_date = '2026-01-01';
        $rate->end_date = null;
        $rate->value = 25;
        $rate->save();

        return compact('department', 'user', 'form', 'formCost');
    }

    public function test_multi_date_submission_persists_one_cost_per_date(): void
    {
        $context = $this->bootstrap();

        $dates = ['2026-05-01', '2026-05-08', '2026-05-15'];

        $payload = [
            'department_id' => $context['department']->id,
            'is_draft' => 0,
            'costs' => array_map(fn (string $date): array => [
                'cost_id' => $context['formCost']->id,
                'data' => ['amount' => 25],
                'date' => $date,
            ], $dates),
        ];

        $response = $this->actingAs($context['user'])
            ->post("/expense-sheet/{$context['form']->id}", $payload);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('expense_sheets', 1);
        $this->assertSame(3, ExpenseSheetCost::count());

        foreach ($dates as $date) {
            $this->assertDatabaseHas('expense_sheet_costs', [
                'form_cost_id' => $context['formCost']->id,
                'date' => $date,
                'total' => 25,
            ]);
        }
    }

    public function test_multi_date_update_replaces_with_one_cost_per_date(): void
    {
        $context = $this->bootstrap();
        $admin = User::factory()->create(['is_admin' => true]);

        $expenseSheet = ExpenseSheet::create([
            'user_id' => $context['user']->id,
            'created_by' => $context['user']->id,
            'status' => 'En attente',
            'total' => 25,
            'form_id' => $context['form']->id,
            'department_id' => $context['department']->id,
            'is_draft' => false,
        ]);
        $expenseSheet->costs()->create([
            'form_cost_id' => $context['formCost']->id,
            'type' => 'fixed',
            'total' => 25,
            'date' => '2026-04-01',
            'requirements' => json_encode([]),
        ]);

        $dates = ['2026-06-01', '2026-06-08', '2026-06-15'];

        $payload = [
            '_method' => 'PUT',
            'department_id' => $context['department']->id,
            'costs' => array_map(fn (string $date): array => [
                'cost_id' => $context['formCost']->id,
                'data' => ['amount' => 25],
                'date' => $date,
            ], $dates),
        ];

        $response = $this->actingAs($admin)
            ->put("/expense-sheet/{$expenseSheet->id}", $payload);

        $response->assertSessionHasNoErrors();
        $this->assertSame(3, ExpenseSheetCost::count());

        foreach ($dates as $date) {
            $this->assertDatabaseHas('expense_sheet_costs', [
                'expense_sheet_id' => $expenseSheet->id,
                'form_cost_id' => $context['formCost']->id,
                'date' => $date,
                'total' => 25,
            ]);
        }
    }
}
