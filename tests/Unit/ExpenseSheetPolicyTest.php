<?php

namespace Tests\Unit;

use App\Models\Department;
use App\Models\ExpenseSheet;
use App\Models\Form;
use App\Models\User;
use App\Policies\ExpenseSheetPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseSheetPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_can_edit_draft_created_for_another_user(): void
    {
        // Créer un département
        $department = Department::factory()->create();

        // Créer deux utilisateurs : un responsable (créateur) et un agent (bénéficiaire)
        $creator = User::factory()->create(['is_admin' => false]);
        $beneficiary = User::factory()->create(['is_admin' => false]);

        // Assigner les deux au département
        $department->users()->attach($beneficiary->id);
        // Le créateur est responsable (is_head = true)
        $department->users()->attach($creator->id, ['is_head' => true]);

        // Créer un formulaire
        $form = Form::factory()->create();

        // Créer un brouillon de note de frais pour le bénéficiaire, créé par le responsable
        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $beneficiary->id,      // Bénéficiaire
            'created_by' => $creator->id,        // Créateur
            'is_draft' => true,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'status' => 'Brouillon',
        ]);

        $policy = new ExpenseSheetPolicy();

        // Le créateur doit pouvoir modifier le brouillon qu'il a créé pour quelqu'un d'autre
        $this->assertTrue(
            $policy->edit($creator, $expenseSheet),
            'Le créateur devrait pouvoir modifier le brouillon qu\'il a créé pour un autre utilisateur'
        );
    }

    public function test_creator_cannot_edit_submitted_expense_sheet_created_for_another_user(): void
    {
        // Créer un département
        $department = Department::factory()->create();

        // Créer deux utilisateurs : un responsable (créateur) et un agent (bénéficiaire)
        $creator = User::factory()->create(['is_admin' => false]);
        $beneficiary = User::factory()->create(['is_admin' => false]);

        // Assigner les deux au département
        $department->users()->attach($beneficiary->id);
        // Le créateur est responsable (is_head = true)
        $department->users()->attach($creator->id, ['is_head' => true]);

        // Créer un formulaire
        $form = Form::factory()->create();

        // Créer une note de frais soumise (non brouillon)
        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $beneficiary->id,
            'created_by' => $creator->id,
            'is_draft' => false,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'status' => 'En attente',
            'approved' => null,
        ]);

        $policy = new ExpenseSheetPolicy();

        // Le créateur ne doit PAS pouvoir modifier une note soumise (non brouillon) créée pour quelqu'un d'autre
        $this->assertFalse(
            $policy->edit($creator, $expenseSheet),
            'Le créateur ne devrait pas pouvoir modifier une note soumise créée pour un autre utilisateur'
        );
    }

    public function test_owner_can_edit_own_draft(): void
    {
        // Créer un département
        $department = Department::factory()->create();

        // Créer un utilisateur
        $user = User::factory()->create(['is_admin' => false]);
        $department->users()->attach($user->id);

        // Créer un formulaire
        $form = Form::factory()->create();

        // Créer un brouillon personnel
        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'is_draft' => true,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'status' => 'Brouillon',
        ]);

        $policy = new ExpenseSheetPolicy();

        // Le propriétaire doit pouvoir modifier son propre brouillon
        $this->assertTrue(
            $policy->edit($user, $expenseSheet),
            'Le propriétaire devrait pouvoir modifier son propre brouillon'
        );
    }

    public function test_owner_can_edit_rejected_expense_sheet(): void
    {
        // Créer un département
        $department = Department::factory()->create();

        // Créer un utilisateur
        $user = User::factory()->create(['is_admin' => false]);
        $department->users()->attach($user->id);

        // Créer un formulaire
        $form = Form::factory()->create();

        // Créer une note de frais rejetée
        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'is_draft' => false,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'status' => 'Refusée',
            'approved' => 0,
        ]);

        $policy = new ExpenseSheetPolicy();

        // Le propriétaire doit pouvoir modifier sa note rejetée
        $this->assertTrue(
            $policy->edit($user, $expenseSheet),
            'Le propriétaire devrait pouvoir modifier sa note rejetée'
        );
    }

    public function test_admin_can_always_edit(): void
    {
        // Créer un département
        $department = Department::factory()->create();

        // Créer un admin et un utilisateur normal
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);
        $department->users()->attach([$admin->id, $user->id]);

        // Créer un formulaire
        $form = Form::factory()->create();

        // Créer une note de frais soumise par l'utilisateur
        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'is_draft' => false,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'status' => 'En attente',
            'approved' => null,
        ]);

        $policy = new ExpenseSheetPolicy();

        // L'admin doit toujours pouvoir modifier
        $this->assertTrue(
            $policy->edit($admin, $expenseSheet),
            'Un admin devrait toujours pouvoir modifier une note de frais'
        );
    }

    public function test_beneficiary_can_edit_own_draft_when_different_from_creator(): void
    {
        // Créer un département
        $department = Department::factory()->create();

        // Créer deux utilisateurs
        $creator = User::factory()->create(['is_admin' => false]);
        $beneficiary = User::factory()->create(['is_admin' => false]);

        // Assigner les deux au département
        $department->users()->attach($beneficiary->id);
        // Le créateur est responsable (is_head = true)
        $department->users()->attach($creator->id, ['is_head' => true]);

        // Créer un formulaire
        $form = Form::factory()->create();

        // Créer un brouillon de note de frais pour le bénéficiaire, créé par le responsable
        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $beneficiary->id,
            'created_by' => $creator->id,
            'is_draft' => true,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'status' => 'Brouillon',
        ]);

        $policy = new ExpenseSheetPolicy();

        // Le bénéficiaire doit également pouvoir modifier le brouillon créé pour lui
        $this->assertTrue(
            $policy->edit($beneficiary, $expenseSheet),
            'Le bénéficiaire devrait pouvoir modifier un brouillon créé pour lui'
        );
    }
}
