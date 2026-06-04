<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExpenseSheetCost;
use App\Models\Form;
use App\Models\FormCost;
use App\Models\FormCostRemboursiementRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseSheetTextRequirementTest extends TestCase
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
        $formCost->name = 'Frais avec prérequis texte';
        $formCost->description = 'Coût avec prérequis texte';
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

    public function test_text_requirement_value_is_persisted(): void
    {
        $context = $this->bootstrap();

        $payload = [
            'department_id' => $context['department']->id,
            'is_draft' => 0,
            'costs' => [
                [
                    'cost_id' => $context['formCost']->id,
                    'data' => ['paidAmount' => 25],
                    'date' => '2026-05-01',
                    'requirements' => [
                        'Numéro de note' => ['value' => 'NDF-2026-001'],
                    ],
                ],
            ],
        ];

        $response = $this->actingAs($context['user'])
            ->post("/expense-sheet/{$context['form']->id}", $payload);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('expense_sheets', 1);

        $cost = ExpenseSheetCost::firstOrFail();
        $requirements = is_string($cost->requirements)
            ? json_decode($cost->requirements, true)
            : $cost->requirements;

        $this->assertArrayHasKey('Numéro de note', $requirements);
        $this->assertSame('NDF-2026-001', $requirements['Numéro de note']['value']);
    }
}
