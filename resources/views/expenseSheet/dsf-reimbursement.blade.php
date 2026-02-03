@php
    use Carbon\Carbon;

    $sheet = $expenseSheet;

    function fr_date($d) {
        return $d ? Carbon::parse($d)->locale('fr_BE')->translatedFormat('d/m/Y') : '';
    }
    function money_eur($v) {
        return number_format((float)$v, 2, ',', ' ') . ' €';
    }
    function safe_array($value) {
        if (is_array($value)) return $value;
        if (is_object($value)) return (array)$value;
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }
        return [];
    }

    // Calculer les totaux uniquement pour les coûts DSF
    $totalDsf = (float) $dsfCosts->sum('total');
@endphp

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Demande de remboursement DSF #{{ $sheet->id }}</title>
    <style>
        @page {
            margin: 28mm 18mm 22mm 18mm;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 12px;
            color: #222;
        }

        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
        }

        .brand {
            font-size: 18px;
            font-weight: 700;
        }

        .muted {
            color: #666;
        }

        .badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 11px;
            border: 1px solid #bbb;
        }

        .status {
            font-weight: 700;
        }

        h1 {
            font-size: 20px;
            margin: 0 0 6px 0;
        }

        h2 {
            font-size: 15px;
            margin: 16px 0 8px 0;
            border-bottom: 1px solid #ddd;
            padding-bottom: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            padding: 8px;
            vertical-align: top;
        }

        th {
            background: #f5f5f5;
            text-align: left;
        }

        .table-bordered th, .table-bordered td {
            border: 1px solid #ddd;
        }

        .small {
            font-size: 11px;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .mb-4 {
            margin-bottom: 16px;
        }

        .mb-6 {
            margin-bottom: 24px;
        }

        .grid-2 {
            width: 100%;
            display: table;
            table-layout: fixed;
        }

        .col {
            display: table-cell;
            vertical-align: top;
        }

        .col + .col {
            padding-left: 18px;
        }

        .box {
            border: 1px solid #e5e5e5;
            border-radius: 6px;
            padding: 10px;
        }

        .break-inside-avoid {
            page-break-inside: avoid;
        }

        .footer {
            position: fixed;
            bottom: -10mm;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #777;
        }

        .waterline {
            border-top: 1px solid #e5e5e5;
            margin-top: 6px;
            padding-top: 6px;
        }

        .sign {
            height: 70px;
            border: 1px dashed #bbb;
            border-radius: 6px;
        }

        .nowrap {
            white-space: nowrap;
        }

        .w-25 {
            width: 25%;
        }

        .w-50 {
            width: 50%;
        }
    </style>
</head>
<body>

<!-- Header -->
<div class="header">
    <div class="brand">
        Ville d'Andenne<br>
        <span class="muted small">Demande de remboursement DSF</span>
    </div>
    <div class="right">
        <div class="badge">Demande #{{ $sheet->id }}</div>
        <br>
        <span class="small muted">Généré le {{ fr_date(now()) }}</span>
    </div>
</div>

<!-- Titre / métadonnées -->
<h1 class="mb-4">Demande de remboursement — Direction des Services Financiers</h1>

<div class="grid-2 mb-6">
    <div class="col">
        <div class="box">
            <table>
                <tr>
                    <td class="w-25 muted">Agent</td>
                    <td>{{ $sheet->user->name ?? '-' }} ({{ $sheet->user->email ?? '-' }})</td>
                </tr>
                @if($sheet->user_id !== $sheet->creator_id && !empty($sheet->created_by))
                <tr>
                    <td class="w-25 muted">Encodeur</td>
                    <td>{{ $sheet->creator->name ?? '-' }} ({{ $sheet->creator->email ?? '-' }})</td>
                </tr>
                @endif
                <tr>
                    <td class="muted">Service</td>
                    <td>{{ $sheet->department->name ?? '-' }}</td>
                </tr>
                <tr>
                    <td class="muted">Créée le</td>
                    <td>{{ fr_date($sheet->created_at) }}</td>
                </tr>
                <tr>
                    <td class="muted">Validée le</td>
                    <td>{{ fr_date($sheet->validated_at) }}</td>
                </tr>
                <tr>
                    <td class="muted">Validée par</td>
                    <td>{{ $sheet->validatedBy->name ?? '-' }}</td>
                </tr>
            </table>
        </div>
    </div>
    <div class="col">
        <div class="box">
            <table>
                <tr>
                    <td class="w-25 muted">Montant total à rembourser</td>
                    <td class="right"><strong>{{ money_eur($totalDsf) }}</strong></td>
                </tr>
                <tr>
                    <td class="muted">Nombre de coûts</td>
                    <td class="right"><strong>{{ $dsfCosts->count() }}</strong></td>
                </tr>
            </table>
        </div>
    </div>
</div>

<!-- Détail des coûts DSF -->
<h2>Détails des frais à rembourser</h2>

<table class="table-bordered small">
    <thead>
    <tr>
        <th class="nowrap">Date</th>
        <th>Nom du coût</th>
        <th class="nowrap">Type</th>
        <th>Détails / Justificatifs</th>
        <th class="right nowrap">Montant / Base</th>
        <th class="right nowrap">Total</th>
    </tr>
    </thead>
    <tbody>
    @foreach($dsfCosts as $cost)
        @php
            $route = safe_array($cost->route);
            $reqs = safe_array($cost->requirements);
            $type = $cost->type;
            $formCost = $cost->formCost ?? null;

            $rate = null;
            if ($formCost && $formCost->reimbursementRates) {
                $rate = $formCost->reimbursementRates
                    ->sortByDesc('start_date')
                    ->first(function ($r) use ($cost) {
                        $d = $cost->date;
                        return $r->start_date <= $d && (empty($r->end_date) || $r->end_date >= $d);
                    });
            }
        @endphp
        <tr class="break-inside-avoid">
            <td class="nowrap">{{ fr_date($cost->date) }}</td>
            <td>
                <strong>{{ $formCost->name ?? '-' }}</strong>
                @if(!empty($formCost->description))
                    <div class="muted small">{{ $formCost->description }}</div>
                @endif
            </td>
            <td class="nowrap">{{ strtoupper($type) }}</td>
            <td>
                @if($type === 'km')
                    <div><strong>Départ :</strong> {{ $route['departure'] ?? '-' }}</div>
                    <div><strong>Arrivée :</strong> {{ $route['arrival'] ?? '-' }}</div>
                    @if(!empty($route['steps']) && is_array($route['steps']))
                        <div><strong>Étapes :</strong></div>
                        <ul class="small" style="margin:4px 0 0 16px;">
                            @foreach($route['steps'] as $s)
                                <li>{{ is_array($s) ? ($s['address'] ?? '-') : $s }}</li>
                            @endforeach
                        </ul>
                    @endif
                    <div class="small waterline">
                        <span>Distance : {{ number_format((float)$cost->google_distance, 2, ',', ' ') }} km</span>
                    </div>
                    @if(!empty($route['justification']))
                        <div class="small"><em>Justification : {{ $route['justification'] }}</em></div>
                    @endif
                @elseif($type === 'fixed')
                    <div>Montant forfaitaire</div>
                @elseif($type === 'percentage')
                    <div>Base déclarée : {{ money_eur($cost->amount) }}</div>
                    @if($rate)
                        <div class="small muted">Taux : {{ number_format($rate->value, 2, ',', ' ') }} %</div>
                    @endif
                @endif

                @if(!empty($reqs))
                    <div class="waterline">
                        <strong>Pièces justificatives :</strong>
                        <ul class="small" style="margin:4px 0 0 16px;">
                            @foreach($reqs as $key => $item)
                                @php $file = $item['file'] ?? null; $val = $item['value'] ?? null; @endphp
                                @if($file)
                                    <li>{{ ucfirst(str_replace('_',' ', $key)) }} :
                                        <span class="muted">{{ basename($file) }}</span>
                                    </li>
                                @elseif($val)
                                    <li>{{ ucfirst(str_replace('_',' ', $key)) }} : {{ $val }}</li>
                                @endif
                            @endforeach
                        </ul>
                    </div>
                @endif
            </td>

            <td class="right">
                @if($type === 'km')
                    @if($rate)
                        <div>{{ number_format((float)$rate->value, 2, ',', ' ') }} €/km</div>
                    @else
                        <div class="muted">Taux non trouvé</div>
                    @endif
                @elseif($type === 'fixed')
                    @if($rate)
                        <div>{{ money_eur($rate->value) }}</div>
                    @endif
                @elseif($type === 'percentage')
                    @if($rate)
                        <div>{{ number_format((float)$rate->value, 2, ',', ' ') }} %</div>
                    @endif
                @else
                    —
                @endif
            </td>
            <td class="right"><strong>{{ money_eur($cost->total) }}</strong></td>
        </tr>
    @endforeach
    </tbody>
    <tfoot>
    <tr>
        <th colspan="5" class="right">Total à rembourser</th>
        <th class="right">{{ money_eur($totalDsf) }}</th>
    </tr>
    </tfoot>
</table>

<!-- Signatures -->
<h2>Visa et approbations</h2>
<table class="table-bordered">
    <tr>
        <th class="w-50">Agent demandeur</th>
        <th class="w-50">Validation hiérarchique</th>
    </tr>
    <tr>
        <td>
            <div class="small muted">Nom</div>
            <div>{{ $sheet->user->name ?? '-' }}</div>
            <div class="small muted">Date</div>
            <div>{{ fr_date($sheet->created_at) }}</div>
            <div class="sign"></div>
        </td>
        <td>
            <div class="small muted">Validé par</div>
            <div>{{ $sheet->validatedBy->name ?? '—' }}</div>
            <div class="small muted">Date</div>
            <div>{{ fr_date($sheet->validated_at) ?: '—' }}</div>
            <div class="sign"></div>
        </td>
    </tr>
</table>

@php
    // Note : Toutes les pièces justificatives (images, PDFs, documents Word) sont
    // automatiquement converties en PDF et fusionnées dans les pages suivantes
@endphp

<div class="footer">
    Demande de remboursement DSF #{{ $sheet->id }} — {{ $sheet->department->name ?? 'Service' }} — {{ fr_date(now()) }}
</div>

</body>
</html>
