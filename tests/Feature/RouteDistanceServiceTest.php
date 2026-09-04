<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\RouteDistance;
use App\Models\User;
use App\Notifications\RouteDistanceAnomalyDetected;
use App\Services\RouteDistanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RouteDistanceServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sums_segment_distances_in_km_via_traffic_unaware_routes_api(): void
    {
        Http::fake([
            'routes.googleapis.com/*' => Http::response(['routes' => [['distanceMeters' => 5000]]]),
        ]);

        $km = (new RouteDistanceService)->distanceInKm(['Paris', 'Lyon', 'Marseille'], 'car');

        $this->assertSame(10.0, $km);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://routes.googleapis.com/directions/v2:computeRoutes'
                && $request['travelMode'] === 'DRIVE'
                && $request['routingPreference'] === 'TRAFFIC_UNAWARE'
                && $request->hasHeader('X-Goog-FieldMask', 'routes.distanceMeters');
        });
    }

    public function test_it_uses_bicycle_mode_without_routing_preference(): void
    {
        Http::fake([
            'routes.googleapis.com/*' => Http::response(['routes' => [['distanceMeters' => 3200]]]),
        ]);

        $km = (new RouteDistanceService)->distanceInKm(['A', 'B'], 'bike');

        $this->assertSame(3.2, $km);

        Http::assertSent(function ($request) {
            return $request['travelMode'] === 'BICYCLE'
                && ! isset($request['routingPreference']);
        });
    }

    public function test_it_ignores_failed_segments(): void
    {
        Http::fake([
            'routes.googleapis.com/*' => Http::response([], 500),
        ]);

        $km = (new RouteDistanceService)->distanceInKm(['A', 'B'], 'car');

        $this->assertSame(0.0, $km);
        $this->assertDatabaseCount('route_distances', 0);
    }

    public function test_it_returns_zero_without_enough_points(): void
    {
        Http::fake();

        $km = (new RouteDistanceService)->distanceInKm(['A'], 'car');

        $this->assertSame(0.0, $km);
        Http::assertNothingSent();
    }

    public function test_it_stores_the_first_measure_as_reference(): void
    {
        Http::fake([
            'routes.googleapis.com/*' => Http::response(['routes' => [['distanceMeters' => 4200]]]),
        ]);

        (new RouteDistanceService)->distanceInKm(['Place du Chapitre', 'Square des Martyrs 1'], 'car');

        $this->assertDatabaseHas('route_distances', [
            'origin' => 'Place du Chapitre',
            'destination' => 'Square des Martyrs 1',
            'transport' => 'car',
            'distance_meters' => 4200,
        ]);
    }

    public function test_it_reuses_the_cached_reference_without_calling_google(): void
    {
        $this->makeReference('Place du Chapitre', 'Square des Martyrs 1', 4200, now());

        Http::fake();

        $km = (new RouteDistanceService)->distanceInKm(['Place du Chapitre', 'Square des Martyrs 1'], 'car');

        $this->assertSame(4.2, $km);
        Http::assertNothingSent();
    }

    public function test_it_reuses_the_reference_whatever_the_address_casing_and_spacing(): void
    {
        $this->makeReference('Place du Chapitre, 1', 'Square des Martyrs 1', 4200, now());

        Http::fake();

        $km = (new RouteDistanceService)->distanceInKm(['  place du   chapitre ,1 ', 'SQUARE DES MARTYRS 1'], 'car');

        $this->assertSame(4.2, $km);
        Http::assertNothingSent();
    }

    public function test_it_refreshes_the_reference_when_the_gap_stays_reasonable(): void
    {
        $reference = $this->makeReference('A', 'B', 100000, now()->subDays(60));

        Http::fake([
            'routes.googleapis.com/*' => Http::response(['routes' => [['distanceMeters' => 110000]]]),
        ]);

        Notification::fake();

        $km = (new RouteDistanceService)->distanceInKm(['A', 'B'], 'car');

        $this->assertSame(110.0, $km);
        $this->assertSame(110000, $reference->fresh()->distance_meters);
        Notification::assertNothingSent();
    }

    public function test_it_keeps_the_reference_and_alerts_administrators_on_a_large_gap(): void
    {
        Notification::fake();

        $organization = Organization::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $encoder = User::factory()->create(['is_admin' => false]);
        $organization->users()->attach([$admin->id, $encoder->id]);
        setCurrentOrganization($organization);
        $this->actingAs($encoder);

        $reference = $this->makeReference('Place du Chapitre', 'Square des Martyrs 1', 10000, now()->subDays(60));

        Http::fake([
            'routes.googleapis.com/*' => Http::response(['routes' => [['distanceMeters' => 20000]]]),
        ]);

        $km = (new RouteDistanceService)->distanceInKm(['Place du Chapitre', 'Square des Martyrs 1'], 'car');

        $this->assertSame(10.0, $km);

        $reference->refresh();
        $this->assertSame(10000, $reference->distance_meters);
        $this->assertSame(20000, $reference->last_anomaly_meters);
        $this->assertNotNull($reference->last_anomaly_at);

        Notification::assertSentTo($admin, RouteDistanceAnomalyDetected::class, function ($notification) use ($reference) {
            return $notification->routeDistance->is($reference)
                && $notification->measuredMeters === 20000
                && $notification->deviationPercent() === 100.0;
        });
        Notification::assertNotSentTo($encoder, RouteDistanceAnomalyDetected::class);
    }

    public function test_it_does_not_alert_twice_before_the_next_revalidation(): void
    {
        Notification::fake();

        $organization = Organization::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $organization->users()->attach($admin->id);
        setCurrentOrganization($organization);

        $this->makeReference('A', 'B', 10000, now()->subDays(60));

        Http::fake([
            'routes.googleapis.com/*' => Http::response(['routes' => [['distanceMeters' => 20000]]]),
        ]);

        $service = new RouteDistanceService;
        $service->distanceInKm(['A', 'B'], 'car');
        $service->distanceInKm(['A', 'B'], 'car');

        Notification::assertSentToTimes($admin, RouteDistanceAnomalyDetected::class, 1);
    }

    public function test_it_falls_back_on_the_reference_when_google_is_unavailable(): void
    {
        $this->makeReference('A', 'B', 10000, now()->subDays(60));

        Http::fake([
            'routes.googleapis.com/*' => Http::response([], 500),
        ]);

        Notification::fake();

        $km = (new RouteDistanceService)->distanceInKm(['A', 'B'], 'car');

        $this->assertSame(10.0, $km);
        Notification::assertNothingSent();
    }

    public function test_it_alerts_the_administrators_of_the_organization_it_was_given(): void
    {
        Notification::fake();

        // Les routes API ne passent pas par ResolveOrganization : aucun locataire
        // n'est lié à la requête, l'organisation vient de l'appelant.
        $organization = Organization::factory()->create();
        $admin = User::factory()->create(['is_admin' => true]);
        $organization->users()->attach($admin->id);

        $otherOrganization = Organization::factory()->create();
        $otherAdmin = User::factory()->create(['is_admin' => true]);
        $otherOrganization->users()->attach($otherAdmin->id);

        $this->makeReference('A', 'B', 10000, now()->subDays(60));

        Http::fake([
            'routes.googleapis.com/*' => Http::response(['routes' => [['distanceMeters' => 20000]]]),
        ]);

        (new RouteDistanceService($organization))->distanceInKm(['A', 'B'], 'car');

        Notification::assertSentTo($admin, RouteDistanceAnomalyDetected::class);
        Notification::assertNotSentTo($otherAdmin, RouteDistanceAnomalyDetected::class);
    }

    public function test_it_alerts_nobody_when_no_organization_can_be_identified(): void
    {
        Notification::fake();

        $admin = User::factory()->create(['is_admin' => true]);
        Organization::factory()->create()->users()->attach($admin->id);

        $this->makeReference('A', 'B', 10000, now()->subDays(60));

        Http::fake([
            'routes.googleapis.com/*' => Http::response(['routes' => [['distanceMeters' => 20000]]]),
        ]);

        (new RouteDistanceService)->distanceInKm(['A', 'B'], 'car');

        Notification::assertNothingSent();
    }

    public function test_it_falls_back_on_the_reference_when_the_connection_fails(): void
    {
        $this->makeReference('A', 'B', 10000, now()->subDays(60));

        Http::fake(function () {
            throw new ConnectionException('cURL error 6: Could not resolve host');
        });

        $km = (new RouteDistanceService)->distanceInKm(['A', 'B'], 'car');

        $this->assertSame(10.0, $km);
    }

    public function test_it_skips_the_segment_when_the_connection_fails_on_a_first_measure(): void
    {
        Http::fake(function () {
            throw new ConnectionException('cURL error 6: Could not resolve host');
        });

        $km = (new RouteDistanceService)->distanceInKm(['A', 'B'], 'car');

        $this->assertSame(0.0, $km);
        $this->assertDatabaseCount('route_distances', 0);
    }

    public function test_it_yields_to_a_concurrent_first_measure_of_the_same_segment(): void
    {
        // Un autre encodage du même trajet insère la référence pendant que
        // celui-ci interroge Google : l'INSERT qui suit viole la contrainte
        // unique et doit rendre la ligne gagnante, pas une erreur.
        Http::fake(function () {
            $this->makeReference('A', 'B', 7000, now());

            return Http::response(['routes' => [['distanceMeters' => 12000]]]);
        });

        $km = (new RouteDistanceService)->distanceInKm(['A', 'B'], 'car');

        $this->assertSame(7.0, $km);
        $this->assertDatabaseCount('route_distances', 1);
    }

    public function test_the_alert_mail_details_the_gap(): void
    {
        $reference = $this->makeReference('Place du Chapitre', 'Square des Martyrs 1', 10000, now());
        $admin = User::factory()->create(['is_admin' => true]);

        $mail = (new RouteDistanceAnomalyDetected($reference, 20000, $admin))->toMail($admin);

        $this->assertSame('Distance inhabituelle sur un trajet encodé', $mail->subject);
        $this->assertTrue(collect($mail->introLines)->contains(
            fn ($line) => str_contains($line, '**Distance de référence :** 10 km')
        ));
        $this->assertTrue(collect($mail->introLines)->contains(
            fn ($line) => str_contains($line, "**Distance mesurée :** 20 km (100 % d'écart)")
        ));
    }

    private function makeReference(string $origin, string $destination, int $meters, $verifiedAt): RouteDistance
    {
        return RouteDistance::create([
            'signature' => RouteDistance::signatureFor($origin, $destination, 'car'),
            'origin' => $origin,
            'destination' => $destination,
            'transport' => 'car',
            'distance_meters' => $meters,
            'verified_at' => $verifiedAt,
            'last_used_at' => $verifiedAt,
        ]);
    }

    protected function tearDown(): void
    {
        setCurrentOrganization(null);

        parent::tearDown();
    }
}
