<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExpenseSheet;
use App\Models\Form;
use App\Models\User;
use App\Notifications\SRHReturnExpenseSheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ReturnBySRHExpenseSheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_return_approved_expense_sheet(): void
    {
        Notification::fake();

        // Créer un département avec un responsable
        $department = Department::factory()->create();
        $head = User::factory()->create(['is_admin' => false]);
        $department->users()->attach($head->id, ['is_head' => true]);

        // Créer un admin (SRH) et un utilisateur normal
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);
        $department->users()->attach($user->id);

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

        // L'admin renvoie la note
        $response = $this->actingAs($admin)->post("/expense-sheet/{$expenseSheet->id}/return-by-srh", [
            'reason' => 'Justificatif manquant pour le déplacement du 15/01',
        ]);

        $response->assertRedirect("/expense-sheet/{$expenseSheet->id}");

        // Vérifier que la note a été mise à jour
        $expenseSheet->refresh();
        $this->assertFalse((bool) $expenseSheet->approved);
        $this->assertEquals('Renvoyé par le SRH', $expenseSheet->status);
        $this->assertEquals('Justificatif manquant pour le déplacement du 15/01', $expenseSheet->refusal_reason);
        $this->assertEquals($admin->id, $expenseSheet->validated_by);

        // Vérifier que les notifications ont été envoyées
        Notification::assertSentTo($user, SRHReturnExpenseSheet::class);
        Notification::assertSentTo($head, SRHReturnExpenseSheet::class);
    }

    public function test_admin_cannot_return_expense_sheet_without_reason(): void
    {
        // Créer un département
        $department = Department::factory()->create();

        // Créer un admin et un utilisateur normal
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);
        $department->users()->attach($user->id);

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

        // L'admin essaie de renvoyer la note sans raison
        $response = $this->actingAs($admin)->post("/expense-sheet/{$expenseSheet->id}/return-by-srh", [
            'reason' => '',
        ]);

        $response->assertSessionHasErrors('reason');

        // Vérifier que la note n'a pas été modifiée
        $expenseSheet->refresh();
        $this->assertTrue((bool) $expenseSheet->approved);
    }

    public function test_non_admin_cannot_return_expense_sheet(): void
    {
        // Créer un département
        $department = Department::factory()->create();

        // Créer un responsable (non admin) et un utilisateur normal
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

        // Le responsable (non admin) essaie de renvoyer la note
        $response = $this->actingAs($head)->post("/expense-sheet/{$expenseSheet->id}/return-by-srh", [
            'reason' => 'Test de renvoi',
        ]);

        $response->assertForbidden();

        // Vérifier que la note n'a pas été modifiée
        $expenseSheet->refresh();
        $this->assertTrue((bool) $expenseSheet->approved);
    }

    public function test_admin_cannot_return_non_approved_expense_sheet(): void
    {
        // Créer un département
        $department = Department::factory()->create();

        // Créer un admin et un utilisateur normal
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['is_admin' => false]);
        $department->users()->attach($user->id);

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

        // L'admin essaie de renvoyer la note
        $response = $this->actingAs($admin)->post("/expense-sheet/{$expenseSheet->id}/return-by-srh", [
            'reason' => 'Test de renvoi',
        ]);

        $response->assertForbidden();
    }
}
