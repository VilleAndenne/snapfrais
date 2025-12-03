<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Mirror\Concerns\Impersonatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Impersonatable, Notifiable, SoftDeletes;

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
        'notify_expense_sheet_to_approval',
        'notify_receipt_expense_sheet',
        'notify_remind_approval',
    ];

    protected $appends = ['is_head'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function setEmailAttribute($value)
    {
        $this->attributes['email'] = strtolower($value);
    }

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
            'notify_expense_sheet_to_approval' => 'boolean',
            'notify_receipt_expense_sheet' => 'boolean',
            'notify_remind_approval' => 'boolean',
            'super_admin' => 'boolean',
        ];
    }

    public function organizations()
    {
        return $this->belongsToMany(Organization::class);
    }

    public function getIsHeadAttribute(): bool
    {
        return $this->isHead() ? true : false;
    }

    public function departments()
    {
        return $this->belongsToMany(Department::class)->withPivot('is_head');
    }

    public function headOfDepartments()
    {
        return $this->belongsToMany(Department::class)->wherePivot('is_head', true);
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

    public function expenseSheets(): HasMany
    {
        return $this->hasMany(ExpenseSheet::class);
    }

    public function sendPasswordResetNotification($token)
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Relation many-to-many avec les patch notes lues
     */
    public function readPatchNotes()
    {
        return $this->belongsToMany(PatchNote::class, 'patch_note_user')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    /**
     * Récupérer les patch notes non lues
     */
    public function unreadPatchNotes()
    {
        return PatchNote::whereDoesntHave('readByUsers', function ($query) {
            $query->where('user_id', $this->id);
        })->orderBy('created_at', 'desc');
    }

    /**
     * Get the mobile devices for the user.
     */
    public function mobileDevices(): HasMany
    {
        return $this->hasMany(MobileDevice::class);
    }

    /**
     * Determine if the user can impersonate other users.
     */
    public function canImpersonate(): bool
    {
        return $this->super_admin;
    }

    /**
     * Determine if the user can be impersonated.
     */
    public function canBeImpersonated(): bool
    {
        return ! $this->super_admin;
    }
}
