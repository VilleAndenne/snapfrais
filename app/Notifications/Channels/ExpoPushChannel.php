<?php

namespace App\Notifications\Channels;

use App\Notifications\Messages\ExpoPushMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoPushChannel
{
    /**
     * Send the given notification.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        Log::info('ExpoPushChannel: Attempting to send push notification', [
            'notification_type' => get_class($notification),
            'notifiable_type' => get_class($notifiable),
            'notifiable_id' => $notifiable->id ?? null,
        ]);

        // Get the Expo push tokens for the notifiable
        $tokens = $this->getTokens($notifiable);

        Log::info('ExpoPushChannel: Tokens retrieved', [
            'token_count' => count($tokens),
            'tokens' => $tokens,
        ]);

        if (empty($tokens)) {
            Log::warning('ExpoPushChannel: No tokens found for notifiable', [
                'notifiable_type' => get_class($notifiable),
                'notifiable_id' => $notifiable->id ?? null,
            ]);

            return;
        }

        // Get the notification message
        $message = $notification->toExpoPush($notifiable);

        if (! $message instanceof ExpoPushMessage) {
            Log::error('ExpoPushChannel: Message is not an instance of ExpoPushMessage', [
                'message_type' => get_class($message),
            ]);

            return;
        }

        Log::info('ExpoPushChannel: Notification message prepared', [
            'title' => $message->title,
            'body' => $message->body,
            'data' => $message->data,
        ]);

        // Send to each token
        foreach ($tokens as $token) {
            $this->sendPushNotification($message, $token);
        }
    }

    /**
     * Get the Expo push tokens for the notifiable entity.
     *
     * @return array<string>
     */
    protected function getTokens(object $notifiable): array
    {
        if (method_exists($notifiable, 'routeNotificationForExpoPush')) {
            $tokens = $notifiable->routeNotificationForExpoPush();

            return is_array($tokens) ? $tokens : [$tokens];
        }

        // Default: get tokens from mobile devices relationship
        if (method_exists($notifiable, 'mobileDevices')) {
            return $notifiable->mobileDevices()
                ->pluck('token')
                ->filter()
                ->toArray();
        }

        return [];
    }

    /**
     * Send a push notification via Expo's API.
     */
    protected function sendPushNotification(ExpoPushMessage $message, string $token): void
    {
        try {
            $payload = $message->toArray($token);

            Log::info('ExpoPushChannel: Sending push notification to Expo API', [
                'token' => $token,
                'payload' => $payload,
            ]);

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Accept-encoding' => 'gzip, deflate',
                'Content-Type' => 'application/json',
            ])->post('https://exp.host/--/api/v2/push/send', $payload);

            if (! $response->successful()) {
                Log::error('ExpoPushChannel: Failed to send Expo push notification', [
                    'token' => $token,
                    'response' => $response->json(),
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            } else {
                Log::info('ExpoPushChannel: Successfully sent Expo push notification', [
                    'token' => $token,
                    'response' => $response->json(),
                    'status' => $response->status(),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('ExpoPushChannel: Exception while sending Expo push notification', [
                'token' => $token,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
