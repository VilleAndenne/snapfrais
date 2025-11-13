<?php

namespace App\Notifications\Concerns;

use App\Notifications\Channels\ExpoPushChannel;

trait SendsExpoPushNotifications
{
    /**
     * Add Expo push channel to the notification channels if user has mobile devices.
     *
     * @param  array<int, string>  $channels
     * @return array<int, string>
     */
    protected function addExpoPushChannel(array $channels, object $notifiable): array
    {
        // Check if the notifiable has mobile devices
        if (method_exists($notifiable, 'mobileDevices') && $notifiable->mobileDevices()->exists()) {
            $channels[] = ExpoPushChannel::class;
        }

        return $channels;
    }
}
