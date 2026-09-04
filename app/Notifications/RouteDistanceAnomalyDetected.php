<?php

namespace App\Notifications;

use App\Models\RouteDistance;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerte envoyée aux administrateurs lorsqu'un trajet déjà encodé est
 * recalculé avec une distance très différente de sa référence : travaux
 * durables, changement de voirie… ou adresse ambiguë.
 */
class RouteDistanceAnomalyDetected extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public RouteDistance $routeDistance,
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
     * Écart en pourcentage entre la distance mesurée et la référence.
     */
    public function deviationPercent(): float
    {
        $reference = $this->routeDistance->distance_meters;

        if ($reference <= 0) {
            return 0.0;
        }

        return round(abs($this->measuredMeters - $reference) / $reference * 100, 1);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $referenceKm = round($this->routeDistance->distance_meters / 1000, 2);
        $measuredKm = round($this->measuredMeters / 1000, 2);

        $message = (new MailMessage)
            ->subject('Distance inhabituelle sur un trajet encodé')
            ->greeting('Bonjour,')
            ->line('Un trajet vient d\'être recalculé avec une distance très différente de celle enregistrée lors des précédents encodages.')
            ->line('**Départ :** '.$this->routeDistance->origin)
            ->line('**Arrivée :** '.$this->routeDistance->destination)
            ->line('**Mode :** '.($this->routeDistance->transport === 'bike' ? 'Vélo' : 'Voiture'))
            ->line('**Distance de référence :** '.$referenceKm.' km')
            ->line('**Distance mesurée :** '.$measuredKm.' km ('.$this->deviationPercent().' % d\'écart)');

        if ($this->encoder) {
            $message->line('**Encodage réalisé par :** '.$this->encoder->name);
        }

        return $message
            ->line('La distance de référence a été conservée pour le remboursement : le montant n\'a donc pas été affecté.')
            ->line('Si ce nouvel itinéraire est durable, la référence doit être mise à jour manuellement.')
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
            'reference_meters' => $this->routeDistance->distance_meters,
            'measured_meters' => $this->measuredMeters,
            'deviation_percent' => $this->deviationPercent(),
        ];
    }
}
