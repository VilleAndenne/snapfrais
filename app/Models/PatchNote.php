<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PatchNote extends Model
{
    protected $fillable = [
        'title',
        'content',
        'created_by',
    ];

    /**
     * Relation avec l'utilisateur créateur
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation many-to-many avec les utilisateurs qui ont lu la note
     */
    public function readByUsers()
    {
        return $this->belongsToMany(User::class, 'patch_note_user')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    /**
     * Vérifier si un utilisateur spécifique a lu cette note
     */
    public function isReadBy(User $user)
    {
        return $this->readByUsers()->where('user_id', $user->id)->exists();
    }
}
