<?php

namespace App\Notifications;

use App\Models\ExpenseSheet;
use App\Notifications\Concerns\SendsExpoPushNotifications;
use App\Notifications\Messages\ExpoPushMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReceiptExpenseSheetForUser extends Notification implements ShouldQueue
{
    use Queueable, SendsExpoPushNotifications;

    public ExpenseSheet $expenseSheet;

    public function __construct(public ExpenseSheet $eS)
    {
        $this->expenseSheet = $eS;
    }

    public function via(object $notifiable): array
    {
        // Vérifier si l'utilisateur souhaite recevoir ce type de notification
        if (! $notifiable->notify_receipt_expense_sheet) {
            return [];
        }

        $channels = ['mail'];

        return $this->addExpoPushChannel($channels, $notifiable);
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Confirmation de l\'encodage de la note de frais')
            ->greeting("Bonjour {$notifiable->name},")
            ->line('Nous avons bien reçu la note de frais que vous avez encodée pour **'.($this->expenseSheet->user?->name ?? 'un utilisateur').'**.')
            ->line('Montant total : '.number_format($this->expenseSheet->total, 2, ',', ' ').' €')
            ->action('Voir la note de frais', url("/expense-sheet/{$this->expenseSheet->id}"))
            ->line('La note de frais est en attente de validation.');
    }

    public function toExpoPush(object $notifiable): ExpoPushMessage
    {
        return new ExpoPushMessage(
            title: 'Encodage confirmé',
            body: "Note de frais encodée pour {$this->expenseSheet->user?->name} - Total: ".number_format($this->expenseSheet->total, 2, ',', ' ').' €',
            data: [
                'type' => 'expense_sheet_encoded',
                'expense_sheet_id' => $this->expenseSheet->id,
            ]
        );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'expense_sheet_id' => $this->expenseSheet->id,
            'total' => $this->expenseSheet->total,
            'created_by' => $this->expenseSheet->creator?->name,
        ];
    }
}
