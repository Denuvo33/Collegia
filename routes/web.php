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
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\CollaborationController;


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

Route::get('/user/transaction', [TransactionController::class, 'index'])->middleware(['auth', 'verified'])->name('user.transaction');
// API endpoint for fetching paginated transactions
Route::get('/user/transaction/paginate', [TransactionController::class, 'getTransactions'])->middleware(['auth', 'verified'])->name('user.transaction.paginate');
// API endpoint for fetching paginated notifications
Route::get('/user/notifications/paginate', [NotificationController::class, 'getNotifications'])->middleware(['auth', 'verified'])->name('user.notifications.paginate');


Route::get('/user/profile', [ProfileController::class, 'index'])->name('user.profile');
// API endpoints for infinite scroll
Route::get('/user/profile/posts', [ProfileController::class, 'posts'])->name('user.profile.posts');
Route::get('/user/profile/comments', [ProfileController::class, 'comments'])->name('user.profile.comments');

Route::get('/user/create-post', [CreatePostController::class, 'index'])->middleware(['auth', 'verified'])->name('user.create-post');

Route::get('/user/post/{hashId}', [HomeController::class, 'details'])->middleware(['auth', 'verified'])->name('user.post.details');

// API endpoint for fetching paginated comments
Route::get('/user/post/{postId}/comments', [CommentController::class, 'getComments'])->middleware(['auth', 'verified'])->name('post.comments.paginate');


Route::get('/user/event', [EventController::class, 'index'])->middleware(['auth', 'verified'])->name('user.event');

Route::get('/user/create-event', [EventController::class, 'create'])->middleware(['auth', 'verified'])->name('user.create-event');

Route::get('/user/create-collaboration', [CollaborationController::class, 'index'])->middleware(['auth', 'verified'])->name('user.create-collaboration');

Route::get('/user/collaboration/{hashId}', [CollaborationController::class, 'show'])->middleware(['auth'])->name('user.collaboration.details');

Route::get('/user/collaboration/{hashId}/edit', [CollaborationController::class, 'edit'])->middleware(['auth', 'verified'])->name('user.collaboration.edit');

Route::get('/user/event/promote/{id}', [EventController::class, 'promoteForm'])->middleware(['auth', 'verified'])->name('user.event.promote.form');

Route::get('/user/event/{hashId}/{title}', [EventController::class, 'show'])->middleware(['auth'])->name('user.details.event');

// This wildcard route must be LAST to avoid catching specific routes above
Route::get('/user/{hashId}/{name}', [HomeController::class, 'user'])->middleware(['auth'])->name('user.details');
// API khusus load posts
Route::get('/user/{hashId}/{name}/posts', [HomeController::class, 'userPosts'])
    ->middleware(['auth'])
    ->name('user.posts');
// API khusus load comments di profile user
Route::get('/user/{hashId}/{name}/comments', [HomeController::class, 'userComments'])
    ->middleware(['auth'])
    ->name('user.comments');

// Payment routes with rate limiting
Route::post('/user/event/initiate-payment', [TransactionController::class, 'initiatePayment'])
    ->middleware(['auth', 'verified', 'throttle:10,1']) // Max 10 attempts per minute
    ->name('user.event.payment.initiate');

Route::post('/user/event/promotion/initiate', [TransactionController::class, 'initiatePromotionPayment'])
    ->middleware(['auth', 'verified', 'throttle:10,1']) // Max 10 attempts per minute
    ->name('user.event.promotion.initiate');

// User Routes Post
Route::post('/user/create-post', [CreatePostController::class, 'store'])->middleware(['auth', 'verified'])->name('user.post.store');

Route::post('/user/create-collaboration', [CollaborationController::class, 'store'])->middleware(['auth', 'verified'])->name('user.collaboration.store');

Route::put('/user/collaboration/{hashId}', [CollaborationController::class, 'update'])->middleware(['auth', 'verified'])->name('user.collaboration.update');

Route::post('/user/collaboration/{hashId}/interest', [CollaborationController::class, 'toggleInterest'])->middleware(['auth'])->name('user.collaboration.interest');

Route::delete('/user/collaboration/{hashId}', [CollaborationController::class, 'destroy'])->middleware(['auth', 'verified'])->name('user.collaboration.delete');

Route::post('/user/create-comment', [CommentController::class, 'store'])->middleware('auth')->name('post.store.comment');

Route::post('/user/post/report', [ReportController::class, 'store'])->middleware('auth')->name('user.post.report');
Route::post('/user/event/report', [ReportController::class, 'reportEvent'])->middleware('auth')->name('user.event.report');
Route::post('/user/comment/report', [ReportController::class, 'reportComment'])->middleware('auth')->name('user.comment.report');

Route::post('/user/create-event', [EventController::class, 'store'])->middleware(['auth', 'verified'])->name('user.event.store');


Route::post('/midtrans/webhook', [TransactionController::class, 'handleWebhook'])
    ->name('midtrans.webhook');

Route::get('/user/transaction/status/{orderId}', [TransactionController::class, 'checkStatus'])
    ->middleware(['auth', 'verified'])
    ->name('transaction.status');

Route::post('/user/transaction/refresh/{orderId}', [TransactionController::class, 'refreshSnapToken'])
    ->middleware(['auth', 'verified'])
    ->name('user.transaction.refresh');

Route::post('/user/transaction/cancel/{orderId}', [TransactionController::class, 'cancelTransaction'])
    ->middleware(['auth', 'verified'])
    ->name('user.transaction.cancel');

//user Routes Destroy
Route::delete('/user/post/comment/${id}', [CommentController::class, 'destroy'])->middleware('auth')->name('user.comment.delete');

Route::delete('/user/post/delete/{id}', [ProfileController::class, 'destroy'])->middleware('auth')->name('user.post.delete');

Route::delete('/user/event/delete/{id}', [EventController::class, 'destroy'])->middleware('auth')->name('user.delete.event');

Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/admin/dashboard', [CommunityController::class, 'adminDashboard'])->name('admin.dashboard');
    Route::get('/admin/user-dashboard', [CommunityController::class, 'fetchUsers'])->name('admin.users');
    Route::get('/admin/report-event', [CommunityController::class, 'reportEvents'])->name('admin.report.events');
    Route::get('/admin/report-post', [CommunityController::class, 'reportPosts'])->name('admin.report.posts');
    Route::get('/admin/report-comment', [CommunityController::class, 'reportComments'])->name('admin.report.comments');
    Route::get('/admin/event', [CommunityController::class, 'fetchEvents'])->name('admin.event');

    Route::post('/admin/user/create', [CommunityController::class, 'storeUsers'])->name('admin.users.store');

    Route::put('/admin/user/update/{id}', [CommunityController::class, 'updateUsers'])->name('admin.users.update');

    Route::delete('/admin/user/delete/{id}', [CommunityController::class, 'deleteUsers'])->name('admin.users.destroy');
    Route::delete('/admin/report/event/delete/{id}', [CommunityController::class, 'deleteReportEvent'])->name('admin.reports.event.destroy');
    Route::delete('/admin/delete/all-event-complete', [CommunityController::class, 'deleteAllCompletedEvents'])->name('admin.events.delete.completed');
    Route::delete('/admin/report/comment/{id}', [CommunityController::class, 'deleteReportComment'])->name('admin.reports.comment.destroy');
    Route::delete('/admin/report/post/{id}', [CommunityController::class, 'deleteReportPost'])->name('admin.reports.post.destroy');
    Route::delete('/admin/event/delete/{id}', [CommunityController::class, 'deleteEvents'])->name('admin.events.destroy');
});

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
