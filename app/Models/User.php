<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class);
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class)->withPivot('is_head');
    }

    public function superiors()
    {
        // Tous les autres membres des services de l'utilisateur where is_head = 1
        $services = $this->services()->get();

        $superiors = collect();

        foreach ($services as $service) {
            if ($service->heads()) {
                $superiors = $superiors->merge($service->heads()->get());
                // While, if the service has a superior, we add the heads of the superior services
                while ($service->superior()->exists()) {
                    $service = $service->superior()->first();
                    $superiors = $superiors->merge($service->heads()->get());
                }
            }
        }

        // On ajoute les utilisateurs qui sont chefs des services dont l'utlisateur est membre

        // On retire l'utilisateur de la liste
        $superiors = $superiors->reject(function ($superior) {
            return $superior->id === $this->id;
        });

        // on retire les doublons
        $superiors = $superiors->unique('id');

        return $superiors->all();
    }

    public function subordonates()
    {
        // Récupérer tous les services de l'utilisateur
        $services = $this->services()->get();

        $subordonates = collect();

        // Fonction récursive pour récupérer les sous-ordonnés hiérarchiques de manière récursive
        $retrieveSubordonates = function ($service) use (&$retrieveSubordonates, &$subordonates) {
            $subServices = $service->subordonates()->get();

            foreach ($subServices as $subService) {
                // Ajouter les chefs de service aux subordonnés
                $subordonates = $subordonates->merge($subService->heads()->get());

                // Ajouter les utilisateurs du sous-service
                $subordonates = $subordonates->merge($subService->users()->get());

                // Appeler récursivement pour récupérer les sous-services des sous-services
                $retrieveSubordonates($subService);
            }
        };

        // Pour chaque service de l'utilisateur, récupérer les sous-services et leurs utilisateurs
        foreach ($services as $service) {
            // Ajouter les utilisateurs du service courant
            $subordonates = $subordonates->merge($service->users()->get());

            // Récupérer les sous-services de manière récursive
            $retrieveSubordonates($service);
        }

        // On retire l'utilisateur de la liste
        $subordonates = $subordonates->reject(function ($subordonate) {
            return $subordonate->id === $this->id;
        });

        // On retire les doublons
        $subordonates = $subordonates->unique('id');

        return $subordonates->all();
    }

    public function isHead()
    {
        return $this->departments()->where('is_head', true)->exists();
    }

}
