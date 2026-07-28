<?php

namespace Tests\Feature;

use App\Models\ExpenseSheet;
use App\Models\Organization;
use App\Models\User;
use App\Notifications\ReceiptExpenseSheet;
use App\Notifications\UserCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class OrganizationNotificationUrlTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'https://snapfrais.be']);
        URL::forceRootUrl('https://snapfrais.be');
    }

    public function test_organization_url_uses_custom_domain_when_set(): void
    {
        $organization = Organization::factory()->make(['domain' => 'frais.cpas-andenne.be']);

        $this->assertSame('https://frais.cpas-andenne.be/dashboard', $organization->url('/dashboard'));
    }

    public function test_organization_url_falls_back_to_subdomain(): void
    {
        $organization = Organization::factory()->make(['slug' => 'ville', 'domain' => null]);

        $this->assertSame('https://ville.snapfrais.be/dashboard', $organization->url('/dashboard'));
    }

    public function test_expense_sheet_notification_links_to_the_sheet_organization(): void
    {
        $organization = Organization::factory()->create(['slug' => 'cpas', 'domain' => 'frais.cpas-andenne.be']);
        $sheet = ExpenseSheet::factory()->create(['organization_id' => $organization->id]);

        $mail = (new ReceiptExpenseSheet($sheet))->toMail($sheet->user);

        $this->assertSame('https://frais.cpas-andenne.be/expense-sheet/'.$sheet->id, $mail->actionUrl);
    }

    public function test_account_notification_links_to_the_provided_organization(): void
    {
        $organization = Organization::factory()->create(['slug' => 'ville', 'domain' => null]);
        $user = User::factory()->create();

        $mail = (new UserCreated('tok', 'agent@ville.be', $organization))->toMail($user);

        $this->assertSame('https://ville.snapfrais.be/reset-password/tok?email=agent@ville.be', $mail->actionUrl);
    }

    public function test_notification_falls_back_to_app_url_without_organization(): void
    {
        $sheet = ExpenseSheet::factory()->create(['organization_id' => null]);

        $mail = (new ReceiptExpenseSheet($sheet))->toMail($sheet->user);

        $this->assertSame(url('/expense-sheet/'.$sheet->id), $mail->actionUrl);
    }
}
