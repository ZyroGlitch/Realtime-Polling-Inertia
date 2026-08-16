<?php

use App\Http\Controllers\PostController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::middleware(['auth'])->group(function () {
    Route::get('dashboard', [PostController::class, 'posts'])->name('dashboard');
    Route::get('my_post', [PostController::class, 'myPosts'])->name('posts.my-post');
    Route::post('my_post/store', [PostController::class, 'store'])->name('my-post.store');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
