<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\RouteDistance;
use App\Models\User;
use App\Notifications\RouteDistanceAnomalyDetected;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class RouteDistanceService
{
    private const ENDPOINT = 'https://routes.googleapis.com/directions/v2:computeRoutes';

    /**
     * @param  Organization|null  $organization  Organisation de la note en cours,
     *                                           destinataire des alertes. Les routes API ne passent pas par
     *                                           ResolveOrganization : sans cet argument, `currentOrganization()`
     *                                           y est nul et personne n'est alerté.
     */
    public function __construct(private ?Organization $organization = null) {}

    /**
     * Distance totale (en km, arrondie à 2 décimales) des segments successifs
     * reliant les points fournis.
     *
     * Chaque segment est mesuré à chaque encodage via l'API Google Routes sans
     * trafic temps réel (routingPreference TRAFFIC_UNAWARE), ce qui écarte déjà
     * les bouchons et les déviations signalées en direct. La mesure est ensuite
     * comparée à la dernière connue pour ce trajet : au-delà du seuil
     * configuré, la nouvelle mesure est tout de même retenue mais les
     * administrateurs sont alertés pour qu'ils puissent regarder.
     *
     * @param  array<int, string>  $points  Adresses ordonnées (départ, étapes…, arrivée)
     */
    public function distanceInKm(array $points, string $transport): float
    {
        if (count($points) < 2) {
            return 0.0;
        }

        $totalMeters = 0;

        foreach (range(0, count($points) - 2) as $i) {
            $totalMeters += $this->segmentDistanceInMeters($points[$i], $points[$i + 1], $transport);
        }

        return round($totalMeters / 1000, 2);
    }

    /**
     * Mesure d'un segment, comparée puis substituée à la dernière connue.
     */
    private function segmentDistanceInMeters(string $origin, string $destination, string $transport): int
    {
        $signature = RouteDistance::signatureFor($origin, $destination, $transport);
        $known = RouteDistance::where('signature', $signature)->first();
        $measured = $this->fetchSegmentDistanceInMeters($origin, $destination, $transport);

        if ($measured <= 0) {
            // Google injoignable ou sans itinéraire : plutôt que de compter le
            // segment pour zéro, on retient la dernière mesure connue.
            return $known?->distance_meters ?? 0;
        }

        if ($known === null) {
            return $this->storeFirstMeasure($signature, $origin, $destination, $transport, $measured);
        }

        $previous = $known->distance_meters;
        $isAnomalous = $this->isAnomalous($previous, $measured);

        $attributes = [
            'distance_meters' => $measured,
            'measured_at' => now(),
        ];

        // La trace de la dernière alerte n'est écrasée que par une nouvelle
        // alerte : un encodage normal ne l'efface pas.
        if ($isAnomalous) {
            $attributes['previous_distance_meters'] = $previous;
            $attributes['last_anomaly_at'] = now();
        }

        $known->forceFill($attributes)->save();

        if ($isAnomalous) {
            $this->alertAdministrators($known, $previous, $measured);
        }

        return $measured;
    }

    /**
     * Premier encodage de ce trajet : rien à comparer, rien à signaler.
     *
     * Deux encodages simultanés mesurent tous les deux avant d'insérer :
     * `createOrFirst()` rattrape la violation de contrainte unique et rend la
     * ligne gagnante au lieu de faire échouer l'enregistrement de la note.
     */
    private function storeFirstMeasure(string $signature, string $origin, string $destination, string $transport, int $measured): int
    {
        return RouteDistance::createOrFirst(
            ['signature' => $signature],
            [
                'origin' => $origin,
                'destination' => $destination,
                'transport' => $transport,
                'distance_meters' => $measured,
                'measured_at' => now(),
            ]
        )->distance_meters;
    }

    /**
     * Écart jugé anormal. Le plancher kilométrique est désactivé par défaut :
     * il n'existe que pour étouffer le bruit des trajets très courts, où
     * quelques centaines de mètres pèsent lourd en pourcentage.
     */
    private function isAnomalous(int $previousMeters, int $measuredMeters): bool
    {
        if ($previousMeters <= 0) {
            return false;
        }

        $gap = abs($measuredMeters - $previousMeters);

        if ($gap < (float) config('route_distance.anomaly.min_km') * 1000) {
            return false;
        }

        return $gap / $previousMeters * 100 >= (float) config('route_distance.anomaly.percent');
    }

    private function alertAdministrators(RouteDistance $routeDistance, int $previousMeters, int $measuredMeters): void
    {
        try {
            $administrators = $this->administrators();

            if ($administrators->isEmpty()) {
                return;
            }

            Notification::send(
                $administrators,
                new RouteDistanceAnomalyDetected($routeDistance, $previousMeters, $measuredMeters, auth()->user())
            );
        } catch (\Throwable $e) {
            Log::error('Impossible d\'alerter les administrateurs d\'une distance anormale', [
                'route_distance_id' => $routeDistance->id,
                'exception' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Administrateurs de l'organisation de la note — jamais d'une autre : sans
     * organisation identifiée, mieux vaut ne prévenir personne que d'envoyer le
     * trajet et le nom de l'encodeur aux administrateurs d'un autre locataire.
     *
     * @return Collection<int, User>
     */
    private function administrators(): Collection
    {
        $organization = $this->organization ?? currentOrganization();

        if ($organization === null) {
            Log::warning('Distance anormale détectée sans organisation identifiable : aucune alerte envoyée.');

            return collect();
        }

        return $organization->users()->where('is_admin', true)->get();
    }

    private function fetchSegmentDistanceInMeters(string $origin, string $destination, string $transport): int
    {
        $payload = [
            'origin' => ['address' => $origin],
            'destination' => ['address' => $destination],
            'travelMode' => $transport === 'bike' ? 'BICYCLE' : 'DRIVE',
        ];

        if ($payload['travelMode'] === 'DRIVE') {
            $payload['routingPreference'] = 'TRAFFIC_UNAWARE';
        }

        try {
            $response = Http::withHeaders([
                'X-Goog-Api-Key' => config('services.google_maps.key'),
                'X-Goog-FieldMask' => 'routes.distanceMeters',
            ])->post(self::ENDPOINT, $payload);
        } catch (ConnectionException $e) {
            // DNS, délai dépassé, connexion refusée : l'appelant se rabat sur la
            // dernière mesure connue plutôt que de faire échouer l'encodage.
            Log::warning('Appel à l\'API Google Routes impossible', [
                'origin' => $origin,
                'destination' => $destination,
                'exception' => $e->getMessage(),
            ]);

            return 0;
        }

        if ($response->successful() && isset($response->json()['routes'][0]['distanceMeters'])) {
            return (int) $response->json()['routes'][0]['distanceMeters'];
        }

        return 0;
    }
}
