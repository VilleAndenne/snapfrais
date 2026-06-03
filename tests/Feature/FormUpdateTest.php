<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_update_keeps_existing_items_adds_and_removes(): void
    {
        $user = User::factory()->create();

        $form = Form::factory()->create(['name' => 'Ancien']);
        $cost = $form->costs()->create(['name' => 'Coût', 'description' => 'Desc', 'type' => 'fixed']);
        $rate = $cost->reimbursementRates()->create([
            'start_date' => '2025-01-01',
            'end_date' => null,
            'value' => 10,
            'transport' => 'car',
        ]);
        $requirement = $cost->requirements()->create(['name' => 'Justif', 'type' => 'text']);

        $this->actingAs($user)
            ->put(route('forms.update', $form->id), [
                'name' => 'Nouveau',
                'description' => 'MAJ',
                'costs' => [
                    [
                        'id' => $cost->id,
                        'name' => 'Coût modifié',
                        'description' => 'Desc modifiée',
                        'type' => 'fixed',
                        'reimbursement_rates' => [
                            // Taux existant mis à jour
                            ['id' => $rate->id, 'start_date' => '2025-01-01', 'end_date' => null, 'value' => 20, 'transport' => 'car'],
                            // Nouveau taux (sans id)
                            ['start_date' => '2026-01-01', 'end_date' => null, 'value' => 30, 'transport' => 'car'],
                        ],
                        // Prérequis vidé -> doit être supprimé
                        'requirements' => [],
                    ],
                ],
            ])
            ->assertRedirect();

        $form->refresh();
        $this->assertSame('Nouveau', $form->name);

        // Coût mis à jour (pas recréé)
        $this->assertDatabaseHas('form_costs', ['id' => $cost->id, 'name' => 'Coût modifié']);
        $this->assertSame(1, $form->costs()->count());

        // Taux existant mis à jour + nouveau taux créé
        $this->assertDatabaseHas('form_cost_remboursiement_rates', ['id' => $rate->id, 'value' => 20]);
        $this->assertDatabaseHas('form_cost_remboursiement_rates', ['form_cost_id' => $cost->id, 'value' => 30]);
        $this->assertSame(2, $cost->reimbursementRates()->count());

        // Prérequis supprimé
        $this->assertDatabaseMissing('form_cost_requirements', ['id' => $requirement->id]);
    }

    public function test_update_removes_cost_no_longer_present(): void
    {
        $user = User::factory()->create();

        $form = Form::factory()->create();
        $kept = $form->costs()->create(['name' => 'Gardé', 'description' => 'D', 'type' => 'fixed']);
        $removed = $form->costs()->create(['name' => 'Retiré', 'description' => 'D', 'type' => 'fixed']);

        $this->actingAs($user)
            ->put(route('forms.update', $form->id), [
                'name' => $form->name,
                'description' => $form->description,
                'costs' => [
                    ['id' => $kept->id, 'name' => 'Gardé', 'description' => 'D', 'type' => 'fixed', 'reimbursement_rates' => [], 'requirements' => []],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('form_costs', ['id' => $kept->id]);
        $this->assertDatabaseMissing('form_costs', ['id' => $removed->id]);
    }
}
