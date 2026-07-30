<?php

namespace Tests\Feature;

use App\Http\Middleware\ResolveOrganization;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationSwitchTest extends TestCase
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

    public function test_super_admin_can_switch_organization_without_changing_url(): void
    {
        $ville = Organization::factory()->create(['slug' => 'ville']);
        $cpas = Organization::factory()->create(['slug' => 'cpas', 'organization_name' => 'CPAS de Test']);
        $super = User::factory()->create(['super_admin' => true]);

        $this->actingAs($super)
            ->post(route('organizations.switch', $cpas))
            ->assertSessionHas(ResolveOrganization::SESSION_KEY, $cpas->id);

        // Even while browsing the "ville" subdomain, the session override wins.
        $this->actingAs($super)
            ->get($this->on('ville', 'dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('organization.slug', 'cpas')
                ->where('organization.organizationName', 'CPAS de Test')
            );
    }

    public function test_super_admin_receives_switcher_options(): void
    {
        Organization::factory()->create(['slug' => 'ville', 'organization_name' => 'Ville de Test']);
        Organization::factory()->create(['slug' => 'cpas', 'organization_name' => 'CPAS de Test']);
        $super = User::factory()->create(['super_admin' => true]);

        $this->actingAs($super)
            ->get($this->on('ville', 'dashboard'))
            ->assertInertia(fn ($page) => $page
                ->where('organizationSwitcher.current', fn ($current) => $current !== null)
                ->has('organizationSwitcher.options', 2)
            );
    }

    public function test_non_super_admin_cannot_switch_organization(): void
    {
        $ville = Organization::factory()->create(['slug' => 'ville']);
        $cpas = Organization::factory()->create(['slug' => 'cpas']);
        $admin = User::factory()->create(['is_admin' => true, 'super_admin' => false]);
        $admin->organizations()->attach($ville);

        $this->actingAs($admin)
            ->post(route('organizations.switch', $cpas))
            ->assertForbidden();

        $this->assertNull(session(ResolveOrganization::SESSION_KEY));
    }

    public function test_non_super_admin_does_not_receive_switcher(): void
    {
        $ville = Organization::factory()->create(['slug' => 'ville']);
        $admin = User::factory()->create(['is_admin' => true, 'super_admin' => false]);
        $admin->organizations()->attach($ville);

        $this->actingAs($admin)
            ->get($this->on('ville', 'dashboard'))
            ->assertInertia(fn ($page) => $page->where('organizationSwitcher', null));
    }

    public function test_stale_override_falls_back_to_host_organization(): void
    {
        $ville = Organization::factory()->create(['slug' => 'ville', 'organization_name' => 'Ville de Test']);
        $super = User::factory()->create(['super_admin' => true]);

        $this->actingAs($super)
            ->withSession([ResolveOrganization::SESSION_KEY => 999999])
            ->get($this->on('ville', 'dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('organization.slug', 'ville'));
    }
}
