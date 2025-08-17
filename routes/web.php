<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Http\Controllers\AskController;
use App\Http\Controllers\CustomInstructionsController;

Route::get('/', function () {
    // Rediriger les utilisateurs connectés vers l'application
    if (Auth::check()) {
        return redirect()->route('conversations.create');
    }

    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('home');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    // Rediriger dashboard vers l'application principale
    Route::get('/dashboard', function () {
        return redirect()->route('conversations.create');
    })->name('dashboard');

    // Custom Instructions routes
    Route::get('/custom-instructions', [CustomInstructionsController::class, 'index'])->name('custom-instructions.index');
    Route::put('/custom-instructions', [CustomInstructionsController::class, 'update'])->name('custom-instructions.update');

    // Ask AI routes
    Route::get('/ask', [AskController::class, 'index'])->name('ask.index');
    Route::post('/ask', [AskController::class, 'ask'])->name('ask');

    // Conversation management routes (now handled by AskController)
    Route::get('/conversations', [AskController::class, 'conversationsList'])->name('conversations.index');
    Route::get('/conversations/create', [AskController::class, 'createConversation'])->name('conversations.create');
    Route::post('/conversations', [AskController::class, 'storeConversation'])->name('conversations.store');
    Route::post('/conversations/empty', [AskController::class, 'createEmptyConversation'])->name('conversations.store.empty');
    Route::get('/conversations/{conversation}', [AskController::class, 'showConversation'])->name('conversations.show');
    Route::post('/conversations/{conversation}/messages', [AskController::class, 'sendMessage'])->name('conversations.messages.store');
    Route::match(['get', 'post'], '/conversations/{conversation}/stream', [AskController::class, 'sendMessageStream'])->name('chat.stream');
    Route::post('/conversations/{conversation}/update-model', [AskController::class, 'updateConversationModel'])->name('conversations.update-model');
    Route::post('/conversations/{conversation}/update-title', [AskController::class, 'updateConversationTitle'])->name('conversations.update-title');
    Route::post('/conversations/{conversation}/toggle-favorite', [AskController::class, 'toggleFavorite'])->name('conversations.toggle-favorite');
    Route::delete('/conversations/{conversation}', [AskController::class, 'destroyConversation'])->name('conversations.destroy');
    Route::delete('/conversations', [AskController::class, 'destroyMultipleConversations'])->name('conversations.destroy-multiple');
});
