<?php

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RemindApprovalExpenseSheet implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Get the count of pending expense sheets for every user
        $users = User::all();

        foreach ($users as $user) {
            // Count the number of pending expense sheets to validate for the user
        }

    }
}
