<?php

namespace Tests\Feature\Notifications;

use App\Models\ExpenseSheet;
use App\Models\MobileDevice;
use App\Models\User;
use App\Notifications\ApprovalExpenseSheet;
use App\Notifications\Channels\ExpoPushChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ExpoPushNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_includes_expo_channel_when_user_has_mobile_device(): void
    {
        // Create a user with a mobile device
        $user = User::factory()->create();
        MobileDevice::factory()->create([
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]',
            'platform' => 'android',
        ]);

        // Create an expense sheet
        $expenseSheet = ExpenseSheet::factory()->create(['user_id' => $user->id]);

        // Create notification
        $notification = new ApprovalExpenseSheet($expenseSheet);

        // Check that expo channel is included
        $channels = $notification->via($user);

        $this->assertContains('mail', $channels);
        $this->assertContains(ExpoPushChannel::class, $channels);
    }

    public function test_notification_excludes_expo_channel_when_user_has_no_mobile_device(): void
    {
        // Create a user without mobile device
        $user = User::factory()->create();

        // Create an expense sheet
        $expenseSheet = ExpenseSheet::factory()->create(['user_id' => $user->id]);

        // Create notification
        $notification = new ApprovalExpenseSheet($expenseSheet);

        // Check that expo channel is not included
        $channels = $notification->via($user);

        $this->assertContains('mail', $channels);
        $this->assertNotContains(ExpoPushChannel::class, $channels);
    }

    public function test_expo_push_notification_is_sent_successfully(): void
    {
        // Fake HTTP requests
        Http::fake([
            'https://exp.host/--/api/v2/push/send' => Http::response(['status' => 'ok'], 200),
        ]);

        // Create a user with a mobile device
        $user = User::factory()->create();
        MobileDevice::factory()->create([
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]',
            'platform' => 'ios',
        ]);

        // Create a validator user
        $validator = User::factory()->create(['name' => 'Validator User']);

        // Create an expense sheet
        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $user->id,
            'validated_by' => $validator->id,
        ]);

        // Send notification
        $user->notify(new ApprovalExpenseSheet($expenseSheet));

        // Assert that HTTP request was sent to Expo
        Http::assertSent(function ($request) {
            return $request->url() === 'https://exp.host/--/api/v2/push/send' &&
                   $request->hasHeader('Content-Type', 'application/json') &&
                   $request['to'] === 'ExponentPushToken[xxxxxxxxxxxxxxxxxxxxxx]';
        });
    }

    public function test_expo_push_notification_works_with_multiple_devices(): void
    {
        // Fake HTTP requests
        Http::fake([
            'https://exp.host/--/api/v2/push/send' => Http::response(['status' => 'ok'], 200),
        ]);

        // Create a user with multiple mobile devices
        $user = User::factory()->create();
        MobileDevice::factory()->create([
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[device1]',
            'platform' => 'ios',
        ]);
        MobileDevice::factory()->create([
            'user_id' => $user->id,
            'token' => 'ExponentPushToken[device2]',
            'platform' => 'android',
        ]);

        // Create a validator user
        $validator = User::factory()->create(['name' => 'Validator User']);

        // Create an expense sheet
        $expenseSheet = ExpenseSheet::factory()->create([
            'user_id' => $user->id,
            'validated_by' => $validator->id,
        ]);

        // Send notification
        $user->notify(new ApprovalExpenseSheet($expenseSheet));

        // Assert that HTTP requests were sent for both devices
        Http::assertSentCount(2);
    }
}
