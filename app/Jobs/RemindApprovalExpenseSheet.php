<?php

namespace App\Jobs;

use App\Models\Department;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Notifications\RemindApprovalExpenseSheetNotification as Reminder;

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

        // Collecter les notes par validateur potentiel
        $validatorSheets = collect();

        $departments = Department::with([
            'heads',
            'expenseSheets' => function ($query) {
                $query->whereNull('validated_at');
            },
            'expenseSheets.user',
            'parent.heads'
        ])->get();

        foreach ($departments as $dept) {
            foreach ($dept->expenseSheets as $sheet) {
                $sheetAuthor = $sheet->user;
                $localHeads = $dept->heads;
                $sheetAssigned = false;

                foreach ($localHeads as $localHead) {
                    // Cas 1 : le validateur essaie de valider sa propre note
                    if ($sheetAuthor->id === $localHead->id) {
                        continue;
                    }

                    // Cas 2 : le validateur est responsable du service ET l'auteur aussi
                    if ($localHeads->contains($sheetAuthor)) {
                        continue;
                    }

                    // Cas 3 : le validateur peut valider la note de l'auteur
                    if (!$validatorSheets->has($localHead->id)) {
                        $validatorSheets->put($localHead->id, collect());
                    }
                    $validatorSheets->get($localHead->id)->push($sheet);
                    $sheetAssigned = true;
                }

                // Cas 4 : si aucun head local ne peut valider, on notifie le parent
                if (!$sheetAssigned && $dept->parent && $dept->parent->heads->isNotEmpty()) {
                    foreach ($dept->parent->heads as $parentHead) {
                        if ($sheetAuthor->id !== $parentHead->id) {
                            if (!$validatorSheets->has($parentHead->id)) {
                                $validatorSheets->put($parentHead->id, collect());
                            }
                            $validatorSheets->get($parentHead->id)->push($sheet);
                        }
                    }
                }
            }
        }

        // Envoyer une seule notification par validateur avec le bon nombre de notes
        foreach ($validatorSheets as $validatorId => $sheets) {
            $validator = \App\Models\User::find($validatorId);
            if ($validator && $sheets->count() > 0) {
                $validator->notify(new Reminder($validator, $sheets->count()));
                Log::info("Notification envoyée à {$validator->name} pour {$sheets->count()} note(s) de frais.");
            }
        }
    }

}
