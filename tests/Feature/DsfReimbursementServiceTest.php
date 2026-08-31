<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExpenseSheet;
use App\Models\Form;
use App\Models\FormCost;
use App\Models\Organization;
use App\Models\User;
use App\Services\DsfReimbursementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Mailer\SentMessage;
use Tests\TestCase;

class DsfReimbursementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        setCurrentOrganization(null);

        parent::tearDown();
    }

    /**
     * Build an approved expense sheet carrying a single cost of the given
     * processing department, in the given organization.
     */
    private function makeSheet(Organization $organization, string $processingDepartment): ExpenseSheet
    {
        setCurrentOrganization($organization);

        $user = User::factory()->create();
        $department = Department::factory()->create();
        $form = Form::factory()->create();
        $formCost = FormCost::factory()->create([
            'name' => 'Fourniture',
            'type' => 'fixed',
            'form_id' => $form->id,
            'processing_department' => $processingDepartment,
        ]);

        $sheet = ExpenseSheet::create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'status' => 'Validé',
            'total' => 90,
            'form_id' => $form->id,
            'department_id' => $department->id,
            'is_draft' => false,
            'approved' => true,
            'validated_by' => $user->id,
            'validated_at' => '2026-06-10 10:00:00',
        ]);

        $sheet->costs()->create([
            'form_cost_id' => $formCost->id,
            'type' => 'fixed',
            'amount' => 90,
            'total' => 90,
            'date' => '2026-06-05',
            'requirements' => json_encode([]),
        ]);

        return $sheet->fresh();
    }

    /**
     * Messages captured by the `array` mail transport used in tests.
     *
     * @return list<SentMessage>
     */
    private function sentMessages(): array
    {
        return Mail::getSymfonyTransport()->messages()->all();
    }

    public function test_the_request_is_sent_to_the_address_configured_on_the_organization(): void
    {
        Storage::fake();

        $organization = Organization::factory()->create([
            'organization_name' => 'CPAS de Test',
            'dsf_recipient_email' => 'compta@cpas.test',
        ]);
        $sheet = $this->makeSheet($organization, 'DSF');

        (new DsfReimbursementService)->generateAndSendReimbursementPdf($sheet);

        $messages = $this->sentMessages();
        $this->assertCount(1, $messages);

        $email = $messages[0]->getOriginalMessage();

        $this->assertSame('compta@cpas.test', $email->getTo()[0]->getAddress());
        // Les organisations peuvent partager la boîte comptable : le sujet les distingue.
        $this->assertStringContainsString('CPAS de Test', $email->getSubject());
        $this->assertStringContainsString('#'.$sheet->id, $email->getSubject());
    }

    public function test_the_request_of_another_organization_goes_to_its_own_address(): void
    {
        Storage::fake();

        $ville = Organization::factory()->create([
            'organization_name' => 'Ville de Test',
            'dsf_recipient_email' => 'compta@ville.test',
        ]);
        $sheet = $this->makeSheet($ville, 'DSF');

        (new DsfReimbursementService)->generateAndSendReimbursementPdf($sheet);

        $email = $this->sentMessages()[0]->getOriginalMessage();

        $this->assertSame('compta@ville.test', $email->getTo()[0]->getAddress());
    }

    public function test_nothing_is_sent_when_the_organization_has_no_address(): void
    {
        Storage::fake();

        $organization = Organization::factory()->create(['dsf_recipient_email' => null]);
        $sheet = $this->makeSheet($organization, 'DSF');

        (new DsfReimbursementService)->generateAndSendReimbursementPdf($sheet);

        $this->assertSame([], $this->sentMessages());
        $this->assertSame([], Storage::allFiles('dsf_reimbursements'));
    }

    public function test_a_sheet_without_dsf_cost_is_detected_as_such(): void
    {
        $organization = Organization::factory()->create(['dsf_recipient_email' => 'compta@ville.test']);
        $sheet = $this->makeSheet($organization, 'SRH');

        $this->assertFalse((new DsfReimbursementService)->hasDsfCosts($sheet));
    }

    public function test_a_sheet_with_a_dsf_cost_is_detected_as_such(): void
    {
        $organization = Organization::factory()->create(['dsf_recipient_email' => 'compta@ville.test']);
        $sheet = $this->makeSheet($organization, 'DSF');

        $this->assertTrue((new DsfReimbursementService)->hasDsfCosts($sheet));
    }
}
