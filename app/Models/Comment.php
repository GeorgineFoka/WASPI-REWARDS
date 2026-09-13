<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'content'];

    // Charger automatiquement le nombre de likes et l'auteur si besoin
    protected $withCount = ['likes'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    /**
     * Vérifie si un utilisateur spécifique a liké ce commentaire
     */
    public function isLikedBy(?int $userId): bool
    {
        if (!$userId) return false;
        return $this->likes()->where('user_id', $userId)->exists();
    }
}