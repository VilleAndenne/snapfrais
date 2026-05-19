<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminInitiatedPasswordReset extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private string $token,
        private string $adminName,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe')
            ->greeting('Bonjour,')
            ->line("Un administrateur ({$this->adminName}) a initié une réinitialisation de votre mot de passe.")
            ->line('Cliquez sur le bouton ci-dessous pour définir un nouveau mot de passe.')
            ->action('Réinitialiser le mot de passe', $url)
            ->line("Si vous n'êtes pas à l'origine de cette demande ou si vous avez des doutes, contactez votre administrateur.")
            ->salutation('Cordialement,');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
