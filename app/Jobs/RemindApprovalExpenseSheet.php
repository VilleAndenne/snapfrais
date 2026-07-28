<?php

namespace App\Jobs;

use App\Models\ExpenseSheet;
use App\Models\User;
use App\Notifications\RemindApprovalExpenseSheetNotification as Reminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class RemindApprovalExpenseSheet implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        Log::info('RemindApprovalExpenseSheet job dispatched.');
    }

    public function handle(): void
    {
        Log::info('RemindApprovalExpenseSheet job started.');

        // Récupérer tous les validateurs potentiels (users qui sont heads d'au moins un département)
        $potentialValidators = User::whereHas('departments', function ($query) {
            $query->where('is_head', true);
        })->get();

        Log::info("Nombre de validateurs potentiels trouvés : {$potentialValidators->count()}");

        // Pour chaque validateur, compter combien de notes il peut valider
        foreach ($potentialValidators as $validator) {
            // Récupérer les notes candidates pour ce validateur (même logique que DashboardController)
            $candidateSheets = ExpenseSheet::query()
                ->withValidationRelations()
                ->pendingValidationBy($validator)
                ->get();

            // Filtrer avec la même logique que le dashboard (Policy shouldAppearInValidationList)
            $sheetsToValidate = $candidateSheets->filter(function ($sheet) use ($validator) {
                return Gate::forUser($validator)->allows('shouldAppearInValidationList', $sheet);
            });

            $count = $sheetsToValidate->count();

            // N'envoyer la notification que si au moins une note à valider.
            // On regroupe par organisation pour que chaque rappel pointe vers
            // le dashboard de la bonne organisation (lien construit hors requête).
            if ($count > 0) {
                $sheetsToValidate
                    ->groupBy(fn ($sheet) => $sheet->organization_id)
                    ->each(function ($sheets) use ($validator) {
                        $validator->notify(new Reminder($validator, $sheets->count(), $sheets->first()->organization));
                    });
                Log::info("Notification envoyée à {$validator->name} pour {$count} note(s) de frais.");
            } else {
                Log::info("Aucune notification envoyée à {$validator->name} : 0 note de frais à valider.");
            }
        }

        Log::info('RemindApprovalExpenseSheet job completed.');
    }
}
