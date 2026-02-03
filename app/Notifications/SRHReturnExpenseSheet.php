<?php

namespace App\Notifications;

use App\Models\ExpenseSheet;
use App\Notifications\Concerns\SendsExpoPushNotifications;
use App\Notifications\Messages\ExpoPushMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SRHReturnExpenseSheet extends Notification implements ShouldQueue
{
    use Queueable, SendsExpoPushNotifications;

    public ExpenseSheet $expenseSheet;

    /**
     * Create a new notification instance.
     */
    public function __construct(ExpenseSheet $expenseSheet)
    {
        $this->expenseSheet = $expenseSheet;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        return $this->addExpoPushChannel($channels, $notifiable);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Le SRH a renvoyé la note de frais #'.$this->expenseSheet->id)
            ->greeting('Bonjour,')
            ->line('Le SRH a décidé de renvoyer la note de frais #'.$this->expenseSheet->id.' de '.$this->expenseSheet->user->name.'.');

        if ($this->expenseSheet->refusal_reason) {
            $message->line('**Motif du renvoi :** '.$this->expenseSheet->refusal_reason);
        }

        return $message
            ->action('Voir la note de frais', url('/expense-sheet/'.$this->expenseSheet->id))
            ->line('Veuillez prendre les mesures nécessaires pour corriger cette note de frais.')
            ->salutation('Bien cordialement,');
    }

    /**
     * Get the Expo push notification representation of the notification.
     */
    public function toExpoPush(object $notifiable): ExpoPushMessage
    {
        return new ExpoPushMessage(
            title: 'Note de frais renvoyée par le SRH',
            body: 'Le SRH a renvoyé la note de frais #'.$this->expenseSheet->id,
            data: [
                'type' => 'expense_sheet_returned_by_srh',
                'expense_sheet_id' => $this->expenseSheet->id,
            ]
        );
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'expense_sheet_id' => $this->expenseSheet->id,
            'refusal_reason' => $this->expenseSheet->refusal_reason,
        ];
    }
}
