<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\ExpenseSheet;
use App\Models\ExpenseSheetCost;
use App\Models\Form;
use App\Models\FormCost;
use App\Models\FormCostRemboursiementRate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExpenseSheetTransportDisplayTest extends TestCase
{
    use RefreshDatabase;

    public function test_bike_transport_is_persisted_and_exposed_to_the_show_view(): void
    {
        Notification::fake();
        Storage::fake('public');
        Http::fake([
            'routes.googleapis.com/*' => Http::response(['routes' => [['distanceMeters' => 5000]]]),
        ]);

        $department = Department::factory()->create();
        $user = User::factory()->create(['is_admin' => false]);
        $department->users()->attach($user->id);

        $form = Form::factory()->create();
        $formCost = new FormCost;
        $formCost->name = 'Déplacement vélo';
        $formCost->description = 'Trajet à vélo';
        $formCost->type = 'km';
        $formCost->form_id = $form->id;
        $formCost->save();

        $rate = new FormCostRemboursiementRate;
        $rate->form_cost_id = $formCost->id;
        $rate->start_date = '2026-01-01';
        $rate->end_date = null;
        $rate->value = 0.29;
        $rate->transport = 'bike';
        $rate->save();

        $response = $this->actingAs($user)->post("/expense-sheet/{$form->id}", [
            'department_id' => $department->id,
            'is_draft' => 0,
            'costs' => [[
                'cost_id' => $formCost->id,
                'date' => '2026-05-01',
                'data' => ['departure' => 'A', 'arrival' => 'B', 'manualKm' => 0],
            ]],
        ]);

        $response->assertSessionHasNoErrors();

        $cost = ExpenseSheetCost::firstOrFail();
        $this->assertSame('bike', $cost->route['transport']);

        $this->get("/expense-sheet/{$cost->expense_sheet_id}")
            ->assertInertia(fn ($page) => $page
                ->where('expenseSheet.costs.0.route.transport', 'bike')
                ->where('expenseSheet.costs.0.form_cost.reimbursement_rates.0.transport', 'bike')
            );
    }

    public function test_show_exposes_the_configured_rate_transport_even_when_the_stored_route_says_car(): void
    {
        Notification::fake();

        $department = Department::factory()->create();
        $user = User::factory()->create(['is_admin' => false]);
        $department->users()->attach($user->id);

        $form = Form::factory()->create();
        $formCost = new FormCost;
        $formCost->name = 'Déplacement vélo';
        $formCost->description = 'Trajet à vélo';
        $formCost->type = 'km';
        $formCost->form_id = $form->id;
        $formCost->save();

        $rate = new FormCostRemboursiementRate;
        $rate->form_cost_id = $formCost->id;
        $rate->start_date = '2026-01-01';
        $rate->end_date = null;
        $rate->value = 0.29;
        $rate->transport = 'bike';
        $rate->save();

        $expenseSheet = ExpenseSheet::create([
            'user_id' => $user->id,
            'created_by' => $user->id,
            'status' => 'En attente',
            'total' => 1.45,
            'form_id' => $form->id,
            'department_id' => $department->id,
            'is_draft' => false,
        ]);

        $expenseSheet->costs()->create([
            'form_cost_id' => $formCost->id,
            'type' => 'km',
            'distance' => 5,
            'google_distance' => 5,
            'route' => ['departure' => 'A', 'arrival' => 'B', 'google_km' => 5, 'manual_km' => 0, 'transport' => 'car'],
            'total' => 1.45,
            'date' => '2026-05-01',
        ]);

        $this->actingAs($user)
            ->get("/expense-sheet/{$expenseSheet->id}")
            ->assertInertia(fn ($page) => $page
                ->where('expenseSheet.costs.0.form_cost.reimbursement_rates.0.transport', 'bike')
                ->where('expenseSheet.costs.0.form_cost.reimbursement_rates.0.start_date', '2026-01-01')
                ->where('expenseSheet.costs.0.date', '2026-05-01')
            );
    }
}
