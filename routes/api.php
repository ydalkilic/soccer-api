<?php

use App\Http\Controllers\Api\LeagueController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::controller(LeagueController::class)->group(function () {
    Route::get('/league', 'index');
    Route::post('/league/create', 'create');
    Route::post('/league/simulate/{round}', 'simulateRound');
    Route::post('/league/simulate-all/', 'simulateAll');
});
