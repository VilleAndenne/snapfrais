<?php

namespace App\Notifications;

use App\Models\ExpenseSheet;
use App\Notifications\Concerns\SendsExpoPushNotifications;
use App\Notifications\Messages\ExpoPushMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ApprovalExpenseSheet extends Notification implements ShouldQueue
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
        return (new MailMessage)
            ->subject('Votre note de frais #'.$this->expenseSheet->id.' a été approuvée')
            ->greeting('Bonjour,')
            ->line($this->expenseSheet->validatedBy->name.' a approuvé votre note de frais #'.$this->expenseSheet->id)
            ->action('Voir la note de frais', url('/expense-sheet/'.$this->expenseSheet->id))
            ->line('Merci d\'utiliser notre application !')
            ->salutation('Bien cordialement,');
    }

    /**
     * Get the Expo push notification representation of the notification.
     */
    public function toExpoPush(object $notifiable): ExpoPushMessage
    {
        return new ExpoPushMessage(
            title: 'Note de frais approuvée',
            body: $this->expenseSheet->validatedBy->name.' a approuvé votre note de frais #'.$this->expenseSheet->id,
            data: [
                'type' => 'expense_sheet_approved',
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
            //
        ];
    }
}
