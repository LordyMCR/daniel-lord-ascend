<?php

use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\BookCopyController;
use App\Http\Controllers\BorrowRequestController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware('auth')->group(function () {
    Route::get('/', [BookCopyController::class, 'index'])->name('book-copies.index');
    Route::post('/borrow-requests', [BorrowRequestController::class, 'store'])->name('borrow-requests.store');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/overdue-borrows', [AdminUserController::class, 'showOverdueBorrows'])->name('overdue');
        Route::post('/overdue-borrows/{borrow}/send-overdue-reminder', [AdminUserController::class, 'sendOverdueReminder'])->name('send_reminder');
        Route::post('/overdue-borrows/send-bulk-overdue-reminders', [AdminUserController::class, 'sendBulkOverdueReminders'])->name('send_bulk_reminders');
    });
});


require __DIR__.'/auth.php';
