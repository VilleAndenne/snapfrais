<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Show the notification settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/Notifications');
    }

    /**
     * Update the user's notification preferences.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'notify_expense_sheet_to_approval' => ['sometimes', 'boolean'],
            'notify_receipt_expense_sheet' => ['sometimes', 'boolean'],
            'notify_remind_approval' => ['sometimes', 'boolean'],
        ]);

        $request->user()->update($validated);

        return to_route('notifications.edit');
    }
}
