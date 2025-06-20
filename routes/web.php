<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Http\Controllers\AskController;
use App\Http\Controllers\CustomInstructionsController;

/*
|--------------------------------------------------------------------------
| ROUTES WEB - ARCHITECTURE DE L'APPLICATION
|--------------------------------------------------------------------------
|
| Organisation des routes selon les patterns REST et SPA.
|
| Structure :
| 1. Routes publiques (accueil, auth)
| 2. Routes protégées par authentification
| 3. API endpoints pour les fonctionnalités avancées
|
| Pattern utilisé : Resource Controller avec routes personnalisées pour
| les fonctionnalités spécifiques (streaming, favoris, etc.)
|
*/

/**
 * ROUTE RACINE - REDIRECTION INTELLIGENTE
 *
 * Pattern UX : Redirige automatiquement selon l'état d'authentification
 * - Utilisateur connecté → Interface de chat
 * - Visiteur → Page d'accueil/connexion
 */
Route::get('/', function () {
    // Rediriger les utilisateurs connectés vers l'application
    if (Auth::check()) {
        return redirect()->route('conversations.create');
    }

    // Page d'accueil pour les visiteurs avec liens authentification
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
})->name('home');

/**
 * GROUPE DE ROUTES PROTÉGÉES
 *
 * Middleware Stack :
 * - auth:sanctum : Authentification requise (sessions + API tokens)
 * - auth_session : Gestion des sessions Laravel Jetstream
 * - verified : Email vérifié requis (sécurité supplémentaire)
 *
 * Pattern Security : Toutes les fonctionnalités sensibles sont protégées
 */
Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    /**
     * REDIRECTION DASHBOARD → CHAT
     *
     * Simplification UX : Le dashboard par défaut de Jetstream
     * redirige vers l'interface principale de l'app
     */
    Route::get('/dashboard', function () {
        return redirect()->route('conversations.create');
    })->name('dashboard');

    /**
     * ROUTES INSTRUCTIONS PERSONNALISÉES
     *
     * Pattern Resource Controller : GET/PUT pour configuration utilisateur
     * Permet la personnalisation du comportement de l'IA par utilisateur
     */
    Route::get('/custom-instructions', [CustomInstructionsController::class, 'index'])
        ->name('custom-instructions.index');
    Route::put('/custom-instructions', [CustomInstructionsController::class, 'update'])
        ->name('custom-instructions.update');
});

/**
 * ENDPOINT UTILITAIRE - REFRESH CSRF TOKEN
 *
 * Sécurité : Permet le renouvellement du token CSRF pour les requêtes
 * de longue durée (streaming) sans recharger la page.
 *
 * Technique : Essentiel pour les SPA avec sessions PHP
 */
Route::get('/csrf-token', function () {
    return response()->json([
        'csrf_token' => csrf_token()
    ]);
});

/**
 * ROUTES PRINCIPALES DE L'APPLICATION CHAT
 *
 * Pattern Hybrid : Mélange de routes classiques et d'endpoints API
 * pour supporter à la fois l'interface SPA et les requêtes AJAX
 */

// ROUTES LEGACY - Interface simple (conservées pour compatibilité)
Route::get('/ask', [AskController::class, 'index'])->name('ask.index');
Route::post('/ask', [AskController::class, 'ask'])->name('ask');

/**
 * ROUTES CONVERSATIONS - CŒUR DE L'APPLICATION
 *
 * Architecture RESTful avec extensions pour fonctionnalités spécifiques :
 *
 * Standard REST :
 * - GET /conversations → Liste
 * - GET /conversations/create → Formulaire création
 * - POST /conversations → Créer nouvelle
 * - GET /conversations/{id} → Afficher une conversation
 * - DELETE /conversations/{id} → Supprimer
 *
 * Extensions Personnalisées :
 * - POST /conversations/{id}/messages → Ajouter message
 * - POST/GET /conversations/{id}/stream → Streaming temps réel
 * - POST /conversations/{id}/update-model → Changer modèle IA
 * - POST /conversations/{id}/toggle-favorite → Gestion favoris
 */

// Routes de base (pattern Resource Controller)
Route::get('/conversations', [AskController::class, 'conversationsList'])
    ->name('conversations.index');
Route::get('/conversations/create', [AskController::class, 'createConversation'])
    ->name('conversations.create');
Route::post('/conversations', [AskController::class, 'storeConversation'])
    ->name('conversations.store');
Route::post('/conversations/empty', [AskController::class, 'createEmptyConversation'])
    ->name('conversations.store.empty');
Route::get('/conversations/{conversation}', [AskController::class, 'showConversation'])
    ->name('conversations.show');

// Routes de messages et streaming
Route::post('/conversations/{conversation}/messages', [AskController::class, 'sendMessage'])
    ->name('conversations.messages.store');

/**
 * ROUTE STREAMING - TECHNIQUE SSE AVANCÉE
 *
 * Pattern Hybride : Support GET et POST pour flexibilité client
 * - GET : EventSource (limitations headers mais plus simple)
 * - POST : Fetch/Axios (headers personnalisés, gestion erreurs avancée)
 *
 * Cœur technique : Server-Sent Events pour streaming temps réel
 */
Route::match(['get', 'post'], '/conversations/{conversation}/stream', [AskController::class, 'sendMessageStream'])
    ->name('chat.stream');

// Routes de gestion avancée
Route::post('/conversations/{conversation}/update-model', [AskController::class, 'updateConversationModel'])
    ->name('conversations.update-model');
Route::post('/conversations/{conversation}/update-title', [AskController::class, 'updateConversationTitle'])
    ->name('conversations.update-title');
Route::post('/conversations/{conversation}/toggle-favorite', [AskController::class, 'toggleFavorite'])
    ->name('conversations.toggle-favorite');

// Routes de suppression (simple et multiple)
Route::delete('/conversations/{conversation}', [AskController::class, 'destroyConversation'])
    ->name('conversations.destroy');
Route::delete('/conversations', [AskController::class, 'destroyMultipleConversations'])
    ->name('conversations.destroy-multiple');
