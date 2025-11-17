<?php

namespace App\Notifications;

use App\Notifications\Concerns\SendsExpoPushNotifications;
use App\Notifications\Messages\ExpoPushMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCreated extends Notification implements ShouldQueue
{
    use Queueable, SendsExpoPushNotifications;

    /**
     * Create a new notification instance.
     */
    private string $token;

    private string $email;

    public function __construct(string $token, string $email)
    {
        $this->token = $token;
        $this->email = $email;
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
            ->subject('Création de compte sur la plateforme de gestion des notes de frais')
            ->greeting('Bonjour ,')
            ->line('Nous vous informons que votre compte a été créé avec succès.')
            ->action('Réinitialiser le mot de passe', url('/reset-password/'.$this->token.'?email='.$this->email))
            ->line('Cette application vous permet de gérer vos notes de frais de manière plus efficace.')
            ->line('Merci d\'utiliser notre application !')
            ->salutation('Cordialement,');
    }

    /**
     * Get the Expo push notification representation of the notification.
     */
    public function toExpoPush(object $notifiable): ExpoPushMessage
    {
        return new ExpoPushMessage(
            title: 'Compte créé',
            body: 'Votre compte a été créé avec succès. Veuillez définir votre mot de passe.',
            data: [
                'type' => 'user_created',
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
