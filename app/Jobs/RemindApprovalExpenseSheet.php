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

        $departments = Department::with('heads')->get();

        foreach ($departments as $dept) {
            foreach ($dept->heads as $manager) {
                if ($notified->contains($manager->id)) {
                    continue; // on a déjà traité ce responsable
                }

                $headsDepartments = $manager->departments()->wherePivot('is_head', true)->get();

                $pendingSheets = $headsDepartments->flatMap(function ($d) {
                    return $d->expenseSheets()->whereNull('validated_at')->get();
                });

                $count = $pendingSheets->count();

                if ($count > 0) {
                    $manager->notify(new Reminder($manager, $count));
                    Log::info("Notification envoyée à {$manager->name} pour {$count} note(s) de frais.");
                }

                $notified->push($manager->id);
            }
        }
    }
}
