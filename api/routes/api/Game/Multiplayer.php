<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Game\GameController;
use App\Http\Controllers\Game\MatchController;
use App\Http\Controllers\Game\LobbyController;
use App\Models\MatchModel;

Route::middleware('auth:sanctum')->group(function () {

    Route::model('match', MatchModel::class);

    Route::get('/games/{game}', [GameController::class, 'show'])
        ->whereNumber('game');

    Route::get('/games/{game}/state', [GameController::class, 'state'])
        ->whereNumber('game');

    Route::post('/games/{game}/join', [GameController::class, 'join'])
        ->whereNumber('game');

    Route::post('/games/{game}/play-card', [GameController::class, 'playCard'])
        ->whereNumber('game');

    Route::post('/games/{game}/ready', [GameController::class, 'ready'])
        ->whereNumber('game');

    Route::post('/games/{game}/unready', [GameController::class, 'unready'])
        ->whereNumber('game');

    Route::post('/games/{game}/leave-lobby', [GameController::class, 'leaveLobby'])
        ->whereNumber('game');

    Route::post('/games/{game}/kick', [GameController::class, 'kickLobby'])
        ->whereNumber('game');

    Route::delete('/games/{game}', [GameController::class, 'cancelLobby'])
        ->whereNumber('game');

    Route::post('/games/{game}/resign', [GameController::class, 'resign'])
        ->whereNumber('game');

    Route::post('/matches', [MatchController::class, 'store']);

    Route::get('/matches/{match}', [MatchController::class, 'show'])
        ->whereNumber('match');

    Route::post('/matches/{match}/join', [MatchController::class, 'join'])
        ->whereNumber('match');

    Route::post('/matches/{match}/resign', [MatchController::class, 'resign'])
        ->whereNumber('match');

    Route::delete('/matches/{match}', [MatchController::class, 'cancel'])
        ->whereNumber('match');

    Route::get('/lobby/games', [LobbyController::class, 'openGames']);
    Route::get('/lobby/matches', [LobbyController::class, 'openMatches']);
});
