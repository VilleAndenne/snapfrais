<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TermsAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.terms_updated_at' => '2026-04-11 00:00:00']);
    }

    public function test_user_with_outdated_acceptance_is_redirected_to_acceptance_page(): void
    {
        $user = User::factory()->create([
            'terms_accepted_at' => Carbon::parse('2026-01-01 00:00:00'),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('terms.accept'));
    }

    public function test_user_who_never_accepted_terms_is_redirected(): void
    {
        $user = User::factory()->create([
            'terms_accepted_at' => null,
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect(route('terms.accept'));
    }

    public function test_user_with_current_acceptance_can_access_dashboard(): void
    {
        $user = User::factory()->create([
            'terms_accepted_at' => Carbon::parse('2026-04-12 12:00:00'),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
    }

    public function test_acceptance_page_is_accessible_to_outdated_user(): void
    {
        $user = User::factory()->create([
            'terms_accepted_at' => null,
        ]);

        $response = $this->actingAs($user)->get(route('terms.accept'));

        $response->assertOk();
    }

    public function test_user_can_record_acceptance(): void
    {
        $user = User::factory()->create([
            'terms_accepted_at' => null,
        ]);

        $response = $this->actingAs($user)->post(route('terms.accept.store'), [
            'accepted' => true,
        ]);

        $response->assertRedirect();
        $this->assertNotNull($user->fresh()->terms_accepted_at);
        $this->assertTrue($user->fresh()->hasAcceptedCurrentTerms());
    }

    public function test_acceptance_requires_checkbox_to_be_checked(): void
    {
        $user = User::factory()->create([
            'terms_accepted_at' => null,
        ]);

        $response = $this->from(route('terms.accept'))->actingAs($user)->post(route('terms.accept.store'), [
            'accepted' => false,
        ]);

        $response->assertSessionHasErrors('accepted');
        $this->assertNull($user->fresh()->terms_accepted_at);
    }

    public function test_fresh_login_with_outdated_terms_is_redirected_to_acceptance_page(): void
    {
        $user = User::factory()->create([
            'terms_accepted_at' => Carbon::parse('2026-01-01 00:00:00'),
            'password' => bcrypt('password'),
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertFalse($user->fresh()->hasAcceptedCurrentTerms());

        $response = $this->actingAs($user->fresh())->get('/dashboard');
        $response->assertRedirect(route('terms.accept'));
    }

    public function test_logout_remains_accessible_to_outdated_user(): void
    {
        $user = User::factory()->create([
            'terms_accepted_at' => null,
        ]);

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect('/');
        $this->assertGuest();
    }
}
