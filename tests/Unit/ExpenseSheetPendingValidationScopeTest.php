<?php

namespace Tests\Unit;

use App\Models\Department;
use App\Models\ExpenseSheet;
use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseSheetPendingValidationScopeTest extends TestCase
{
    use RefreshDatabase;

    public function test_head_gets_sheets_of_own_and_child_department_not_others(): void
    {
        $form = Form::factory()->create();

        // Département parent dont le head est le validateur, et un sous-département
        $parent = Department::factory()->create();
        $child = Department::factory()->create(['parent_id' => $parent->id]);

        $head = User::factory()->create();
        $parent->heads()->attach($head->id, ['is_head' => true]);

        // Département sans lien avec le validateur
        $other = Department::factory()->create();

        $employeeParent = User::factory()->create();
        $parent->users()->attach($employeeParent->id, ['is_head' => false]);

        $employeeChild = User::factory()->create();
        $child->users()->attach($employeeChild->id, ['is_head' => false]);

        $employeeOther = User::factory()->create();
        $other->users()->attach($employeeOther->id, ['is_head' => false]);

        // Note du département du head → candidate
        $ownSheet = ExpenseSheet::factory()->create([
            'user_id' => $employeeParent->id,
            'department_id' => $parent->id,
            'form_id' => $form->id,
        ]);

        // Note du sous-département (parent = département du head, N+1) → candidate
        $childSheet = ExpenseSheet::factory()->create([
            'user_id' => $employeeChild->id,
            'department_id' => $child->id,
            'form_id' => $form->id,
        ]);

        // Note d'un département tiers → non candidate
        ExpenseSheet::factory()->create([
            'user_id' => $employeeOther->id,
            'department_id' => $other->id,
            'form_id' => $form->id,
        ]);

        $candidateIds = ExpenseSheet::query()
            ->pendingValidationBy($head)
            ->pluck('id');

        $this->assertEqualsCanonicalizing(
            [$ownSheet->id, $childSheet->id],
            $candidateIds->all(),
            'Le head doit récupérer les notes de son département et du sous-département, pas celles des autres.'
        );
    }
}
