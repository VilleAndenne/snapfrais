<?php

use App\Models\Organization;
use Carbon\Carbon;

if (! function_exists('currentOrganization')) {
    /**
     * Retrieve the organization resolved for the current request, if any.
     */
    function currentOrganization(): ?Organization
    {
        return app()->bound('tenant.organization')
            ? app('tenant.organization')
            : null;
    }
}

if (! function_exists('setCurrentOrganization')) {
    /**
     * Bind (or clear) the organization for the current request lifecycle.
     */
    function setCurrentOrganization(?Organization $organization): void
    {
        if ($organization === null) {
            app()->forgetInstance('tenant.organization');

            return;
        }

        app()->instance('tenant.organization', $organization);
    }
}

if (! function_exists('fr_date')) {
    /**
     * Date au format belge francophone, utilisée par les gabarits PDF.
     */
    function fr_date(mixed $date): string
    {
        return $date ? Carbon::parse($date)->locale('fr_BE')->translatedFormat('d/m/Y') : '';
    }
}

if (! function_exists('money_eur')) {
    /**
     * Montant en euros, séparateurs belges.
     */
    function money_eur(mixed $amount): string
    {
        return number_format((float) $amount, 2, ',', ' ').' €';
    }
}

if (! function_exists('iban_format')) {
    /**
     * IBAN présenté par blocs de quatre caractères, plus lisible pour l'encodage
     * d'un virement que la forme canonique stockée.
     */
    function iban_format(?string $iban): string
    {
        return $iban ? trim(chunk_split(str_replace(' ', '', $iban), 4, ' ')) : '';
    }
}

if (! function_exists('safe_array')) {
    /**
     * Normalise en tableau une valeur qui peut arriver sous forme de tableau,
     * d'objet ou de chaîne JSON, selon la provenance de la donnée.
     *
     * @return array<mixed>
     */
    function safe_array(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_object($value)) {
            return (array) $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
