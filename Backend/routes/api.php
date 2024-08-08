<?php

use Illuminate\Http\Request;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\AdminController;



Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('password/forgot', [AuthController::class, 'forgotPassword']);
Route::post('password/reset', [AuthController::class, 'resetPassword']);

Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
Route::get('/posts', [PostController::class, 'getAllPosts']);
Route::post('/posts/{id}/like', [PostController::class, 'likePost']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);

    Route::post('/posts', [PostController::class, 'createPost']);
    Route::put('/posts/{id}', [PostController::class, 'editPost']);
    Route::delete('/posts/{id}', [PostController::class, 'deletePost']);

    Route::get('/posts/{id}', [PostController::class, 'getPost']);

    Route::post('/comments', [CommentController::class, 'addComment']);
    Route::put('/comments/{id}', [CommentController::class, 'editComment']);
    Route::delete('/comments/{id}', [CommentController::class, 'deleteComment']);
    Route::post('/comments/reply', [CommentController::class, 'replyToComment']);
    Route::post('/comments/{id}/like', [CommentController::class, 'likeComment']);
    Route::get('/comments/{id}', [CommentController::class, 'getComment']);
    Route::get('/comments', [CommentController::class, 'getAllComments']);
});

Route::post('admin/register', [AdminAuthController::class, 'register']);
Route::post('admin/login', [AdminAuthController::class, 'login']);
Route::get('admin/profile', [AdminAuthController::class, 'profile'])->middleware('auth:sanctum');
Route::put('admin/profile', [AdminAuthController::class, 'updateProfile'])->middleware('auth:sanctum');
Route::post('/admin/logout', [AdminAuthController::class, 'logout'])->middleware('auth:sanctum');






Route::apiResource('categories', 'CategoryController');
Route::get('/admin/kpi', [AdminAuthController::class, 'getKPI'])->middleware('auth:sanctum');


