<?php

use App\Http\Controllers\Backoffice\CaptainController;
use App\Http\Controllers\Backoffice\DashboardController;
use App\Http\Controllers\Backoffice\OrderController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web / Backoffice shell
|--------------------------------------------------------------------------
|
| Routes here use the `web` middleware group (sessions, cookies, CSRF on unsafe
| verbs). Mount your React/Vue dashboard SPA and protect it with `auth`.
|
| External machine clients should NOT use these routes; they belong under
| routes/api.php and routes/external.php with X-API-TOKEN (ExternalOrderController).
|--------------------------------------------------------------------------
*/

Route::view('/', 'welcome');

Route::middleware(['auth'])->prefix('crm')->name('crm.')->group(function (): void {
    Route::get('/dashboard', [DashboardController::class, 'page'])->name('dashboard');
    Route::get('/dashboard/api/summary', [DashboardController::class, 'summary'])->name('dashboard.api.summary');
    Route::get('/dashboard/api/analytics', [DashboardController::class, 'analytics'])->name('dashboard.api.analytics');

    Route::resource('captains', CaptainController::class)->except(['show']);

    Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/follow-up', [OrderController::class, 'followUpQueue'])->name('orders.follow-up');
    Route::post('/orders/{order}/follow-up', [OrderController::class, 'updateFollowUp'])->name('orders.follow-up.update');
    Route::post('/orders/{order}/log-followup', [OrderController::class, 'logFollowUp'])->name('orders.log-followup');
});

/** توافق مع المسارات القديمة للوحة */
Route::middleware(['auth'])->group(function (): void {
    Route::redirect('/dashboard', '/crm/dashboard')->name('dashboard');
});

/*
|--------------------------------------------------------------------------
| Auth scaffolding
|--------------------------------------------------------------------------
|
| After running `php artisan install:api` or Breeze/Vue, add:
|   require __DIR__.'/auth.php';
| Keep CSRF protection enabled for all state-changing backoffice requests.
|--------------------------------------------------------------------------
*/
