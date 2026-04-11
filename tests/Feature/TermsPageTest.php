<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class TermsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_terms_page_is_accessible_to_guests(): void
    {
        $response = $this->get(route('terms.show'));

        $response->assertStatus(200);
        $response->assertInertia(fn (AssertableInertia $page) => $page->component('Legal/Terms'));
    }
}
