<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Seuil d'alerte
    |--------------------------------------------------------------------------
    |
    | Chaque encodage remesure le trajet et le compare à la dernière mesure
    | connue. Au-delà de cet écart, la nouvelle mesure est retenue malgré tout
    | mais les administrateurs sont alertés.
    |
    */

    'anomaly' => [
        'percent' => env('ROUTE_DISTANCE_ANOMALY_PERCENT', 20),

        /*
        | Plancher facultatif, désactivé par défaut : sur un trajet de deux
        | kilomètres, trois cents mètres pèsent plus de 20 % sans rien changer
        | au remboursement. À relever si les alertes deviennent bruyantes.
        */
        'min_km' => env('ROUTE_DISTANCE_ANOMALY_MIN_KM', 0),
    ],

];
