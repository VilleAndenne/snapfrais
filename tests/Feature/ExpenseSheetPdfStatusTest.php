<?php

namespace Tests\Feature;

use App\Models\ExpenseSheet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseSheetPdfStatusTest extends TestCase
{
    use RefreshDatabase;

    private function renderStatus(?bool $approved, bool $isDraft = false): string
    {
        $validator = User::factory()->create();

        $expenseSheet = ExpenseSheet::factory()->create([
            'is_draft' => $isDraft,
            'approved' => $approved,
            'validated_by' => $approved === null ? null : $validator->id,
            'validated_at' => $approved === null ? null : now(),
        ]);

        return view('expenseSheet.pdf', [
            'expenseSheet' => $expenseSheet->fresh(),
            'organizationName' => 'Organisation de test',
        ])->render();
    }

    public function test_approved_expense_sheet_pdf_shows_approved_status(): void
    {
        $this->assertStringContainsString('Approuvée', $this->renderStatus(true));
    }

    public function test_rejected_expense_sheet_pdf_shows_rejected_status(): void
    {
        $this->assertStringContainsString('Refusée', $this->renderStatus(false));
    }

    public function test_pending_expense_sheet_pdf_shows_pending_status(): void
    {
        $this->assertStringContainsString('En attente', $this->renderStatus(null));
    }
}
