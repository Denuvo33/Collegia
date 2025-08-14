<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\CreatePostController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('auth/Google');
})->name('home');


// User Routes Get
Route::get('/user/home', [HomeController::class, 'index'])->middleware(['auth', 'verified'])->name('user.home');
Route::get('/user/explore', function () {
    return Inertia::render('user/Explore');
})->middleware(['auth', 'verified'])->name('user.explore');
Route::get('/user/notifications', function () {
    return Inertia::render('user/Notification');
})->middleware(['auth', 'verified'])->name('user.notifications');
Route::get('/user/profile', function () {
    return Inertia::render('user/Profile');
})->middleware(['auth', 'verified'])->name('user.profile');
Route::get('/user/create-post', [CreatePostController::class, 'index'])->middleware(['auth', 'verified'])->name('user.create-post');
Route::get('/user/post/{id}', [HomeController::class, 'details'])->middleware(['auth', 'verified'])->name('user.post.details');

// User Routes Post
Route::post('/user/create-post', [CreatePostController::class, 'store'])->middleware(['auth', 'verified'])->name('user.post.store');
Route::post('/user/create-comment',[CommentController::class,'store'])->middleware('auth')->name('post.store.comment');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
