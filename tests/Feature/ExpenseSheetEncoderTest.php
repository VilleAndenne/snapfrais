<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExpenseSheet;
use App\Models\Form;
use App\Models\FormCost;
use App\Models\FormCostRemboursiementRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExpenseSheetEncoderTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{department: Department, head: User, encoder: User, agent: User, member: User, form: Form, formCost: FormCost}
     */
    private function bootstrap(): array
    {
        Notification::fake();
        Storage::fake('public');

        $department = Department::factory()->create();

        $head = User::factory()->create(['is_admin' => false]);
        $department->users()->attach($head->id, ['is_head' => true]);

        $encoder = User::factory()->create(['is_admin' => false]);
        $department->users()->attach($encoder->id, ['is_encoder' => true]);

        $agent = User::factory()->create(['is_admin' => false]);
        $department->users()->attach($agent->id);

        $member = User::factory()->create(['is_admin' => false]);
        $department->users()->attach($member->id);

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

        return compact('department', 'head', 'encoder', 'agent', 'member', 'form', 'formCost');
    }

    /**
     * @param  array{department: Department, form: Form, formCost: FormCost}  $context
     * @return array<string, mixed>
     */
    private function payloadFor(array $context, User $target): array
    {
        return [
            'department_id' => $context['department']->id,
            'target_user_id' => $target->id,
            'is_draft' => 0,
            'costs' => [[
                'cost_id' => $context['formCost']->id,
                'data' => ['amount' => 25],
                'date' => '2026-05-01',
            ]],
        ];
    }

    /**
     * @param  array{formCost: FormCost}  $context
     * @return array<string, mixed>
     */
    private function updatePayloadFor(array $context, Department $department): array
    {
        return [
            'department_id' => $department->id,
            'is_draft' => 0,
            'costs' => [[
                'cost_id' => $context['formCost']->id,
                'data' => ['amount' => 25],
                'date' => '2026-05-01',
            ]],
        ];
    }

    public function test_encoder_can_create_an_expense_sheet_for_another_agent(): void
    {
        $context = $this->bootstrap();

        $response = $this->actingAs($context['encoder'])
            ->post("/expense-sheet/{$context['form']->id}", $this->payloadFor($context, $context['agent']));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('expense_sheets', [
            'user_id' => $context['agent']->id,
            'created_by' => $context['encoder']->id,
            'department_id' => $context['department']->id,
        ]);
    }

    public function test_head_can_still_create_an_expense_sheet_for_another_agent(): void
    {
        $context = $this->bootstrap();

        $response = $this->actingAs($context['head'])
            ->post("/expense-sheet/{$context['form']->id}", $this->payloadFor($context, $context['agent']));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('expense_sheets', [
            'user_id' => $context['agent']->id,
            'created_by' => $context['head']->id,
        ]);
    }

    public function test_simple_member_cannot_create_an_expense_sheet_for_another_agent(): void
    {
        $context = $this->bootstrap();

        $response = $this->actingAs($context['member'])
            ->post("/expense-sheet/{$context['form']->id}", $this->payloadFor($context, $context['agent']));

        $response->assertForbidden();
        $this->assertDatabaseCount('expense_sheets', 0);
    }

    public function test_encoder_cannot_encode_for_an_agent_outside_the_department(): void
    {
        $context = $this->bootstrap();
        $outsider = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($context['encoder'])
            ->post("/expense-sheet/{$context['form']->id}", $this->payloadFor($context, $outsider));

        $response->assertSessionHasErrors('target_user_id');
        $this->assertDatabaseCount('expense_sheets', 0);
    }

    public function test_encoder_cannot_approve_the_expense_sheet_he_encoded(): void
    {
        $context = $this->bootstrap();

        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $context['agent']->id,
            'created_by' => $context['encoder']->id,
            'is_draft' => false,
            'department_id' => $context['department']->id,
            'form_id' => $context['form']->id,
            'approved' => null,
        ]);

        $response = $this->actingAs($context['encoder'])
            ->post("/expense-sheet/{$expenseSheet->id}/approve", ['approval' => 1]);

        $response->assertForbidden();
        $this->assertNull($expenseSheet->fresh()->approved);
    }

    public function test_head_approves_the_expense_sheet_encoded_by_an_encoder(): void
    {
        $context = $this->bootstrap();

        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $context['agent']->id,
            'created_by' => $context['encoder']->id,
            'is_draft' => false,
            'department_id' => $context['department']->id,
            'form_id' => $context['form']->id,
            'approved' => null,
        ]);

        $response = $this->actingAs($context['head'])
            ->post("/expense-sheet/{$expenseSheet->id}/approve", ['approval' => 1]);

        $response->assertRedirect("/expense-sheet/{$expenseSheet->id}");
        $this->assertTrue((bool) $expenseSheet->fresh()->approved);
    }

    public function test_encoder_cannot_move_the_draft_of_another_agent_to_a_department_he_cannot_encode_for(): void
    {
        $context = $this->bootstrap();
        $foreignDepartment = Department::factory()->create();

        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $context['agent']->id,
            'created_by' => $context['encoder']->id,
            'is_draft' => true,
            'department_id' => $context['department']->id,
            'form_id' => $context['form']->id,
        ]);

        $response = $this->actingAs($context['encoder'])
            ->put("/expense-sheet/{$expenseSheet->id}", $this->updatePayloadFor($context, $foreignDepartment));

        $response->assertForbidden();
        $this->assertSame($context['department']->id, $expenseSheet->fresh()->department_id);
    }

    public function test_encoder_cannot_move_the_draft_of_another_agent_to_a_department_the_agent_left(): void
    {
        $context = $this->bootstrap();
        $otherDepartment = Department::factory()->create();
        $otherDepartment->users()->attach($context['encoder']->id, ['is_encoder' => true]);

        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $context['agent']->id,
            'created_by' => $context['encoder']->id,
            'is_draft' => true,
            'department_id' => $context['department']->id,
            'form_id' => $context['form']->id,
        ]);

        $response = $this->actingAs($context['encoder'])
            ->put("/expense-sheet/{$expenseSheet->id}", $this->updatePayloadFor($context, $otherDepartment));

        $response->assertSessionHasErrors('target_user_id');
        $this->assertSame($context['department']->id, $expenseSheet->fresh()->department_id);
    }

    public function test_resubmitting_a_rejected_sheet_keeps_the_original_encoder(): void
    {
        $context = $this->bootstrap();

        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $context['agent']->id,
            'created_by' => $context['encoder']->id,
            'is_draft' => false,
            'approved' => 0,
            'department_id' => $context['department']->id,
            'form_id' => $context['form']->id,
        ]);

        $response = $this->actingAs($context['agent'])
            ->put("/expense-sheet/{$expenseSheet->id}", $this->updatePayloadFor($context, $context['department']));

        $response->assertSessionHasNoErrors();
        $this->assertSame($context['encoder']->id, $expenseSheet->fresh()->created_by);
    }

    public function test_a_former_encoder_cannot_duplicate_the_sheet_of_another_agent(): void
    {
        $context = $this->bootstrap();

        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $context['agent']->id,
            'created_by' => $context['encoder']->id,
            'is_draft' => false,
            'department_id' => $context['department']->id,
            'form_id' => $context['form']->id,
        ]);

        $context['department']->users()->updateExistingPivot($context['encoder']->id, ['is_encoder' => false]);

        $response = $this->actingAs($context['encoder'])
            ->post("/expense-sheet/{$expenseSheet->id}/duplicate");

        $response->assertForbidden();
        $this->assertDatabaseCount('expense_sheets', 1);
    }

    public function test_an_encoder_still_in_place_can_duplicate_the_sheet_of_another_agent(): void
    {
        $context = $this->bootstrap();

        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $context['agent']->id,
            'created_by' => $context['encoder']->id,
            'is_draft' => false,
            'department_id' => $context['department']->id,
            'form_id' => $context['form']->id,
        ]);

        $response = $this->actingAs($context['encoder'])
            ->post("/expense-sheet/{$expenseSheet->id}/duplicate");

        $response->assertRedirect();
        $this->assertDatabaseCount('expense_sheets', 2);
        $this->assertDatabaseHas('expense_sheets', [
            'user_id' => $context['agent']->id,
            'created_by' => $context['encoder']->id,
            'is_draft' => true,
        ]);
    }

    public function test_api_encoder_cannot_move_the_draft_of_another_agent_to_a_foreign_department(): void
    {
        $context = $this->bootstrap();
        $foreignDepartment = Department::factory()->create();

        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $context['agent']->id,
            'created_by' => $context['encoder']->id,
            'is_draft' => true,
            'department_id' => $context['department']->id,
            'form_id' => $context['form']->id,
        ]);

        Sanctum::actingAs($context['encoder']);

        $response = $this->putJson("/api/expense-sheets/{$expenseSheet->id}", [
            'department_id' => $foreignDepartment->id,
            'is_draft' => true,
            'costs' => [[
                'cost_id' => $context['formCost']->id,
                'data' => ['amount' => 25],
                'date' => '2026-05-01',
            ]],
        ]);

        $response->assertForbidden();
        $this->assertSame($context['department']->id, $expenseSheet->fresh()->department_id);
    }

    public function test_encoder_sees_the_expense_sheets_he_encoded(): void
    {
        $context = $this->bootstrap();

        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $context['agent']->id,
            'created_by' => $context['encoder']->id,
            'is_draft' => false,
            'department_id' => $context['department']->id,
            'form_id' => $context['form']->id,
        ]);

        $this->assertTrue($context['encoder']->can('view', $expenseSheet));
        $this->assertTrue(
            ExpenseSheet::visibleBy($context['encoder'])->whereKey($expenseSheet->id)->exists()
        );

        $this->assertFalse($context['member']->can('view', $expenseSheet));
    }
}
