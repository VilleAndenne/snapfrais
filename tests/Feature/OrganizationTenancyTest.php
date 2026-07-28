<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationTenancyTest extends TestCase
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
    private function on(string $slug, string $routeName, array $params = []): string
    {
        return "http://{$slug}.snapfrais.test".route($routeName, $params, absolute: false);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function memberOf(Organization $organization, array $attributes = []): User
    {
        $user = User::factory()->create($attributes);
        $user->organizations()->attach($organization);

        return $user;
    }

    public function test_unknown_subdomain_returns_404(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get($this->on('inconnu', 'dashboard'))
            ->assertNotFound();
    }

    public function test_member_can_access_their_subdomain(): void
    {
        $organization = Organization::factory()->create(['slug' => 'ville']);
        $user = $this->memberOf($organization);

        $this->actingAs($user)
            ->get($this->on('ville', 'dashboard'))
            ->assertOk();
    }

    public function test_non_member_is_forbidden_on_other_subdomain(): void
    {
        $orgA = Organization::factory()->create(['slug' => 'ville']);
        Organization::factory()->create(['slug' => 'cpas']);
        $user = $this->memberOf($orgA);

        $this->actingAs($user)
            ->get($this->on('cpas', 'dashboard'))
            ->assertForbidden();
    }

    public function test_super_admin_is_transverse(): void
    {
        Organization::factory()->create(['slug' => 'cpas']);
        $super = User::factory()->create(['super_admin' => true]);

        $this->actingAs($super)
            ->get($this->on('cpas', 'dashboard'))
            ->assertOk();
    }

    public function test_user_index_is_scoped_to_subdomain_organization(): void
    {
        $orgA = Organization::factory()->create(['slug' => 'ville']);
        $orgB = Organization::factory()->create(['slug' => 'cpas']);

        $admin = $this->memberOf($orgA, ['is_admin' => true, 'super_admin' => false]);
        $this->memberOf($orgA);
        $this->memberOf($orgB);

        $this->actingAs($admin)
            ->get($this->on('ville', 'users.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('users/Index')
                ->has('users.data', 2)
            );
    }

    public function test_inertia_shares_current_organization_branding(): void
    {
        $organization = Organization::factory()->create([
            'slug' => 'ville',
            'organization_name' => 'Ville de Test',
        ]);
        $user = $this->memberOf($organization);

        $this->actingAs($user)
            ->get($this->on('ville', 'dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('organization.slug', 'ville')
                ->where('organization.organizationName', 'Ville de Test')
            );
    }
}
