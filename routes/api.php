<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\RewardController;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes - WASPI REWARDS
|--------------------------------------------------------------------------
*/

// Tableau de bord et gestion des utilisateurs
Route::get('/users', [RewardController::class, 'index']);
Route::post('/users/{id}/points', [RewardController::class, 'addPoints']);

// Gestion des commentaires
Route::get('/comments', [RewardController::class, 'indexComments']);
Route::post('/comments', [RewardController::class, 'storeComment']);
Route::delete('/comments/{id}', [RewardController::class, 'destroyComment']);

// Gestion des likes (Format standard /likes)
Route::post('/likes', [RewardController::class, 'storeLike']);
Route::delete('/likes', [RewardController::class, 'destroyLike']);
Route::post('/likes/toggle', [RewardController::class, 'toggleLike']);

// Support des URLs imbriquées (/comments/{id}/like)
Route::post('/comments/{id}/like', function (Request $request, $id) {
    $request->merge(['comment_id' => $id]);
    return app(RewardController::class)->storeLike($request);
});

Route::delete('/comments/{id}/like', function (Request $request, $id) {
    $request->merge(['comment_id' => $id]);
    return app(RewardController::class)->destroyLike($request);
});