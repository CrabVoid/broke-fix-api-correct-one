<?php

use App\Http\Controllers\ApplicationController;
use App\Http\Controllers\CommentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::get('/applications', [ApplicationController::class, 'index']);
Route::post('/applications', [ApplicationController::class, 'store']);
Route::post('/applications/procedure', [ApplicationController::class, 'storeWithProcedure']);

// Comments routes for applications and evaluations
Route::get('/{type}/{id}/comments', [CommentController::class, 'index']);
Route::post('/{type}/{id}/comments', [CommentController::class, 'store']);
Route::put('/comments/{id}', [CommentController::class, 'update']);
Route::delete('/comments/{id}', [CommentController::class, 'destroy']);
