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
     * Chaque segment est mesuré une seule fois via l'API Google Routes sans
     * trafic temps réel (routingPreference TRAFFIC_UNAWARE), puis conservé
     * comme distance de référence. Les encodages suivants réutilisent cette
     * référence : un chantier ou une déviation ponctuelle ne peut donc pas
     * gonfler un remboursement. La référence n'est recontrôlée qu'après le
     * délai configuré, et un écart important alerte les administrateurs sans
     * modifier la distance retenue.
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
            $totalMeters += $this->referenceDistanceInMeters($points[$i], $points[$i + 1], $transport);
        }

        return round($totalMeters / 1000, 2);
    }

    /**
     * Distance de référence d'un segment, mesurée puis mise en cache.
     */
    private function referenceDistanceInMeters(string $origin, string $destination, string $transport): int
    {
        $signature = RouteDistance::signatureFor($origin, $destination, $transport);
        $reference = RouteDistance::where('signature', $signature)->first();

        if ($reference === null) {
            return $this->createReference($signature, $origin, $destination, $transport);
        }

        if (! $reference->needsRevalidation()) {
            $reference->forceFill(['last_used_at' => now()])->save();

            return $reference->distance_meters;
        }

        $measured = $this->fetchSegmentDistanceInMeters($origin, $destination, $transport);

        if ($measured <= 0) {
            $reference->forceFill(['last_used_at' => now()])->save();

            return $reference->distance_meters;
        }

        if ($this->isAnomalous($reference->distance_meters, $measured)) {
            $reference->forceFill([
                'last_anomaly_meters' => $measured,
                'last_anomaly_at' => now(),
                'verified_at' => now(),
                'last_used_at' => now(),
            ])->save();

            $this->alertAdministrators($reference, $measured);

            return $reference->distance_meters;
        }

        $reference->forceFill([
            'distance_meters' => $measured,
            'verified_at' => now(),
            'last_used_at' => now(),
        ])->save();

        return $measured;
    }

    /**
     * Première mesure d'un segment encore inconnu.
     *
     * Deux encodages simultanés du même trajet mesurent tous les deux avant
     * d'insérer : `createOrFirst()` rattrape la violation de contrainte unique
     * et rend la ligne gagnante au lieu de faire échouer l'enregistrement de
     * la note.
     */
    private function createReference(string $signature, string $origin, string $destination, string $transport): int
    {
        $measured = $this->fetchSegmentDistanceInMeters($origin, $destination, $transport);

        if ($measured <= 0) {
            return 0;
        }

        return RouteDistance::createOrFirst(
            ['signature' => $signature],
            [
                'origin' => $origin,
                'destination' => $destination,
                'transport' => $transport,
                'distance_meters' => $measured,
                'verified_at' => now(),
                'last_used_at' => now(),
            ]
        )->distance_meters;
    }

    /**
     * Un écart n'est anormal que s'il dépasse à la fois le seuil relatif et le
     * seuil absolu : sur un court trajet, quelques centaines de mètres pèsent
     * beaucoup en pourcentage sans rien changer au remboursement.
     */
    private function isAnomalous(int $referenceMeters, int $measuredMeters): bool
    {
        if ($referenceMeters <= 0) {
            return false;
        }

        $gap = abs($measuredMeters - $referenceMeters);

        if ($gap < (float) config('route_distance.anomaly.min_km') * 1000) {
            return false;
        }

        return $gap / $referenceMeters * 100 >= (float) config('route_distance.anomaly.percent');
    }

    private function alertAdministrators(RouteDistance $reference, int $measuredMeters): void
    {
        try {
            $administrators = $this->administrators();

            if ($administrators->isEmpty()) {
                return;
            }

            Notification::send(
                $administrators,
                new RouteDistanceAnomalyDetected($reference, $measuredMeters, auth()->user())
            );
        } catch (\Throwable $e) {
            Log::error('Impossible d\'alerter les administrateurs d\'une distance anormale', [
                'route_distance_id' => $reference->id,
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
            // référence enregistrée plutôt que de faire échouer l'encodage.
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
