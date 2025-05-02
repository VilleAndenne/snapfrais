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

        $departments = Department::with(['heads', 'expenseSheets' => function ($query) {
            $query->whereNull('validated_at');
        }, 'parent.heads'])->get();

        foreach ($departments as $dept) {
            foreach ($dept->expenseSheets as $sheet) {
                $localHeads = $dept->heads;

                foreach ($localHeads as $localHead) {
                    // Si l'agent est responsable de son propre service...
                    if ($sheet->created_by === $localHead->id) {
                        // Et que ce service a un parent...
                        if ($dept->parent && $dept->parent->heads->isNotEmpty()) {
                            foreach ($dept->parent->heads as $parentHead) {
                                if (!$notified->contains($parentHead->id)) {
                                    $parentHead->notify(new Reminder($parentHead, 1)); // 1 car 1 note ici
                                    Log::info("Notification envoyée au responsable parent {$parentHead->name} pour 1 note de frais.");
                                    $notified->push($parentHead->id);
                                }
                            }
                        }
                    } else {
                        // Cas normal : ce n’est pas le responsable du service de la note
                        if (!$notified->contains($localHead->id)) {
                            $localHead->notify(new Reminder($localHead, 1));
                            Log::info("Notification envoyée à {$localHead->name} pour 1 note de frais.");
                            $notified->push($localHead->id);
                        }
                    }
                }
            }
        }
    }
}
