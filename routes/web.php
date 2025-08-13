<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('auth/Google');
})->name('home');


// User Routes
Route::get('/user/home', function () {
    return Inertia::render('user/Home');
})->middleware(['auth', 'verified'])->name('user.home');
Route::get('/user/explore', function () {
    return Inertia::render('user/Explore');
})->middleware(['auth', 'verified'])->name('user.explore');
Route::get('/user/notifications', function () {
    return Inertia::render('user/Notification');
})->middleware(['auth', 'verified'])->name('user.notifications');
Route::get('/user/profile', function () {
    return Inertia::render('user/Profile');
})->middleware(['auth', 'verified'])->name('user.profile');
Route::get('/user/create-post', function () {
    return Inertia::render('user/CreatePost');
})->middleware(['auth', 'verified'])->name('user.create-post');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
