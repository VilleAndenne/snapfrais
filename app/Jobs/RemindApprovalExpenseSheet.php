<?php

namespace App\Jobs;

use App\Notifications\RemindApprovalExpenseSheetNotification as Reminder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
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
        $potentialValidators = \App\Models\User::whereHas('departments', function ($query) {
            $query->where('is_head', true);
        })->get();

        Log::info("Nombre de validateurs potentiels trouvés : {$potentialValidators->count()}");

        // Pour chaque validateur, compter combien de notes il peut valider
        foreach ($potentialValidators as $validator) {
            // Récupérer les notes candidates pour ce validateur (même logique que DashboardController)
            $candidateSheets = \App\Models\ExpenseSheet::with([
                'form',
                'department.heads',
                'department.parent.heads',
                'user',
            ])
                ->where(function ($q) use ($validator) {
                    $q->whereHas('department.heads', function ($h) use ($validator) {
                        $h->where('users.id', $validator->id);
                    })
                        ->orWhereHas('department.parent.heads', function ($h) use ($validator) {
                            $h->where('users.id', $validator->id);
                        });
                })
                ->get();

            // Filtrer avec la même logique que le dashboard (Policy shouldAppearInValidationList)
            $sheetsToValidate = $candidateSheets->filter(function ($sheet) use ($validator) {
                return \Illuminate\Support\Facades\Gate::forUser($validator)->allows('shouldAppearInValidationList', $sheet);
            });

            $count = $sheetsToValidate->count();

            // N'envoyer la notification que si au moins une note à valider
            if ($count > 0) {
                $validator->notify(new Reminder($validator, $count));
                Log::info("Notification envoyée à {$validator->name} pour {$count} note(s) de frais.");
            } else {
                Log::info("Aucune notification envoyée à {$validator->name} : 0 note de frais à valider.");
            }
        }

        Log::info('RemindApprovalExpenseSheet job completed.');
    }
}
