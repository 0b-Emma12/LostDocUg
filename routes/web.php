<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\MatchReviewController;
use App\Http\Controllers\Admin\StationDropoffController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DirectoryController::class, 'home'])->name('home');
Route::view('/privacy', 'privacy')->name('privacy');

Route::get('/report/{kind}', function (string $kind) {
    abort_unless(in_array($kind, ['lost', 'found'], true), 404);

    return redirect()->route('reports.create', ['kind' => $kind]);
});

Route::middleware('guest')->group(function (): void {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('throttle:5,1')->name('register.store');
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('verification.send');
});

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/found-ids', [DirectoryController::class, 'index'])->name('directory.index');
    Route::get('/found-ids/{report}', [DirectoryController::class, 'show'])->name('directory.show');
    Route::post('/found-ids/{report}/claim', [DirectoryController::class, 'claim'])->middleware('throttle:10,1')->name('directory.claim');
    Route::get('/reports/create', [ReportController::class, 'create'])->name('reports.create');
    Route::post('/reports', [ReportController::class, 'store'])->middleware('throttle:10,1')->name('reports.store');
    Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    Route::post('/reports/drafts', [ReportController::class, 'saveDraft'])->middleware('throttle:30,1')->name('reports.drafts.save');
    Route::get('/reports/drafts/{draftKey}', [ReportController::class, 'loadDraft'])->name('reports.drafts.load');
    Route::post('/reports/{report}/images', [ReportController::class, 'uploadImage'])->middleware('throttle:10,1')->name('reports.images.store');
    Route::get('/reports/{report}/images/{image}', [ReportController::class, 'image'])->scopeBindings()->name('reports.images.show');
    Route::delete('/reports/{report}/images/{image}', [ReportController::class, 'destroyImage'])->scopeBindings()->name('reports.images.destroy');

    Route::middleware(['role:reviewer,admin'])->prefix('admin')->group(function (): void {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');
        Route::patch('/reports/{report}/status', [AdminDashboardController::class, 'updateReportStatus'])->name('admin.reports.status');
        Route::patch('/reports/{report}/dropoff', [StationDropoffController::class, 'update'])->name('admin.reports.dropoff');
        Route::patch('/claims/{claim}', [AdminDashboardController::class, 'updateClaim'])->name('admin.claims.update');
        Route::get('/matches', [MatchReviewController::class, 'index'])->name('admin.matches.index');
        Route::get('/matches/{match}', [MatchReviewController::class, 'show'])->name('admin.matches.show');
        Route::patch('/matches/{match}', [MatchReviewController::class, 'update'])->name('admin.matches.update');
    });
});
