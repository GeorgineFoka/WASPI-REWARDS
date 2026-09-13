<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Comment;
use App\Models\Like;
use App\Services\RewardService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class RewardController extends Controller
{
    protected RewardService $rewardService;

    public function __construct(RewardService $rewardService)
    {
        $this->rewardService = $rewardService;
    }

    /**
     * Vérification de sécurité pour la présence du token requis.
     */
    private function checkAuthorization(Request $request): ?JsonResponse
    {
        $token = $request->query('access_token') ?? $request->bearerToken();
        $expectedToken = config('app.api_access_token', 'waspi_secret_token_2026');

        if (!$token || $token !== $expectedToken) {
            return response()->json([
                'error' => 'Unauthorized. Un access_token valide est requis.'
            ], 401);
        }

        return null;
    }

    /**
     * GET /api/users
     * Récupère la liste des utilisateurs avec filtres et informations de récompense.
     */
    public function index(Request $request): JsonResponse
    {
        if ($authError = $this->checkAuthorization($request)) {
            return $authError;
        }

        $users = User::withCount(['comments', 'likes'])->get()->map(function ($user) {
            $commentsCount = $user->comments_count;
            $likesCount = $user->likes_count;

            if (empty($user->current_badge) || $user->current_badge === 'aucun') {
                $user = $this->rewardService->evaluateUserRewards($user);
            }

            return [
                'id'              => $user->id,
                'name'            => $user->name,
                'email'           => $user->email,
                'points'          => $user->points ?? 0,
                'current_badge'   => $user->current_badge ?? 'aucun',
                'comments_count'  => $commentsCount,
                'likes_count'     => $likesCount,
                'next_badge_info' => $this->rewardService->getNextBadgeInfo($user),
            ];
        });

        // Filtrage par type / nom de badge
        $badgeFilter = $request->query('type') ?? $request->query('badge');
        if ($badgeFilter) {
            $users = $users->where('current_badge', $badgeFilter)->values();
        }

        // Filtrage par points minimums
        if ($request->filled('points') && is_numeric($request->query('points'))) {
            $minPoints = (int) $request->query('points');
            $users = $users->where('points', '>=', $minPoints)->values();
        }

        return response()->json(['data' => $users]);
    }

    /**
     * POST /api/users/{id}/points
     * Ajoute des points directement à un utilisateur et sauvegarde la modification.
     */
    public function addPoints(Request $request, $id): JsonResponse
    {
        if ($authError = $this->checkAuthorization($request)) {
            return $authError;
        }

        $request->validate([
            'points' => 'required|integer|min:1',
        ]);

        $user = User::findOrFail($id);

        // Ajout explicite des points et sauvegarde direct
        $user->points = ($user->points ?? 0) + (int) $request->input('points');
        $user->save();

        // Réévaluation des badges sans recalculer/écraser le total de points
        if (method_exists($this->rewardService, 'evaluateBadges')) {
            $user = $this->rewardService->evaluateBadges($user);
            $user->save();
        }

        return response()->json([
            'message' => 'Points ajoutés avec succès !',
            'data'    => $user,
        ], 200);
    }

    /**
     * GET /api/comments
     * Récupère la liste des commentaires avec la vérification de 'is_liked' pour l'utilisateur.
     */
    public function indexComments(Request $request): JsonResponse
    {
        if ($authError = $this->checkAuthorization($request)) {
            return $authError;
        }

        $currentUserId = $request->query('user_id');

        $comments = Comment::with('user')
            ->withCount('likes')
            ->latest()
            ->get()
            ->map(function ($comment) use ($currentUserId) {
                return [
                    'id'          => $comment->id,
                    'content'     => $comment->content,
                    'user_id'     => $comment->user_id,
                    'user'        => $comment->user ? [
                        'id'   => $comment->user->id,
                        'name' => $comment->user->name,
                    ] : null,
                    'likes_count' => $comment->likes_count ?? 0,
                    'is_liked'    => $currentUserId ? $comment->likes()->where('user_id', $currentUserId)->exists() : false,
                    'created_at'  => $comment->created_at->diffForHumans(),
                ];
            });

        return response()->json([
            'data' => $comments,
        ], 200);
    }

    /**
     * POST /api/comments
     */
    public function storeComment(Request $request): JsonResponse
    {
        if ($authError = $this->checkAuthorization($request)) {
            return $authError;
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'content' => 'required|string|max:1000',
        ]);

        $comment = Comment::create([
            'user_id' => $request->user_id,
            'content' => $request->content,
        ]);

        $user = User::withCount(['comments', 'likes'])->find($request->user_id);
        $user = $this->rewardService->evaluateUserRewards($user);

        return response()->json([
            'message' => 'Commentaire ajouté avec succès !',
            'comment' => $comment,
            'user'    => $user,
        ], 201);
    }

    /**
     * DELETE /api/comments/{id}
     */
    public function destroyComment(Request $request, $id): JsonResponse
    {
        if ($authError = $this->checkAuthorization($request)) {
            return $authError;
        }

        $comment = Comment::findOrFail($id);
        $userId = $comment->user_id;

        $comment->delete();

        $user = User::withCount(['comments', 'likes'])->find($userId);
        $user = $this->rewardService->evaluateUserRewards($user);

        return response()->json([
            'message' => 'Commentaire supprimé avec succès !',
            'user'    => $user,
        ]);
    }

    /**
     * POST /api/likes/toggle
     * Bascule l'état du Like (Ajoute si absent, Supprime si présent) et recalcule les points.
     */
    public function toggleLike(Request $request): JsonResponse
    {
        if ($authError = $this->checkAuthorization($request)) {
            return $authError;
        }

        $request->validate([
            'user_id'    => 'required|exists:users,id',
            'comment_id' => 'required|exists:comments,id',
        ]);

        $existingLike = Like::where('user_id', $request->user_id)
            ->where('comment_id', $request->comment_id)
            ->first();

        if ($existingLike) {
            $existingLike->delete();
            $isLiked = false;
            $message = 'Like retiré !';
        } else {
            Like::create([
                'user_id'    => $request->user_id,
                'comment_id' => $request->comment_id,
            ]);
            $isLiked = true;
            $message = 'Like enregistré !';
        }

        // Réévaluation des récompenses
        $user = User::withCount(['comments', 'likes'])->find($request->user_id);
        $user = $this->rewardService->evaluateUserRewards($user);

        $likesCount = Like::where('comment_id', $request->comment_id)->count();

        return response()->json([
            'message'     => $message,
            'is_liked'    => $isLiked,
            'likes_count' => $likesCount,
            'user'        => $user,
        ]);
    }

    /**
     * POST /api/likes
     */
    public function storeLike(Request $request): JsonResponse
    {
        return $this->toggleLike($request);
    }

    /**
     * DELETE /api/likes
     * Supprime explicitement un Like et réévalue les points de l'utilisateur.
     */
    public function destroyLike(Request $request): JsonResponse
    {
        if ($authError = $this->checkAuthorization($request)) {
            return $authError;
        }

        // Fusionne les paramètres de requête de l'URL pour valider correctement en HTTP DELETE
        $data = array_merge($request->query(), $request->all());

        $validator = \Illuminate\Support\Facades\Validator::make($data, [
            'user_id'    => 'required|exists:users,id',
            'comment_id' => 'required|exists:comments,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The comment id field is required.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $userId = $data['user_id'];
        $commentId = $data['comment_id'];

        Like::where('user_id', $userId)
            ->where('comment_id', $commentId)
            ->delete();

        $user = User::withCount(['comments', 'likes'])->find($userId);
        $user = $this->rewardService->evaluateUserRewards($user);

        return response()->json([
            'message' => 'Like supprimé avec succès !',
            'user'    => $user,
        ], 200);
    }
}