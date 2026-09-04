<?php

namespace App\Notifications;

use App\Models\RouteDistance;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerte envoyée aux administrateurs lorsqu'un trajet déjà encodé est mesuré
 * avec une distance très différente de la dernière connue : voirie modifiée,
 * chantier de longue durée… ou adresse ambiguë. La note utilise la nouvelle
 * mesure ; l'alerte permet de vérifier qu'elle est justifiée.
 */
class RouteDistanceAnomalyDetected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RouteDistance $routeDistance,
        public int $previousMeters,
        public int $measuredMeters,
        public ?User $encoder = null,
    ) {}

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
     * Écart en pourcentage entre la mesure du jour et la précédente.
     */
    public function deviationPercent(): float
    {
        if ($this->previousMeters <= 0) {
            return 0.0;
        }

        return round(abs($this->measuredMeters - $this->previousMeters) / $this->previousMeters * 100, 1);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $previousKm = round($this->previousMeters / 1000, 2);
        $measuredKm = round($this->measuredMeters / 1000, 2);
        $direction = $this->measuredMeters > $this->previousMeters ? 'plus longue' : 'plus courte';

        $message = (new MailMessage)
            ->subject('Distance inhabituelle sur un trajet encodé')
            ->greeting('Bonjour,')
            ->line('Un trajet vient d\'être encodé avec une distance nettement '.$direction.' que la dernière fois qu\'il a été calculé.')
            ->line('**Départ :** '.$this->routeDistance->origin)
            ->line('**Arrivée :** '.$this->routeDistance->destination)
            ->line('**Mode :** '.($this->routeDistance->transport === 'bike' ? 'Vélo' : 'Voiture'))
            ->line('**Distance précédente :** '.$previousKm.' km')
            ->line('**Distance retenue :** '.$measuredKm.' km ('.$this->deviationPercent().' % d\'écart)');

        if ($this->encoder) {
            $message->line('**Encodage réalisé par :** '.$this->encoder->name);
        }

        return $message
            ->line('C\'est bien la nouvelle distance qui a été retenue pour le remboursement. Ce message est informatif : il vous permet de vérifier que l\'écart est justifié (voirie modifiée, chantier de longue durée) et non dû à une adresse mal saisie.')
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
            'route_distance_id' => $this->routeDistance->id,
            'origin' => $this->routeDistance->origin,
            'destination' => $this->routeDistance->destination,
            'transport' => $this->routeDistance->transport,
            'previous_meters' => $this->previousMeters,
            'measured_meters' => $this->measuredMeters,
            'deviation_percent' => $this->deviationPercent(),
        ];
    }
}
