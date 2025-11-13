<?php

namespace App\Notifications;

use App\Models\ExpenseSheet;
use App\Notifications\Concerns\SendsExpoPushNotifications;
use App\Notifications\Messages\ExpoPushMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpenseSheetToApproval extends Notification implements ShouldQueue
{
    use Queueable, SendsExpoPushNotifications;

    public ExpenseSheet $sheet;

    public function __construct(ExpenseSheet $sheet)
    {
        $this->sheet = $sheet;
    }

    public function via(object $notifiable): array
    {
        // Vérifier si l'utilisateur souhaite recevoir ce type de notification
        if (! $notifiable->notify_expense_sheet_to_approval) {
            return [];
        }

        $channels = ['mail'];

        return $this->addExpoPushChannel($channels, $notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Nouvelle note de frais à valider')
            ->greeting('Bonjour,')
            ->line("Une nouvelle note de frais a été soumise par : **{$this->sheet->user->name}**.")
            ->line("Date de création : {$this->sheet->created_at->format('d/m/Y')}")
            ->action('Voir la note de frais', url("/expense-sheet/{$this->sheet->id}"))
            ->line('Merci de valider ou refuser cette note dès que possible.');
    }

    public function toExpoPush(object $notifiable): ExpoPushMessage
    {
        return new ExpoPushMessage(
            title: 'Nouvelle note de frais à valider',
            body: "Note de frais soumise par {$this->sheet->user->name}",
            data: [
                'type' => 'expense_sheet_to_approval',
                'expense_sheet_id' => $this->sheet->id,
            ]
        );
    }

    public function toArray(object $notifiable): array
    {
        return [
        ];
    }
}
