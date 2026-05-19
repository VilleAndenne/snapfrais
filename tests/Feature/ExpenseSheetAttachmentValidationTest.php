<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Form;
use App\Models\FormCost;
use App\Models\FormCostRemboursiementRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseSheetAttachmentValidationTest extends TestCase
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
        $formCost->name = 'Frais avec annexe';
        $formCost->description = 'Coût avec annexe obligatoire';
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

    private function payload(array $context, UploadedFile $file): array
    {
        return [
            'department_id' => $context['department']->id,
            'is_draft' => 0,
            'costs' => [
                [
                    'cost_id' => $context['formCost']->id,
                    'data' => ['paidAmount' => 25],
                    'date' => '2026-05-01',
                    'requirements' => [
                        'justificatif' => ['file' => $file],
                    ],
                ],
            ],
        ];
    }

    public function test_rejects_attachment_with_disallowed_extension(): void
    {
        $context = $this->bootstrap();
        $file = UploadedFile::fake()->create('document.docx', 100, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $response = $this->actingAs($context['user'])
            ->post("/expense-sheet/{$context['form']->id}", $this->payload($context, $file));

        $response->assertSessionHasErrors('costs.0.requirements.justificatif.file');
        $this->assertDatabaseCount('expense_sheets', 0);
    }

    public function test_accepts_pdf_attachment(): void
    {
        $context = $this->bootstrap();
        $file = UploadedFile::fake()->create('justificatif.pdf', 100, 'application/pdf');

        $response = $this->actingAs($context['user'])
            ->post("/expense-sheet/{$context['form']->id}", $this->payload($context, $file));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('expense_sheets', 1);
    }

    public function test_accepts_image_attachment(): void
    {
        $context = $this->bootstrap();
        $file = UploadedFile::fake()->image('photo.jpg');

        $response = $this->actingAs($context['user'])
            ->post("/expense-sheet/{$context['form']->id}", $this->payload($context, $file));

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseCount('expense_sheets', 1);
    }
}
