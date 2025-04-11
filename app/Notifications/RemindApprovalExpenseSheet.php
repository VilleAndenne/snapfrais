<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RemindApprovalExpenseSheet extends Notification
{
    use Queueable;

    public int $count;

    /**
     * Create a new notification instance.
     */
    public function __construct(int $count)
    {
        $this->count = $count;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Rappel : Notes de frais en attente de validation')
            ->greeting('Bonjour,')
            ->line('Vous avez encore ' . $this->count . ' note(s) de frais à valider.')
            ->action('Voir les notes de frais', url('/expense-sheets/pending'))
            ->line('Merci de traiter ces demandes dès que possible.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
