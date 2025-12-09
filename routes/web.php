<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\CommunityController;
use App\Http\Controllers\CreatePostController;
use App\Http\Controllers\ExploreController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\EventController;


Route::get('/', function () {
    return Inertia::render('auth/Google');
})->name('home');

// Route For Community
Route::get('/community/login', function () {
    return Inertia::render('auth/CommunityLogin');
})->name('community.login');
Route::post('/community/login', [CommunityController::class, 'login'])->name('community.login');


// User Routes Get
Route::get('/user/home', [HomeController::class, 'index'])->middleware(['auth', 'verified'])->name('user.home');

Route::get('/user/explore', [ExploreController::class, 'index'])->middleware(['auth', 'verified'])->name('user.explore');

Route::get('/user/notifications', [NotificationController::class, 'index'])->middleware(['auth', 'verified'])->name('user.notifications');

Route::get('/user/profile', [ProfileController::class, 'index'])->name('user.profile');
// API endpoints for infinite scroll
Route::get('/user/profile/posts', [ProfileController::class, 'posts'])->name('user.profile.posts');
Route::get('/user/profile/comments', [ProfileController::class, 'comments'])->name('user.profile.comments');

Route::get('/user/create-post', [CreatePostController::class, 'index'])->middleware(['auth', 'verified'])->name('user.create-post');

Route::get('/user/post/{hashId}', [HomeController::class, 'details'])->middleware(['auth', 'verified'])->name('user.post.details');

Route::get('/user/{hashId}/{name}', [HomeController::class, 'user'])->middleware(['auth'])->name('user.details');
// API khusus load posts
Route::get('/user/{hashId}/{name}/posts', [HomeController::class, 'userPosts'])
    ->middleware(['auth'])
    ->name('user.posts');
// API khusus load comments di profile user
Route::get('/user/{hashId}/{name}/comments', [HomeController::class, 'userComments'])
    ->middleware(['auth'])
    ->name('user.comments');


Route::get('/user/event', [EventController::class, 'index'])->middleware(['auth', 'verified'])->name('user.event');

Route::get('/user/create-event', [EventController::class, 'create'])->middleware(['auth', 'verified'])->name('user.create-event');

Route::get('/user/event/{hashId}/{title}', [EventController::class, 'show'])->middleware(['auth'])->name('user.details.event');

Route::post('/user/event/promote/{eventId}', [EventController::class, 'promote'])->middleware(['auth', 'verified'])->name('user.event.promote');

// User Routes Post
Route::post('/user/create-post', [CreatePostController::class, 'store'])->middleware(['auth', 'verified'])->name('user.post.store');

Route::post('/user/create-comment', [CommentController::class, 'store'])->middleware('auth')->name('post.store.comment');

Route::post('/user/post/report', [ReportController::class, 'store'])->middleware('auth')->name('user.post.report');
Route::post('/user/event/report', [ReportController::class, 'reportEvent'])->middleware('auth')->name('user.event.report');
Route::post('/user/comment/report', [ReportController::class, 'reportComment'])->middleware('auth')->name('user.comment.report');

Route::post('/user/create-event', [EventController::class, 'store'])->middleware(['auth', 'verified'])->name('user.event.store');

//user Routes Destroy
Route::delete('/user/post/comment/${id}', [CommentController::class, 'destroy'])->middleware('auth')->name('user.comment.delete');

Route::delete('/user/post/delete/{id}', [ProfileController::class, 'destroy'])->middleware('auth')->name('user.post.delete');

Route::delete('/user/event/delete/{id}', [EventController::class, 'destroy'])->middleware('auth')->name('user.delete.event');

Route::middleware('auth', 'admin')->group(function () {
    Route::get('/admin/dashboard', function () {
        return Inertia::render('admin/Dashboard');
    })->name('admin.dashboard');
    Route::get('/admin/user-dashboard', [CommunityController::class, 'fetchUsers'])->name('admin.users');

    Route::post('/admin/user/create', [CommunityController::class, 'storeUsers'])->name('admin.users.store');

    Route::put('/admin/user/update/{id}', [CommunityController::class, 'updateUsers'])->name('admin.users.update');

    Route::delete('/admin/user/delete/{id}', [CommunityController::class, 'deleteUsers'])->name('admin.users.destroy');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
