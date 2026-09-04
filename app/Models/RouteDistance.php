<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Dernière distance mesurée entre deux adresses, conservée pour servir de
 * point de comparaison au prochain encodage du même trajet. Ce n'est pas une
 * référence figée : chaque nouvelle mesure remplace la précédente.
 *
 * La table est volontairement globale (pas de `organization_id`) : la distance
 * entre deux adresses ne dépend pas du locataire.
 */
class RouteDistance extends Model
{
    protected $fillable = [
        'signature',
        'origin',
        'destination',
        'transport',
        'distance_meters',
        'previous_distance_meters',
        'last_anomaly_at',
        'measured_at',
    ];

    protected function casts(): array
    {
        return [
            'distance_meters' => 'integer',
            'previous_distance_meters' => 'integer',
            'last_anomaly_at' => 'datetime',
            'measured_at' => 'datetime',
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
     * « place du chapitre,1 » soient reconnues comme un même point.
     */
    public static function normalizeAddress(string $address): string
    {
        $address = mb_strtolower(trim($address));
        $address = preg_replace('/\s*,\s*/', ', ', $address);

        return preg_replace('/\s+/', ' ', $address);
    }
}
