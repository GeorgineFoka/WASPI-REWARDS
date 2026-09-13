<?php

namespace App\Services;

use App\Models\User;

class RewardService
{
    /**
     * Paliers officiels demandés par WASPITO (du plus grand au plus petit).
     */
    protected const BADGE_HIERARCHY = [
        ['name' => 'super-fan',      'min_points' => 5000],
        ['name' => 'top-fan',        'min_points' => 2500],
        ['name' => 'beginner',       'min_points' => 500],
        ['name' => 'beginner-badge', 'min_points' => 50],
    ];

    /**
     * Évalue et met à jour les points et le badge de l'utilisateur.
     * Permet également la rétrogradation des points/badges en cas de suppression de contenu.
     */
    public function evaluateUserRewards(User $user): User
    {
        $likesCount = $user->likes_count ?? $user->likes()->count();
        $commentsCount = $user->comments_count ?? $user->comments()->count();

        // 1. Calcul des points sur les commentaires (Paliers Waspito)
        $commentPoints = 0;
        if ($commentsCount >= 50) {
            $commentPoints = 5000;
        } elseif ($commentsCount >= 30) {
            $commentPoints = 2500;
        } elseif ($commentsCount >= 1) {
            $commentPoints = 50;
        }

        // 2. Calcul des points sur les likes (Palier Waspito)
        $likePoints = ($likesCount >= 10) ? 500 : 0;

        // Total des points calculés dynamiquement selon l'état actuel de la BDD
        $calculatedPoints = $commentPoints + $likePoints;
        
        // Si tu gères des points ajoutés manuellement via l'API (ex: addPoints), 
        // on conserve l'excédent manuel tout en autorisant la baisse des points de participation.
        $manualPoints = $user->manual_points ?? 0;
        $totalPoints = $calculatedPoints + $manualPoints;

        // Détermination du badge correspondant au nouveau solde de points
        $currentBadge = $this->resolveBadgeForPoints($totalPoints);

        // Mise à jour de l'instance et de la BDD si un changement est détecté
        if ($user->points !== $totalPoints || $user->current_badge !== $currentBadge) {
            $user->points = $totalPoints;
            $user->current_badge = $currentBadge;
            $user->save();
        }

        return $user;
    }

    /**
     * Retourne le nom exact du badge selon le total de points.
     */
    public function resolveBadgeForPoints(int $points): string
    {
        foreach (self::BADGE_HIERARCHY as $badge) {
            if ($points >= $badge['min_points']) {
                return $badge['name'];
            }
        }

        return 'aucun';
    }

    /**
     * Détermine la prochaine étape / badge supérieur pour l'API et l'IHM.
     */
    public function getNextBadgeInfo(User $user): array
    {
        $currentPoints = $user->points ?? 0;
        $ascendingHierarchy = array_reverse(self::BADGE_HIERARCHY);

        foreach ($ascendingHierarchy as $badge) {
            if ($currentPoints < $badge['min_points']) {
                return [
                    'next_badge'    => $badge['name'],
                    'points_needed' => $badge['min_points'] - $currentPoints,
                    'target_points' => $badge['min_points'],
                ];
            }
        }

        return [
            'next_badge'    => 'Niveau Max',
            'points_needed' => 0,
            'target_points' => 5000,
        ];
    }
}