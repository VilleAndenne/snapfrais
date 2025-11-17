<?php

namespace App\Notifications\Messages;

class ExpoPushMessage
{
    public function __construct(
        public string $title,
        public string $body,
        public ?array $data = null,
        public string $sound = 'default',
        public ?string $channelId = null,
        public ?int $badge = null,
    ) {}

    /**
     * Set the title of the notification.
     */
    public function title(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Set the body of the notification.
     */
    public function body(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Set the data payload.
     */
    public function data(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Set the sound of the notification.
     */
    public function sound(string $sound): self
    {
        $this->sound = $sound;

        return $this;
    }

    /**
     * Set the channel ID (Android only).
     */
    public function channelId(string $channelId): self
    {
        $this->channelId = $channelId;

        return $this;
    }

    /**
     * Set the badge count.
     */
    public function badge(int $badge): self
    {
        $this->badge = $badge;

        return $this;
    }

    /**
     * Convert the message to an array for the Expo API.
     *
     * @return array<string, mixed>
     */
    public function toArray(string $token): array
    {
        $message = [
            'to' => $token,
            'sound' => $this->sound,
            'title' => $this->title,
            'body' => $this->body,
        ];

        if ($this->data !== null) {
            $message['data'] = $this->data;
        }

        if ($this->channelId !== null) {
            $message['channelId'] = $this->channelId;
        }

        if ($this->badge !== null) {
            $message['badge'] = $this->badge;
        }

        return $message;
    }
}
