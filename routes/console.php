<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

\Illuminate\Support\Facades\Schedule::job(\App\Jobs\RemindApprovalExpenseSheet::class)->daily();
