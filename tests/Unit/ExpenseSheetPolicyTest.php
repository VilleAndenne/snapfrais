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

        $policy = new ExpenseSheetPolicy;

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

        $policy = new ExpenseSheetPolicy;

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

        $policy = new ExpenseSheetPolicy;

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

        $policy = new ExpenseSheetPolicy;

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

        $policy = new ExpenseSheetPolicy;

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

        $policy = new ExpenseSheetPolicy;

        // Le bénéficiaire doit également pouvoir modifier le brouillon créé pour lui
        $this->assertTrue(
            $policy->edit($beneficiary, $expenseSheet),
            'Le bénéficiaire devrait pouvoir modifier un brouillon créé pour lui'
        );
    }

    public function test_admin_can_return_approved_expense_sheet(): void
    {
        // Créer un département
        $department = Department::factory()->create();

        // Créer un admin et un utilisateur normal
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);
        $department->users()->attach([$admin->id, $user->id]);

        // Créer un formulaire
        $form = Form::factory()->create();

        // Créer une note de frais approuvée
        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'is_draft' => false,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'status' => 'Approuvée',
            'approved' => true,
        ]);

        $policy = new ExpenseSheetPolicy;

        // L'admin (SRH) doit pouvoir renvoyer une note approuvée
        $this->assertTrue(
            $policy->returnBySRH($admin, $expenseSheet),
            'Un admin devrait pouvoir renvoyer une note de frais approuvée'
        );
    }

    public function test_admin_cannot_return_non_approved_expense_sheet(): void
    {
        // Créer un département
        $department = Department::factory()->create();

        // Créer un admin et un utilisateur normal
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);
        $department->users()->attach([$admin->id, $user->id]);

        // Créer un formulaire
        $form = Form::factory()->create();

        // Créer une note de frais en attente (non approuvée)
        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'is_draft' => false,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'status' => 'En attente',
            'approved' => null,
        ]);

        $policy = new ExpenseSheetPolicy;

        // L'admin ne doit PAS pouvoir renvoyer une note non approuvée
        $this->assertFalse(
            $policy->returnBySRH($admin, $expenseSheet),
            'Un admin ne devrait pas pouvoir renvoyer une note de frais non approuvée'
        );
    }

    public function test_head_of_root_department_can_self_validate_own_note(): void
    {
        // Département racine (parent_id = null par défaut via la factory)
        $rootDepartment = Department::factory()->create();

        // Utilisateur responsable du département racine
        $head = User::factory()->create(['is_admin' => false]);
        $rootDepartment->users()->attach($head->id, ['is_head' => true]);

        $form = Form::factory()->create();

        // Note de frais soumise par le responsable lui-même
        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $head->id,
            'created_by' => $head->id,
            'is_draft' => false,
            'department_id' => $rootDepartment->id,
            'form_id' => $form->id,
            'status' => 'En attente',
            'approved' => null,
        ]);

        $policy = new ExpenseSheetPolicy;

        $this->assertTrue(
            $policy->approve($head, $expenseSheet),
            'Le responsable d\'un département racine doit pouvoir auto-valider sa propre note'
        );
        $this->assertTrue(
            $policy->shouldAppearInValidationList($head, $expenseSheet),
            'L\'auto-validation doit faire apparaître la note dans la liste à valider'
        );
    }

    public function test_head_of_non_root_department_cannot_self_validate_own_note(): void
    {
        // Département parent
        $parentDepartment = Department::factory()->create();
        // Sous-département (a un parent_id)
        $childDepartment = Department::factory()->create(['parent_id' => $parentDepartment->id]);

        // Responsable du sous-département uniquement
        $head = User::factory()->create(['is_admin' => false]);
        $childDepartment->users()->attach($head->id, ['is_head' => true]);

        $form = Form::factory()->create();

        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $head->id,
            'created_by' => $head->id,
            'is_draft' => false,
            'department_id' => $childDepartment->id,
            'form_id' => $form->id,
            'status' => 'En attente',
            'approved' => null,
        ]);

        $policy = new ExpenseSheetPolicy;

        $this->assertFalse(
            $policy->approve($head, $expenseSheet),
            'Le responsable d\'un département non-racine ne doit pas pouvoir auto-valider'
        );
        $this->assertFalse(
            $policy->shouldAppearInValidationList($head, $expenseSheet),
            'La note ne doit pas apparaître dans la liste à valider de l\'auteur (non-racine)'
        );
    }

    public function test_co_head_of_root_department_cannot_validate_other_head_note(): void
    {
        $rootDepartment = Department::factory()->create();

        $headA = User::factory()->create(['is_admin' => false]);
        $headB = User::factory()->create(['is_admin' => false]);
        $rootDepartment->users()->attach($headA->id, ['is_head' => true]);
        $rootDepartment->users()->attach($headB->id, ['is_head' => true]);

        $form = Form::factory()->create();

        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $headA->id,
            'created_by' => $headA->id,
            'is_draft' => false,
            'department_id' => $rootDepartment->id,
            'form_id' => $form->id,
            'status' => 'En attente',
            'approved' => null,
        ]);

        $policy = new ExpenseSheetPolicy;

        // L'auto-validation est réservée à l'auteur ; les co-responsables ne se valident pas entre eux
        $this->assertFalse(
            $policy->approve($headB, $expenseSheet),
            'Un co-responsable ne doit pas valider la note d\'un autre co-responsable'
        );
    }

    public function test_resolve_approvers_for_root_department_head_returns_only_author(): void
    {
        $rootDepartment = Department::factory()->create();

        $headA = User::factory()->create(['is_admin' => false]);
        $headB = User::factory()->create(['is_admin' => false]);
        $rootDepartment->users()->attach($headA->id, ['is_head' => true]);
        $rootDepartment->users()->attach($headB->id, ['is_head' => true]);

        $form = Form::factory()->create();

        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $headA->id,
            'created_by' => $headA->id,
            'is_draft' => false,
            'department_id' => $rootDepartment->id,
            'form_id' => $form->id,
            'status' => 'En attente',
            'approved' => null,
        ]);

        $approvers = $expenseSheet->resolveApprovers($headA);

        $this->assertCount(1, $approvers);
        $this->assertTrue($approvers->contains($headA));
        $this->assertFalse($approvers->contains($headB));
    }

    public function test_non_admin_cannot_return_expense_sheet(): void
    {
        // Créer un département
        $department = Department::factory()->create();

        // Créer deux utilisateurs non admin
        $head = User::factory()->create(['is_admin' => false]);
        $user = User::factory()->create(['is_admin' => false]);
        $department->users()->attach($user->id);
        $department->users()->attach($head->id, ['is_head' => true]);

        // Créer un formulaire
        $form = Form::factory()->create();

        // Créer une note de frais approuvée
        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'is_draft' => false,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'status' => 'Approuvée',
            'approved' => true,
        ]);

        $policy = new ExpenseSheetPolicy;

        // Un non-admin (même responsable) ne doit PAS pouvoir renvoyer une note approuvée
        $this->assertFalse(
            $policy->returnBySRH($head, $expenseSheet),
            'Un non-admin ne devrait pas pouvoir renvoyer une note de frais'
        );
    }
}
