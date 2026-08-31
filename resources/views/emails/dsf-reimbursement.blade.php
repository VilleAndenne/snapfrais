@php
    use Carbon\Carbon;

    $organization = $expenseSheet->organization;
    $organizationName = $organization?->organization_name
        ?: ($organization?->name ?: config('app.name'));

    $total = $dsfCosts->sum('total');
    $validatedAt = $expenseSheet->validated_at
        ? Carbon::parse($expenseSheet->validated_at)->locale('fr_BE')->translatedFormat('d/m/Y à H:i')
        : null;

    $label = 'padding: 10px 0; color: #5b6470; font-size: 14px; vertical-align: top; width: 40%;';
    $value = 'padding: 10px 0; color: #1c2430; font-size: 14px; vertical-align: top; font-weight: 600;';
    $cell = 'padding: 12px 16px; font-size: 14px; color: #1c2430; border-bottom: 1px solid #e3e6ea;';
@endphp
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de remboursement — Direction des Services Financiers</title>
</head>
<body style="margin: 0; padding: 0; background-color: #eef0f3;">

<span style="display: none; max-height: 0; overflow: hidden; opacity: 0;">
    Note de frais #{{ $expenseSheet->id }} — {{ number_format($total, 2, ',', ' ') }} € à rembourser à {{ $expenseSheet->user->name ?? 'l\'agent' }}.
