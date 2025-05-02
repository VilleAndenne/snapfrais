<?php

namespace App\Notifications;

use App\Models\ExpenseSheet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ExpenseSheetToApproval extends Notification implements ShouldQueue
{
    use Queueable;

    public ExpenseSheet $sheet;

    public function __construct(ExpenseSheet $sheet)
    {
        $this->sheet = $sheet;
    }

    public function via(object $notifiable)
    {
        return ['mail'];
    }

    public function toMail(object $notifiable)
    {
        return (new MailMessage)
            ->subject('Nouvelle note de frais à valider')
            ->greeting("Bonjour,")
            ->line("Une nouvelle note de frais a été soumise par : **{$this->sheet->user->name}**.")
            ->line("Date de création : {$this->sheet->created_at->format('d/m/Y')}")
            ->action('Voir la note de frais', url("/expense-sheets/{$this->sheet->id}"))
            ->line("Merci de valider ou refuser cette note dès que possible.");
    }

    public function toArray(object $notifiable)
    {
        return [
        ];
    }
}
