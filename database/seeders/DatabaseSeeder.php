<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Comment;
use App\Models\Like;
use App\Services\RewardService;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $rewardService = app(RewardService::class);

        // 1. Test User (50 pts) -> 1 commentaire -> beginner-badge
        $user1 = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);
        $c1 = Comment::create(['user_id' => $user1->id, 'content' => 'Premier commentaire de Test User']);

        // 2. Alice Mbida (50 pts) -> 1 commentaire -> beginner-badge
        $user2 = User::create([
            'name' => 'Alice Mbida',
            'email' => 'alice@waspito.com',
            'password' => bcrypt('password'),
        ]);
        $c2 = Comment::create(['user_id' => $user2->id, 'content' => 'Commentaire Alice 1']);

        // 3. Jean Kengne (550 pts) -> 1 commentaire (50 pts) + 10 likes sur différents commentaires (500 pts) -> beginner
        $user3 = User::create([
            'name' => 'Jean Kengne',
            'email' => 'jean@waspito.com',
            'password' => bcrypt('password'),
        ]);
        Comment::create(['user_id' => $user3->id, 'content' => 'Commentaire Jean 1']);

        // Pour respecter la contrainte d'unicité (user_id, comment_id), Jean likera 10 commentaires de Test User et Alice
        $targetComments = [];
        for ($i = 2; $i <= 11; $i++) {
            $targetComments[] = Comment::create(['user_id' => $user1->id, 'content' => "Post public $i"]);
        }
        foreach ($targetComments as $comment) {
            Like::create(['user_id' => $user3->id, 'comment_id' => $comment->id]);
        }

        // 4. Carine Foka (5500 pts) -> 50 commentaires (5000 pts) + 10 likes (500 pts) -> super-fan (Niveau max)
        $user4 = User::create([
            'name' => 'Carine Foka',
            'email' => 'carine@waspito.com',
            'password' => bcrypt('password'),
        ]);
        
        for ($i = 1; $i <= 50; $i++) {
            Comment::create(['user_id' => $user4->id, 'content' => "Commentaire Carine $i"]);
        }
        
        // Carine like les 10 posts de Test User
        foreach ($targetComments as $comment) {
            Like::create(['user_id' => $user4->id, 'comment_id' => $comment->id]);
        }

        // Recalculer les points et synchroniser les badges en BDD
        foreach (User::all() as $user) {
            $userWithCounts = User::withCount(['comments', 'likes'])->find($user->id);
            $rewardService->evaluateUserRewards($userWithCounts);
        }
    }
}