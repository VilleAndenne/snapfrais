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

        $notified = collect(); // éviter les doublons

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
                $sheetAuthor = $sheet->user; // L’auteur de la note de frais
                $localHeads = $dept->heads;

                foreach ($localHeads as $localHead) {
                    // Cas 1 : le validateur essaie de valider sa propre note
                    if ($sheetAuthor->id === $localHead->id) {
                        continue; // skip
                    }

                    // Cas 2 : le validateur est responsable du service ET l’auteur aussi
                    if ($localHeads->contains($sheetAuthor)) {
                        continue; // deux responsables dans le même service → rejet
                    }

                    // Cas 3 : le validateur peut valider la note de l’auteur
                    if (!$notified->contains($localHead->id)) {
                        $localHead->notify(new Reminder($localHead, 1));
                        Log::info("Notification envoyée à {$localHead->name} pour 1 note de frais.");
                        $notified->push($localHead->id);
                    }
                }

                // Cas 4 : si aucun head local ne peut valider, on notifie le parent
                if ($dept->parent && $dept->parent->heads->isNotEmpty()) {
                    foreach ($dept->parent->heads as $parentHead) {
                        if (
                            $sheetAuthor->id !== $parentHead->id && // pas sa propre note
                            !$notified->contains($parentHead->id)
                        ) {
                            $parentHead->notify(new Reminder($parentHead, 1));
                            Log::info("Notification envoyée au responsable parent {$parentHead->name} pour 1 note de frais.");
                            $notified->push($parentHead->id);
                        }
                    }
                }
            }
        }
    }

}
