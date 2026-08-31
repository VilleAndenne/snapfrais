<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationDsfSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.url' => 'http://snapfrais.test']);
    }

    protected function tearDown(): void
    {
        setCurrentOrganization(null);

        parent::tearDown();
    }

    /**
     * Build a URL on the given organization subdomain.
     */
    private function on(string $slug, string $routeName): string
    {
        return "http://{$slug}.snapfrais.test".route($routeName, [], absolute: false);
    }

    public function test_admin_sees_the_dsf_recipient_of_their_organization(): void
    {
        $organization = Organization::factory()->create([
            'slug' => 'ville',
            'dsf_recipient_email' => 'compta@ville.test',
        ]);
        $admin = User::factory()->create(['is_admin' => true]);
        $admin->organizations()->attach($organization);

        $this->actingAs($admin)
            ->get($this->on('ville', 'organization.edit'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('settings/Organization')
                ->where('organization.dsf_recipient_email', 'compta@ville.test')
            );
    }

    public function test_admin_updates_the_dsf_recipient_of_their_organization(): void
    {
        $organization = Organization::factory()->create(['slug' => 'ville']);
        $admin = User::factory()->create(['is_admin' => true]);
        $admin->organizations()->attach($organization);

        $this->actingAs($admin)
            ->patch($this->on('ville', 'organization.update'), [
                'dsf_recipient_email' => 'compta@ville.test',
            ])
            ->assertRedirect(route('organization.edit', [], absolute: false));

        $this->assertSame('compta@ville.test', $organization->fresh()->dsf_recipient_email);
    }

    public function test_the_dsf_recipient_must_be_a_valid_email(): void
    {
        $organization = Organization::factory()->create(['slug' => 'ville']);
        $admin = User::factory()->create(['is_admin' => true]);
        $admin->organizations()->attach($organization);

        $this->actingAs($admin)
            ->patch($this->on('ville', 'organization.update'), [
                'dsf_recipient_email' => 'pas-une-adresse',
            ])
            ->assertSessionHasErrors('dsf_recipient_email');

        $this->assertNull($organization->fresh()->dsf_recipient_email);
    }

    public function test_the_dsf_recipient_can_be_cleared(): void
    {
        $organization = Organization::factory()->create([
            'slug' => 'ville',
            'dsf_recipient_email' => 'compta@ville.test',
        ]);
        $admin = User::factory()->create(['is_admin' => true]);
        $admin->organizations()->attach($organization);

        $this->actingAs($admin)
            ->patch($this->on('ville', 'organization.update'), ['dsf_recipient_email' => null])
            ->assertRedirect(route('organization.edit', [], absolute: false));

        $this->assertNull($organization->fresh()->dsf_recipient_email);
    }

    public function test_a_non_admin_cannot_reach_the_organization_settings(): void
    {
        $organization = Organization::factory()->create(['slug' => 'ville']);
        $user = User::factory()->create(['is_admin' => false]);
        $user->organizations()->attach($organization);

        $this->actingAs($user)
            ->get($this->on('ville', 'organization.edit'))
            ->assertForbidden();
    }

    public function test_an_admin_of_another_organization_cannot_update_this_one(): void
    {
        $ville = Organization::factory()->create(['slug' => 'ville']);
        $cpas = Organization::factory()->create(['slug' => 'cpas']);
        $admin = User::factory()->create(['is_admin' => true]);
        $admin->organizations()->attach($cpas);

        $this->actingAs($admin)
            ->patch($this->on('ville', 'organization.update'), [
                'dsf_recipient_email' => 'compta@pirate.test',
            ])
            ->assertForbidden();

        $this->assertNull($ville->fresh()->dsf_recipient_email);
    }
}