</span>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #eef0f3; padding: 32px 16px;">
    <tr>
        <td align="center">
            <table role="presentation" width="640" cellpadding="0" cellspacing="0" style="max-width: 640px; width: 100%; background-color: #ffffff; border: 1px solid #dfe3e8;">

                <tr>
                    <td style="background-color: #1c2430; padding: 24px 32px;">
                        <p style="margin: 0; color: #ffffff; font-family: Georgia, 'Times New Roman', serif; font-size: 19px; letter-spacing: 0.3px;">
                            {{ $organizationName }}
                        </p>
                        <p style="margin: 4px 0 0 0; color: #9aa4b1; font-family: Arial, Helvetica, sans-serif; font-size: 12px; text-transform: uppercase; letter-spacing: 1.2px;">
                            Direction des Services Financiers
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="padding: 32px 32px 0 32px; font-family: Arial, Helvetica, sans-serif;">
                        <p style="margin: 0 0 24px 0; font-size: 15px; color: #1c2430;">
                            <strong>Objet :</strong> demande de remboursement de frais — note n° {{ $expenseSheet->id }}
                        </p>

                        <p style="margin: 0 0 16px 0; font-size: 15px; color: #1c2430; line-height: 1.6;">
                            Madame, Monsieur,
                        </p>

                        <p style="margin: 0 0 24px 0; font-size: 15px; color: #1c2430; line-height: 1.6;">
                            Une note de frais comportant des coûts relevant de votre service a été approuvée
                            @if ($validatedAt) le {{ $validatedAt }} @endif
                            et vous est transmise pour traitement. Le détail figure ci-dessous ; la demande complète
                            et ses pièces justificatives sont réunies dans le document joint.
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="padding: 0 32px; font-family: Arial, Helvetica, sans-serif;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top: 2px solid #1c2430; border-bottom: 1px solid #dfe3e8;">
                            <tr>
                                <td colspan="2" style="padding: 14px 0 6px 0;">
                                    <p style="margin: 0; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #5b6470;">
                                        Identification de la demande
                                    </p>
                                </td>
                            </tr>
                            <tr>
                                <td style="{{ $label }}">Note de frais</td>
                                <td style="{{ $value }}">n° {{ $expenseSheet->id }}</td>
                            </tr>
                            <tr>
                                <td style="{{ $label }}">Agent bénéficiaire</td>
                                <td style="{{ $value }}">
                                    {{ $expenseSheet->user->name ?? '—' }}
                                    @if ($expenseSheet->user?->email)
                                        <br><span style="font-weight: 400; color: #5b6470;">{{ $expenseSheet->user->email }}</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td style="{{ $label }}">Service</td>
                                <td style="{{ $value }}">{{ $expenseSheet->department->name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td style="{{ $label }}">Approuvée par</td>
                                <td style="{{ $value }}">{{ $expenseSheet->validatedBy->name ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td style="{{ $label }}">Date d'approbation</td>
                                <td style="{{ $value }}">{{ $validatedAt ?? '—' }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding: 28px 32px 0 32px; font-family: Arial, Helvetica, sans-serif;">
                        <p style="margin: 0 0 10px 0; font-size: 12px; text-transform: uppercase; letter-spacing: 1px; color: #5b6470;">
                            Détail des montants
                        </p>

                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse: collapse; border: 1px solid #e3e6ea;">
                            <thead>
                                <tr style="background-color: #f5f6f8;">
                                    <th align="left" style="padding: 10px 16px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.6px; color: #5b6470; border-bottom: 1px solid #e3e6ea;">Objet du frais</th>
                                    <th align="right" style="padding: 10px 16px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.6px; color: #5b6470; border-bottom: 1px solid #e3e6ea;">Montant</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($dsfCosts as $cost)
                                    <tr>
                                        <td style="{{ $cell }}">
                                            {{ $cost->formCost->name ?? '—' }}
                                            @if ($cost->date)
                                                <br><span style="color: #5b6470; font-size: 13px;">Exposé le {{ Carbon::parse($cost->date)->format('d/m/Y') }}</span>
                                            @endif
                                        </td>
                                        <td align="right" style="{{ $cell }} white-space: nowrap;">
                                            {{ number_format($cost->total, 2, ',', ' ') }} €
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr style="background-color: #1c2430;">
                                    <td align="right" style="padding: 12px 16px; font-size: 13px; text-transform: uppercase; letter-spacing: 0.6px; color: #ffffff;">
                                        Total à rembourser
                                    </td>
                                    <td align="right" style="padding: 12px 16px; font-size: 16px; font-weight: bold; color: #ffffff; white-space: nowrap;">
                                        {{ number_format($total, 2, ',', ' ') }} €
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding: 28px 32px 0 32px; font-family: Arial, Helvetica, sans-serif;">
                        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f5f6f8; border-left: 3px solid #1c2430;">
                            <tr>
                                <td style="padding: 16px 20px;">
                                    <p style="margin: 0 0 6px 0; font-size: 14px; font-weight: bold; color: #1c2430;">
                                        Pièce jointe — demande de remboursement (PDF)
                                    </p>
                                    <p style="margin: 0; font-size: 14px; color: #3d4753; line-height: 1.6;">
                                        @php
                                            $plural = ($attachmentCount ?? 0) > 1 ? 's' : '';

                                            $piecesJointes = ($attachmentCount ?? 0) > 0
                                                ? 'ainsi que '.$attachmentCount.' pièce'.$plural.' justificative'.$plural
                                                    .(($convertedCount ?? 0) > 0
                                                        ? ' (dont '.$convertedCount.' convertie'.(($convertedCount > 1) ? 's' : '').' au format PDF)'
                                                        : '')
                                                : '; aucune pièce justificative n\'était jointe à cette note';
                                        @endphp
                                        {{ 'Un document unique regroupant la demande détaillée '.$piecesJointes.'.' }}
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td style="padding: 28px 32px 32px 32px; font-family: Arial, Helvetica, sans-serif;">
                        <p style="margin: 0 0 4px 0; font-size: 15px; color: #1c2430; line-height: 1.6;">
                            Nous vous prions d'agréer, Madame, Monsieur, l'expression de nos salutations distinguées.
                        </p>
                        <p style="margin: 16px 0 0 0; font-size: 15px; color: #1c2430;">
                            {{ $organizationName }}
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="background-color: #f5f6f8; border-top: 1px solid #dfe3e8; padding: 18px 32px; font-family: Arial, Helvetica, sans-serif;">
                        <p style="margin: 0; font-size: 12px; color: #5b6470; line-height: 1.6;">
                            {{ $organizationName }} — message généré automatiquement par SnapFrais, application de
                            gestion des notes de frais. Merci de ne pas répondre à cet envoi ; pour toute question
                            relative à cette demande, veuillez contacter le service émetteur.
                        </p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>

</body>
</html>
