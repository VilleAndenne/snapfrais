<?php

namespace Tests\Unit;

use App\Models\Form;
use App\Models\Organization;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BelongsToOrganizationTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        setCurrentOrganization(null);

        parent::tearDown();
    }

    public function test_auto_fills_organization_id_from_current_context(): void
    {
        $organization = Organization::factory()->create();
        setCurrentOrganization($organization);

        $form = Form::create(['name' => 'Frais', 'description' => 'Test']);

        $this->assertSame($organization->id, $form->organization_id);
    }

    public function test_global_scope_isolates_queries_by_organization(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        Form::factory()->create(['organization_id' => $orgA->id]);
        Form::factory()->create(['organization_id' => $orgB->id]);

        setCurrentOrganization($orgA);
        $this->assertSame(1, Form::count());

        setCurrentOrganization($orgB);
        $this->assertSame(1, Form::count());
    }

    public function test_no_context_applies_no_scope(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();

        Form::factory()->create(['organization_id' => $orgA->id]);
        Form::factory()->create(['organization_id' => $orgB->id]);

        setCurrentOrganization(null);

        $this->assertSame(2, Form::count());
    }

    public function test_explicit_organization_id_is_not_overwritten(): void
    {
        $orgA = Organization::factory()->create();
        $orgB = Organization::factory()->create();
        setCurrentOrganization($orgA);

        $form = Form::create([
            'name' => 'Frais',
            'description' => 'Test',
            'organization_id' => $orgB->id,
        ]);

        $this->assertSame($orgB->id, $form->organization_id);
    }
}
