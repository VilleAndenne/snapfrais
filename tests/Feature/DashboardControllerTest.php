<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Form;
use App\Models\ExpenseSheet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    // public function test_dashboard_index_returns_correct_data()
    {
        // Créer un utilisateur authentifié
        $user = User::factory()->create();
        $this->actingAs($user);
        // Appeler la route du dashboard
        $response = $this->get(route('dashboard'));

        // Vérifier que la réponse est correcte
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('forms', 0)
            ->has('expenseSheets', 0)
            ->has('expenseToValidate')
            ->where('isHead', false)
        );
    }
}
