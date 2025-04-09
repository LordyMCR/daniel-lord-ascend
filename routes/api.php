<?php

use App\Http\Controllers\BorrowAPIController;
use App\Http\Controllers\ReturnAPIController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/borrow', [BorrowAPIController::class, 'store'])->name('borrows.store');
Route::post('/return', [ReturnAPIController::class, 'store'])->name('returns.store');

