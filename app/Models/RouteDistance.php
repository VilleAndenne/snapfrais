<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Distance de référence entre deux adresses, mesurée une seule fois puis
 * réutilisée. Le cache est volontairement global (pas de `organization_id`) :
 * la distance entre deux adresses ne dépend pas du locataire.
 */
class RouteDistance extends Model
{
    protected $fillable = [
        'signature',
        'origin',
        'destination',
        'transport',
        'distance_meters',
        'last_anomaly_meters',
        'last_anomaly_at',
        'verified_at',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'distance_meters' => 'integer',
            'last_anomaly_meters' => 'integer',
            'last_anomaly_at' => 'datetime',
            'verified_at' => 'datetime',
            'last_used_at' => 'datetime',
        ];
    }

    /**
     * Clé stable d'un segment : adresses normalisées + mode de transport.
     */
    public static function signatureFor(string $origin, string $destination, string $transport): string
    {
        return hash('sha256', implode('|', [
            self::normalizeAddress($origin),
            self::normalizeAddress($destination),
            $transport,
        ]));
    }

    /**
     * Normalise une adresse pour que « Place du Chapitre, 1 » et
     * « place du chapitre,1 » partagent la même référence.
     */
    public static function normalizeAddress(string $address): string
    {
        $address = mb_strtolower(trim($address));
        $address = preg_replace('/\s*,\s*/', ', ', $address);

        return preg_replace('/\s+/', ' ', $address);
    }

    /**
     * La référence doit-elle être recontrôlée auprès de Google ?
     */
    public function needsRevalidation(): bool
    {
        $days = (int) config('route_distance.revalidate_after_days');

        if ($days <= 0) {
            return true;
        }

        return $this->verified_at === null
            || $this->verified_at->lessThan(Carbon::now()->subDays($days));
    }
}
