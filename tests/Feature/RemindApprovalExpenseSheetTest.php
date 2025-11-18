<?php

namespace Tests\Feature;

use App\Jobs\RemindApprovalExpenseSheet;
use App\Models\Department;
use App\Models\ExpenseSheet;
use App\Models\Form;
use App\Models\User;
use App\Notifications\RemindApprovalExpenseSheetNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RemindApprovalExpenseSheetTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_notification_sent_when_no_expense_sheets_to_validate(): void
    {
        Notification::fake();

        // Créer un département avec un responsable
        $department = Department::factory()->create();
        $head = User::factory()->create();
        $department->heads()->attach($head->id, ['is_head' => true]);

        // Pas de notes de frais à valider
        // Exécuter le job
        RemindApprovalExpenseSheet::dispatch();

        // Vérifier qu'aucune notification n'a été envoyée
        Notification::assertNothingSent();
    }

    public function test_notification_sent_with_correct_count_for_pending_expense_sheets(): void
    {
        Notification::fake();

        // Créer un département avec un responsable
        $department = Department::factory()->create();
        $head = User::factory()->create();
        $department->heads()->attach($head->id, ['is_head' => true]);

        // Créer un utilisateur membre du département (non responsable)
        $employee = User::factory()->create();
        $department->users()->attach($employee->id, ['is_head' => false]);

        // Créer un formulaire
        $form = Form::factory()->create();

        // Créer 3 notes de frais en attente de validation
        ExpenseSheet::factory()->count(3)->create([
            'user_id' => $employee->id,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'is_draft' => false,
            'approved' => null,
        ]);

        // Exécuter le job
        RemindApprovalExpenseSheet::dispatch();

        // Vérifier qu'une notification a été envoyée au responsable avec le bon nombre
        Notification::assertSentTo(
            $head,
            RemindApprovalExpenseSheetNotification::class,
            function ($notification) {
                return $notification->count === 3;
            }
        );
    }

    public function test_no_notification_sent_for_draft_expense_sheets(): void
    {
        Notification::fake();

        // Créer un département avec un responsable
        $department = Department::factory()->create();
        $head = User::factory()->create();
        $department->heads()->attach($head->id, ['is_head' => true]);

        // Créer un utilisateur membre du département
        $employee = User::factory()->create();
        $department->users()->attach($employee->id, ['is_head' => false]);

        // Créer un formulaire
        $form = Form::factory()->create();

        // Créer 2 notes de frais en brouillon
        ExpenseSheet::factory()->count(2)->create([
            'user_id' => $employee->id,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'is_draft' => true,
            'approved' => null,
        ]);

        // Exécuter le job
        RemindApprovalExpenseSheet::dispatch();

        // Vérifier qu'aucune notification n'a été envoyée
        Notification::assertNothingSent();
    }

    public function test_no_notification_sent_for_already_approved_expense_sheets(): void
    {
        Notification::fake();

        // Créer un département avec un responsable
        $department = Department::factory()->create();
        $head = User::factory()->create();
        $department->heads()->attach($head->id, ['is_head' => true]);

        // Créer un utilisateur membre du département
        $employee = User::factory()->create();
        $department->users()->attach($employee->id, ['is_head' => false]);

        // Créer un formulaire
        $form = Form::factory()->create();

        // Créer 2 notes de frais déjà approuvées
        ExpenseSheet::factory()->count(2)->create([
            'user_id' => $employee->id,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'is_draft' => false,
            'approved' => true,
        ]);

        // Exécuter le job
        RemindApprovalExpenseSheet::dispatch();

        // Vérifier qu'aucune notification n'a été envoyée
        Notification::assertNothingSent();
    }

    public function test_no_notification_sent_for_expense_sheets_from_same_head(): void
    {
        Notification::fake();

        // Créer un département avec un responsable
        $department = Department::factory()->create();
        $head = User::factory()->create();
        $department->heads()->attach($head->id, ['is_head' => true]);

        // Créer un formulaire
        $form = Form::factory()->create();

        // Créer 2 notes de frais créées par le responsable lui-même
        ExpenseSheet::factory()->count(2)->create([
            'user_id' => $head->id,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'is_draft' => false,
            'approved' => null,
        ]);

        // Exécuter le job
        RemindApprovalExpenseSheet::dispatch();

        // Vérifier qu'aucune notification n'a été envoyée
        Notification::assertNothingSent();
    }

    public function test_notification_count_matches_dashboard_filtered_count(): void
    {
        Notification::fake();

        // Créer un département avec un responsable
        $department = Department::factory()->create();
        $head = User::factory()->create();
        $department->heads()->attach($head->id, ['is_head' => true]);

        // Créer un utilisateur membre du département
        $employee = User::factory()->create();
        $department->users()->attach($employee->id, ['is_head' => false]);

        // Créer un formulaire
        $form = Form::factory()->create();

        // Créer différents types de notes de frais
        // 5 notes en attente (doivent apparaître)
        ExpenseSheet::factory()->count(5)->create([
            'user_id' => $employee->id,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'is_draft' => false,
            'approved' => null,
        ]);

        // 2 brouillons (ne doivent PAS apparaître)
        ExpenseSheet::factory()->count(2)->create([
            'user_id' => $employee->id,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'is_draft' => true,
            'approved' => null,
        ]);

        // 3 notes approuvées (ne doivent PAS apparaître)
        ExpenseSheet::factory()->count(3)->create([
            'user_id' => $employee->id,
            'department_id' => $department->id,
            'form_id' => $form->id,
            'is_draft' => false,
            'approved' => true,
        ]);

        // Exécuter le job
        RemindApprovalExpenseSheet::dispatch();

        // Vérifier qu'une notification a été envoyée avec exactement 5 notes
        Notification::assertSentTo(
            $head,
            RemindApprovalExpenseSheetNotification::class,
            function ($notification) {
                return $notification->count === 5;
            }
        );
    }
}
