<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Durée de vie de la distance de référence
    |--------------------------------------------------------------------------
    |
    | Une distance déjà mesurée est réutilisée telle quelle pendant ce nombre
    | de jours : un chantier ou une déviation temporaire ne peut donc pas
    | gonfler un remboursement. Passé ce délai, le trajet est recalculé et
    | comparé à la référence.
    |
    */

    'revalidate_after_days' => env('ROUTE_DISTANCE_REVALIDATE_AFTER_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Seuils d'anomalie
    |--------------------------------------------------------------------------
    |
    | Lors d'une revalidation, un écart qui dépasse *à la fois* le pourcentage
    | et le nombre de kilomètres ci-dessous est considéré comme anormal : la
    | référence est conservée et les administrateurs sont alertés.
    |
    */

    'anomaly' => [
        'percent' => env('ROUTE_DISTANCE_ANOMALY_PERCENT', 20),
        'min_km' => env('ROUTE_DISTANCE_ANOMALY_MIN_KM', 3),
    ],

];
