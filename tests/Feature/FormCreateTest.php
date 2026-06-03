<?php

namespace Tests\Feature;

use App\Models\Form;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FormCreateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_creates_form_with_cost_rate_and_requirement(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('forms.store'), [
                'name' => 'Frais de déplacement',
                'description' => 'Formulaire de test',
                'costs' => [
                    [
                        'name' => 'Indemnité kilométrique',
                        'description' => 'Trajet domicile-travail',
                        'type' => 'km',
                        'reimbursement_rates' => [
                            [
                                'start_date' => '2026-01-01',
                                'end_date' => null,
                                'value' => 0.42,
                                'transport' => 'car',
                            ],
                        ],
                        'requirements' => [
                            ['name' => 'Justificatif', 'type' => 'file'],
                        ],
                    ],
                ],
            ])
            ->assertRedirect(route('forms.index'))
            ->assertSessionHas('success');

        $form = Form::where('name', 'Frais de déplacement')->firstOrFail();
        $cost = $form->costs()->firstOrFail();

        $this->assertSame('Indemnité kilométrique', $cost->name);
        $this->assertSame('km', $cost->type);

        $this->assertDatabaseHas('form_cost_remboursiement_rates', [
            'form_cost_id' => $cost->id,
            'value' => 0.42,
            'transport' => 'car',
        ]);
        $this->assertDatabaseHas('form_cost_requirements', [
            'form_cost_id' => $cost->id,
            'name' => 'Justificatif',
            'type' => 'file',
        ]);
    }

    public function test_create_form_requires_at_least_one_cost(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('forms.store'), [
                'name' => 'Sans coût',
                'description' => 'Test',
                'costs' => [],
            ])
            ->assertSessionHasErrors('costs');

        $this->assertDatabaseMissing('forms', ['name' => 'Sans coût']);
    }
}
