<?php

namespace App\Notifications;

use App\Models\ExpenseSheet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RejectionExpenseSheet extends Notification implements ShouldQueue
{
    use Queueable;

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
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre note de frais #' . $this->expenseSheet->id . ' a été refusée')
            ->greeting('Bonjour,')
            ->line($this->expenseSheet->validatedBy->name . ' a refusé votre note de frais #' . $this->expenseSheet->id)
            ->action('Voir la note de frais', url('/expense-sheets/' . $this->expenseSheet->id))
            ->line('Si vous pensez qu’il s’agit d’une erreur, vous pouvez contacter votre responsable de service ou justifiez à nouveau votre demande.')
            ->salutation('Bien cordialement,');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
        ];
    }
}
