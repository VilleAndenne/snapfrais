<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExpenseSheet;
use App\Models\Form;
use App\Models\FormCost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ExpenseSheetExportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @param  array<int, array{date: string, amount: float}>  $costs
     */
    private function makeSheet(User $user, Department $department, Form $form, FormCost $formCost, array $costs): ExpenseSheet
    {
        $sheet = ExpenseSheet::create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'status' => 'Validé',
            'total' => 0,
            'form_id' => $form->id,
            'department_id' => $department->id,
            'is_draft' => false,
            'approved' => true,
            'validated_at' => '2026-06-10 10:00:00',
        ]);

        foreach ($costs as $cost) {
            $sheet->costs()->create([
                'form_cost_id' => $formCost->id,
                'type' => 'fixed',
                'amount' => $cost['amount'],
                'total' => $cost['amount'],
                'date' => $cost['date'],
                'requirements' => json_encode([]),
            ]);
        }

        return $sheet;
    }

    /**
     * @return array<int, array<int, string|float|null>>
     */
    private function readExport(): array
    {
        $files = Storage::allFiles('exports');
        $this->assertCount(1, $files);

        $spreadsheet = IOFactory::load(Storage::path($files[0]));

        return $spreadsheet->getActiveSheet()->toArray();
    }

    public function test_export_groups_cost_columns_by_month_of_cost_date(): void
    {
        Storage::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $department = Department::factory()->create();
        $user = User::factory()->create(['is_admin' => false, 'name' => 'Jean Agent']);

        $form = Form::factory()->create();
        $formCost = FormCost::factory()->create([
            'name' => 'Repas',
            'type' => 'fixed',
            'form_id' => $form->id,
        ]);

        // Une note validée en juin, mais avec des coûts datés d'avril et de mai.
        $this->makeSheet($user, $department, $form, $formCost, [
            ['date' => '2026-04-05', 'amount' => 25],
            ['date' => '2026-04-12', 'amount' => 15],
            ['date' => '2026-05-03', 'amount' => 30],
        ]);

        $response = $this->actingAs($admin)->get(route('expense-sheets.export', [
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
        ]));

        $response->assertSessionHasNoErrors();

        $rows = $this->readExport();
        $headers = $rows[0];

        // En-têtes : Username, AVRIL 2026 (titre), colonne coût avril, MAI 2026 (titre), colonne coût mai
        $costColumn = 'EURO - Repas ('.$form->name.')';

        $this->assertSame('Username', $headers[0]);
        $this->assertSame('AVRIL 2026', $headers[1]);
        $this->assertSame($costColumn, $headers[2]);
        $this->assertSame('MAI 2026', $headers[3]);
        $this->assertSame($costColumn, $headers[4]);

        // Ligne de l'utilisateur : nom, titre mois vide, total avril (40), titre mois vide, total mai (30)
        $userRow = $rows[1];
        $this->assertSame('Jean Agent', $userRow[0]);
        $this->assertNull($userRow[1]);
        $this->assertEquals(40, $userRow[2]);
        $this->assertNull($userRow[3]);
        $this->assertEquals(30, $userRow[4]);
    }

    public function test_export_only_includes_months_that_have_costs(): void
    {
        Storage::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        $department = Department::factory()->create();
        $user = User::factory()->create(['is_admin' => false]);

        $form = Form::factory()->create();
        $formCost = FormCost::factory()->create([
            'name' => 'Repas',
            'type' => 'fixed',
            'form_id' => $form->id,
        ]);

        $this->makeSheet($user, $department, $form, $formCost, [
            ['date' => '2026-04-05', 'amount' => 25],
        ]);

        $this->actingAs($admin)->get(route('expense-sheets.export', [
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
        ]))->assertSessionHasNoErrors();

        $headers = $this->readExport()[0];

        $this->assertContains('AVRIL 2026', $headers);
        $this->assertNotContains('MAI 2026', $headers);
        $this->assertNotContains('JUIN 2026', $headers);
    }
}
