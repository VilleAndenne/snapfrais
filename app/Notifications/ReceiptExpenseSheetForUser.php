<?php

namespace App\Notifications;

use App\Models\ExpenseSheet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReceiptExpenseSheetForUser extends Notification implements ShouldQueue
{
    use Queueable;

    public ExpenseSheet $expenseSheet;

    public function __construct(public ExpenseSheet $eS)
    {
        $this->expenseSheet = $eS;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Confirmation de l\'encodage de la note de frais')
            ->greeting("Bonjour {$notifiable->name},")
            ->line("Nous avons bien reçu la note de frais que vous avez encodée pour **{$this->expenseSheet->user?->name ?? 'un utilisateur'}**.")
            ->line("Montant total : " . number_format($this->expenseSheet->total, 2, ',', ' ') . " €")
            ->action('Voir la note de frais', url("/expense-sheet/{$this->expenseSheet->id}"))
            ->line('La note de frais est en attente de validation.');
    }

    public function toArray($notifiable)
    {
        return [
            'expense_sheet_id' => $this->expenseSheet->id,
            'total' => $this->expenseSheet->total,
            'created_by' => $this->expenseSheet->creator?->name,
        ];
    }
}
