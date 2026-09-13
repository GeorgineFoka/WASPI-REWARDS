<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Comment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RewardTest extends TestCase
{
    use RefreshDatabase;

    private string $validToken = 'waspi_secret_token_2026';

    /**
     * Vérifie que l'accès est refusé sans token valide.
     */
    public function test_access_is_denied_without_valid_access_token(): void
    {
        $response = $this->getJson('/api/users');
        $response->assertStatus(401)
                 ->assertJson(['error' => 'Unauthorized. Un access_token valide est requis.']);

        $responseWithBadToken = $this->getJson('/api/users?access_token=invalid_token');
        $responseWithBadToken->assertStatus(401);
    }

    /**
     * Vérifie la récupération de la liste des utilisateurs avec leur structure JSON.
     */
    public function test_get_users_successfully_with_valid_token(): void
    {
        User::factory()->create([
            'name'  => 'Utilisateur Test',
            'email' => 'test@waspito.com',
        ]);

        $response = $this->getJson("/api/users?access_token={$this->validToken}");

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'data' => [
                         '*' => [
                             'id',
                             'name',
                             'email',
                             'points',
                             'current_badge',
                             'comments_count',
                             'likes_count',
                             'next_badge_info'
                         ]
                     ]
                 ]);
    }

    /**
     * Palier 1 : Premier commentaire = 50 points et badge beginner-badge.
     */
    public function test_user_earns_points_and_badge_on_first_comment(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson("/api/comments?access_token={$this->validToken}", [
            'user_id' => $user->id,
            'content' => 'Mon premier commentaire sur Waspito !'
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('user.points', 50)
                 ->assertJsonPath('user.current_badge', 'beginner-badge');

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'content' => 'Mon premier commentaire sur Waspito !'
        ]);
    }

    /**
     * Palier 2 : 30ème commentaire = 2500 points et badge top-fan.
     */
    public function test_user_reaches_top_fan_badge_after_threshold_comments(): void
    {
        $user = User::factory()->create();

        Comment::factory()->count(29)->create([
            'user_id' => $user->id,
            'content' => 'Commentaire de test automatisé'
        ]);

        $response = $this->postJson("/api/comments?access_token={$this->validToken}", [
            'user_id' => $user->id,
            'content' => '30ème commentaire !'
        ]);

        $response->assertStatus(201)
                 ->assertJsonPath('user.points', 2500)
                 ->assertJsonPath('user.current_badge', 'top-fan');
    }

    /**
     * Suppression d'un commentaire.
     */
    public function test_user_can_delete_comment(): void
    {
        $user = User::factory()->create();
        $comment = Comment::create([
            'user_id' => $user->id,
            'content' => 'Commentaire à supprimer'
        ]);

        $response = $this->deleteJson("/api/comments/{$comment->id}?access_token={$this->validToken}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    /**
     * Ajouter et supprimer un Like (Unlike).
     */
    public function test_user_can_like_and_unlike_comment(): void
    {
        $user = User::factory()->create();
        $author = User::factory()->create();

        $comment = Comment::create([
            'user_id' => $author->id,
            'content' => 'Commentaire pour Like'
        ]);

        // 1. Ajouter un Like (POST)
        $likeResponse = $this->postJson("/api/likes?access_token={$this->validToken}", [
            'user_id'    => $user->id,
            'comment_id' => $comment->id
        ]);
        $likeResponse->assertStatus(200);

        // 2. Supprimer le Like (DELETE)
        $unlikeResponse = $this->deleteJson("/api/likes?access_token={$this->validToken}", [
            'user_id'    => $user->id,
            'comment_id' => $comment->id
        ]);
        $unlikeResponse->assertStatus(200);
    }

    /**
     * Ajout de points par un administrateur.
     */
    public function test_admin_can_add_points_to_user(): void
    {
        $user = User::factory()->create(['points' => 0]);

        $response = $this->postJson("/api/users/{$user->id}/points?access_token={$this->validToken}", [
            'points' => 500
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.points', 500);

        // Vérification directe en base de données
        $this->assertDatabaseHas('users', [
            'id'     => $user->id,
            'points' => 500
        ]);
    }

    /**
     * Filtrer la liste des utilisateurs par badge.
     */
    public function test_filter_users_by_badge_type(): void
    {
        User::factory()->create([
            'name'          => 'User Avec Badge',
            'current_badge' => 'beginner-badge'
        ]);

        $response = $this->getJson("/api/users?access_token={$this->validToken}&type=beginner-badge");

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }

    /**
     * Filtrer la liste des utilisateurs par points minimums.
     */
    public function test_filter_users_by_minimum_points(): void
    {
        User::factory()->create(['points' => 2500]);

        $response = $this->getJson("/api/users?access_token={$this->validToken}&points=500");

        $response->assertStatus(200)
                 ->assertJsonCount(1, 'data');
    }
}