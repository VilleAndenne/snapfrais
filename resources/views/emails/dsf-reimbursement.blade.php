<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de remboursement DSF</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
        <h2 style="color: #2563eb; border-bottom: 2px solid #2563eb; padding-bottom: 10px;">
            Nouvelle demande de remboursement - Direction des Services Financiers
        </h2>

        <p>Bonjour,</p>

        <p>Une nouvelle demande de remboursement a été validée et nécessite votre traitement.</p>

        <div style="background-color: #f3f4f6; padding: 15px; border-radius: 5px; margin: 20px 0;">
            <h3 style="margin-top: 0;">Détails de la demande</h3>
            <ul style="list-style: none; padding: 0;">
                <li><strong>Numéro de note de frais :</strong> #{{ $expenseSheet->id }}</li>
                <li><strong>Agent :</strong> {{ $expenseSheet->user->name ?? '-' }} ({{ $expenseSheet->user->email ?? '-' }})</li>
                <li><strong>Service :</strong> {{ $expenseSheet->department->name ?? '-' }}</li>
                <li><strong>Validée par :</strong> {{ $expenseSheet->validatedBy->name ?? '-' }}</li>
                <li><strong>Date de validation :</strong> {{ $expenseSheet->validated_at ? \Carbon\Carbon::parse($expenseSheet->validated_at)->locale('fr_BE')->translatedFormat('d/m/Y à H:i') : '-' }}</li>
                <li><strong>Montant total :</strong> {{ number_format($dsfCosts->sum('total'), 2, ',', ' ') }} €</li>
                <li><strong>Nombre de coûts :</strong> {{ $dsfCosts->count() }}</li>
            </ul>
        </div>

        <h3>Coûts à rembourser</h3>
        <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
            <thead>
                <tr style="background-color: #e5e7eb;">
                    <th style="border: 1px solid #d1d5db; padding: 8px; text-align: left;">Description</th>
                    <th style="border: 1px solid #d1d5db; padding: 8px; text-align: right;">Montant</th>
                </tr>
            </thead>
            <tbody>
                @foreach($dsfCosts as $cost)
                <tr>
                    <td style="border: 1px solid #d1d5db; padding: 8px;">
                        {{ $cost->formCost->name ?? '-' }}
                        <br>
                        <small style="color: #6b7280;">{{ \Carbon\Carbon::parse($cost->date)->format('d/m/Y') }}</small>
                    </td>
                    <td style="border: 1px solid #d1d5db; padding: 8px; text-align: right;">
                        {{ number_format($cost->total, 2, ',', ' ') }} €
                    </td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr style="background-color: #f9fafb; font-weight: bold;">
                    <td style="border: 1px solid #d1d5db; padding: 8px; text-align: right;">Total :</td>
                    <td style="border: 1px solid #d1d5db; padding: 8px; text-align: right;">
                        {{ number_format($dsfCosts->sum('total'), 2, ',', ' ') }} €
                    </td>
                </tr>
            </tfoot>
        </table>

        <p style="margin-top: 30px;">
            <strong>Document joint :</strong>
        </p>
        <div style="background-color: #f9fafb; padding: 15px; border-radius: 5px; border-left: 4px solid #2563eb;">
            <p style="margin: 0; font-weight: bold;">📄 Demande de remboursement complète (PDF)</p>
            <p style="margin: 5px 0 0 0; font-size: 14px; color: #6b7280;">
                Ce PDF contient :
            </p>
            <ul style="margin: 10px 0; padding-left: 20px; color: #6b7280; font-size: 14px;">
                <li>La demande de remboursement détaillée</li>
                @if(isset($attachmentCount) && $attachmentCount > 0)
                    <li>{{ $attachmentCount }} pièce(s) justificative(s) fusionnée(s) (PDF, images, documents Word)</li>
                @endif
                @if(isset($convertedCount) && $convertedCount > 0)
                    <li>{{ $convertedCount }} fichier(s) converti(s) automatiquement en PDF</li>
                @endif
            </ul>
            <p style="margin: 10px 0 0 0; font-size: 13px; color: #059669; font-weight: bold;">
                ✓ Toutes les pièces justificatives sont incluses dans ce document unique
            </p>
        </div>

        <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 30px 0;">

        <p style="color: #6b7280; font-size: 12px;">
            Cet email a été généré automatiquement par le système de gestion des notes de frais de
            {{ $expenseSheet->organization?->organization_name ?? $expenseSheet->organization?->name ?? config('app.name') }}.
            <br>
            Pour toute question, veuillez contacter le service concerné.
        </p>
    </div>
</body>
</html>
