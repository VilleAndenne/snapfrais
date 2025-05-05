<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new App\Jobs\RemindApprovalExpenseSheet)->dailyAt('08:00')->timezone('Europe/Paris');

Schedule::command('telescope:prune --hours=48')->daily();
