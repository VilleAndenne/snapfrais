<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PaymentDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_agent_records_their_payment_details(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('payment-details.update'), [
                'bank_account_number' => 'BE68 5390 0754 7034',
                'address' => 'Rue de Velaine 162, 5300 Andenne',
            ])
            ->assertSessionHasNoErrors();

        $user->refresh();

        // L'IBAN est stocké sous sa forme canonique, quelle que soit la saisie.
        $this->assertSame('BE68539007547034', $user->bank_account_number);
        $this->assertSame('Rue de Velaine 162, 5300 Andenne', $user->address);
    }

    public function test_payment_details_are_encrypted_at_rest(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patch(route('payment-details.update'), [
            'bank_account_number' => 'BE68539007547034',
            'address' => 'Rue de Velaine 162, 5300 Andenne',
        ]);

        $stored = DB::table('users')->where('id', $user->id)->first();

        $this->assertNotSame('BE68539007547034', $stored->bank_account_number);
        $this->assertStringNotContainsString('BE68539007547034', $stored->bank_account_number);
        $this->assertStringNotContainsString('Velaine', $stored->address);
    }

    public function test_an_invalid_iban_is_rejected(): void
    {
        $user = User::factory()->create();

        // Checksum invalide : un seul chiffre modifié sur un IBAN par ailleurs bien formé.
        $this->actingAs($user)
            ->patch(route('payment-details.update'), [
                'bank_account_number' => 'BE68539007547035',
                'address' => 'Rue de Velaine 162, 5300 Andenne',
            ])
            ->assertSessionHasErrors('bank_account_number');

        $this->assertNull($user->fresh()->bank_account_number);
    }

    public function test_both_fields_are_required(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patch(route('payment-details.update'), [])
            ->assertSessionHasErrors(['bank_account_number', 'address']);
    }

    public function test_missing_details_are_advertised_to_the_front_end(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('missingPaymentDetails', ['bank_account_number', 'address'])
            );

        $user->update([
            'bank_account_number' => 'BE68539007547034',
            'address' => 'Rue de Velaine 162, 5300 Andenne',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertInertia(fn ($page) => $page->where('missingPaymentDetails', []));
    }

    public function test_payment_details_are_never_serialised_with_the_user(): void
    {
        $user = User::factory()->create([
            'bank_account_number' => 'BE68539007547034',
            'address' => 'Rue de Velaine 162, 5300 Andenne',
        ]);

        $serialised = $user->toArray();

        $this->assertArrayNotHasKey('bank_account_number', $serialised);
        $this->assertArrayNotHasKey('address', $serialised);

        // Le modèle authentifié est partagé sur chaque page : rien ne doit fuir.
        $response = $this->actingAs($user)->get(route('dashboard'));

        $this->assertStringNotContainsString('BE68539007547034', $response->getContent());
        $this->assertStringNotContainsString('Velaine', $response->getContent());
    }

    public function test_the_profile_page_exposes_the_details_to_their_owner(): void
    {
        $user = User::factory()->create([
            'bank_account_number' => 'BE68539007547034',
            'address' => 'Rue de Velaine 162, 5300 Andenne',
        ]);

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertInertia(fn ($page) => $page
                ->where('paymentDetails.bank_account_number', 'BE68539007547034')
                ->where('paymentDetails.address', 'Rue de Velaine 162, 5300 Andenne')
            );
    }
}
