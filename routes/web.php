<?php

use App\Http\Controllers\ChatRoomController;
use App\Http\Controllers\SendMessageController;
use Illuminate\Support\Facades\Route;

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

require __DIR__.'/auth.php';

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/{room?}', ChatRoomController::class)->name('dashboard');
    Route::post('/message', SendMessageController::class)->name('send.message');
    Route::get('/api/messages/{room}', [App\Http\Controllers\MessageController::class, 'index'])->name('messages.index');
    Route::post('/api/messages', [App\Http\Controllers\MessageController::class, 'store'])->name('messages.store');

    // AI Chat routes
    Route::post('/api/ai/chat', [App\Http\Controllers\AIChatController::class, 'processMessage'])->name('ai.chat');
    Route::get('/api/ai/status', [App\Http\Controllers\AIChatController::class, 'getStatus'])->name('ai.status');
});
