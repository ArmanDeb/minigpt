<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\ChatService;
use App\Http\Requests\StoreMessageRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

/**
 * CONTRÔLEUR PRINCIPAL - GESTION DES CONVERSATIONS ET MESSAGES
 *
 * Pattern MVC : Ce contrôleur orchestre toutes les interactions
 * entre l'utilisateur et le système de chat IA.
 *
 * Responsabilités principales :
 * - Gestion du cycle de vie des conversations
 * - Coordination des messages (envoi, réception, streaming)
 * - Interface entre Vue.js (Inertia) et les services backend
 * - Validation des données et contrôle d'accès
 */
class AskController extends Controller
{
    protected $chatService;

    /**
     * INJECTION DE DÉPENDANCE - PATTERN DI
     *
     * Le ChatService est injecté automatiquement par Laravel,
     * facilitant les tests et la maintenance du code.
     */
    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    /**
     * REDIRECTION AUTOMATIQUE VERS L'INTERFACE DE CHAT
     *
     * Simplifie l'UX en évitant une page intermédiaire inutile
     */
    public function index()
    {
        // Rediriger directement vers la création d'une nouvelle conversation
        return redirect()->route('conversations.create');
    }

    /**
     * ANCIEN ENDPOINT - CONSERVÉ POUR COMPATIBILITÉ
     *
     * Cette méthode n'est plus utilisée dans l'interface actuelle
     * mais pourrait servir pour des appels API directs.
     */
    public function ask(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'model' => 'required|string',
        ]);

        try {
            // Sauvegarder le modèle choisi par l'utilisateur
            if (Auth::check()) {
                Auth::user()->update(['preferred_model' => $request->model]);
            }

            $messages = [[
                'role' => 'user',
                'content' => $request->message,
            ]];

            $response = (new ChatService())->sendMessage(
                messages: $messages,
                model: $request->model
            );

            return redirect()->back()->with('message', $response);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * AFFICHAGE DE L'INTERFACE DE CHAT PRINCIPALE
     *
     * Point d'entrée principal de l'application.
     * Prépare toutes les données nécessaires pour Vue.js.
     *
     * Pattern Data Transfer Object : Toutes les données sont
     * structurées et transmises en une fois à Inertia.
     */
    public function createConversation()
    {
        // 1. RÉCUPÉRATION DES MODÈLES IA DISPONIBLES (avec cache)
        $models = (new ChatService())->getModels();

        // 2. DÉTERMINATION DU MODÈLE PRÉFÉRÉ
        // Utilise le modèle sauvegardé de l'utilisateur ou le défaut
        $selectedModel = Auth::check() && Auth::user()->preferred_model
            ? Auth::user()->preferred_model
            : ChatService::DEFAULT_MODEL;

        // 3. RÉCUPÉRATION DES CONVERSATIONS POUR LA SIDEBAR
        // Pattern Eager Loading : On charge les relations en une requête
        // pour éviter le problème N+1
        $conversations = Auth::check()
            ? Auth::user()->conversations()
                ->with(['messages' => function ($query) {
                    $query->latest()->limit(1); // Seul le dernier message pour l'aperçu
                }])
                ->orderBy('is_favorite', 'desc')    // Favoris en premier
                ->orderBy('last_activity_at', 'desc') // Puis par activité récente
                ->get()
            : [];

        // 4. RENDU VUE.JS AVEC INERTIA
        // Pattern Single Page Application : Une seule page Vue.js
        // qui gère tous les états (create, show, list)
        return Inertia::render('Ask/ChatInterface', [
            'models' => $models,
            'selectedModel' => $selectedModel,
            'conversations' => $conversations,
            'mode' => 'create' // Indique à Vue.js le mode d'affichage
        ]);
    }

    /**
     * CRÉATION D'UNE NOUVELLE CONVERSATION AVEC PREMIER MESSAGE
     *
     * Workflow complet :
     * 1. Validation des données
     * 2. Création de la conversation
     * 3. Sauvegarde du message utilisateur
     * 4. Appel à l'IA pour la réponse
     * 5. Génération automatique du titre
     * 6. Redirection vers la conversation créée
     */
    public function storeConversation(Request $request)
    {
        // VALIDATION DES ENTRÉES UTILISATEUR
        $request->validate([
            'message' => 'required|string',
            'model' => 'required|string',
        ]);

        // SAUVEGARDE DU MODÈLE PRÉFÉRÉ - UX IMPROVEMENT
        // L'utilisateur n'a pas besoin de re-sélectionner à chaque fois
        if (Auth::check()) {
            Auth::user()->update(['preferred_model' => $request->model]);
        }

        // CRÉATION DE LA CONVERSATION
        // Titre temporaire basé sur le début du message
        $conversation = Conversation::create([
            'user_id' => Auth::id(),
            'title' => substr($request->message, 0, 50) . (strlen($request->message) > 50 ? '...' : ''),
            'model' => $request->model,
            'last_activity_at' => now(),
        ]);

        // SAUVEGARDE DU MESSAGE UTILISATEUR
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $request->message,
        ]);

        // APPEL À L'IA ET GESTION DE LA RÉPONSE
        try {
            $messages = [[
                'role' => 'user',
                'content' => $request->message,
            ]];

            // Appel synchrone pour la première création
            $response = (new ChatService())->sendMessage(
                messages: $messages,
                model: $request->model
            );

            // SAUVEGARDE DE LA RÉPONSE IA
            $assistantMessage = Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $response,
            ]);

            // GÉNÉRATION AUTOMATIQUE DU TITRE
            // Utilise l'IA pour créer un titre pertinent
            $this->generateConversationTitle($conversation, $request->message, $response);

            // RECHARGEMENT AVEC RELATIONS
            $conversation->load('messages');

            // RÉPONSE ADAPTÉE AU TYPE DE REQUÊTE
            if ($request->wantsJson()) {
                // Pour les appels AJAX
                return response()->json([
                    'conversation' => $conversation,
                    'userMessage' => $message,
                    'assistantMessage' => $assistantMessage
                ]);
            }

            // Redirection classique
            return redirect()->route('conversations.show', $conversation);
        } catch (\Exception $e) {
            // GESTION D'ERREURS ADAPTÉE
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * GÉNÉRATION AUTOMATIQUE DU TITRE DE CONVERSATION
     *
     * Fonctionnalité UX avancée : Utilise l'IA pour créer
     * des titres pertinents basés sur le contenu de la conversation.
     *
     * Pattern Strategy : L'IA elle-même génère le titre,
     * garantissant une cohérence avec le contenu.
     */
    private function generateConversationTitle($conversation, $userMessage, $aiResponse)
    {
        try {
            // PROMPT SPÉCIALISÉ POUR LA GÉNÉRATION DE TITRE
            $prompt = "Génère un titre court (maximum 5 mots) pour une conversation basée sur ce message utilisateur et cette réponse AI. Ne mets pas de guillemets autour du titre.\n\nMessage utilisateur: {$userMessage}\n\nRéponse AI: {$aiResponse}";

            $messages = [[
                'role' => 'user',
                'content' => $prompt,
            ]];

            // APPEL IA POUR GÉNÉRER LE TITRE
            $title = (new ChatService())->sendMessage(
                messages: $messages,
                model: $conversation->model
            );

            // NETTOYAGE ET LIMITATION DU TITRE
            $title = substr(trim($title), 0, 100);

            // MISE À JOUR EN BASE DE DONNÉES
            $conversation->update(['title' => $title]);
        } catch (\Exception $e) {
            // EN CAS D'ERREUR, ON GARDE LE TITRE PAR DÉFAUT
            // Principe : Ne jamais casser l'expérience utilisateur
            logger()->error('Erreur lors de la génération du titre:', ['error' => $e->getMessage()]);
        }
    }

    /**
     * AFFICHAGE D'UNE CONVERSATION EXISTANTE
     *
     * Charge une conversation avec tous ses messages
     * et prépare l'interface pour la consultation/continuation.
     */
    public function showConversation(Request $request, Conversation $conversation)
    {
        // CONTRÔLE D'ACCÈS - SÉCURITÉ CRITIQUE
        // Seul le propriétaire peut voir ses conversations
        if ($conversation->user_id !== Auth::id()) {
            abort(403);
        }

        // CHARGEMENT OPTIMISÉ DES RELATIONS
        $conversation->load('messages');
        $models = (new ChatService())->getModels();

        // RÉCUPÉRATION DE TOUTES LES CONVERSATIONS POUR LA SIDEBAR
        $conversations = Auth::user()->conversations()
            ->with(['messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->orderBy('is_favorite', 'desc')
            ->orderBy('last_activity_at', 'desc')
            ->get();

        // RENDU AVEC MODE 'SHOW'
        return Inertia::render('Ask/ChatInterface', [
            'models' => $models,
            'selectedModel' => $conversation->model,
            'conversations' => $conversations,
            'conversation' => $conversation,
            'mode' => 'show' // Vue.js adapte l'interface
        ]);
    }

    /**
     * LISTE DES CONVERSATIONS - MODE LISTE
     */
    public function conversationsList()
    {
        $conversations = Auth::user()->conversations()
            ->with(['messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->orderBy('is_favorite', 'desc')
            ->orderBy('last_activity_at', 'desc')
            ->get();

        $models = (new ChatService())->getModels();
        $selectedModel = Auth::check() && Auth::user()->preferred_model
            ? Auth::user()->preferred_model
            : ChatService::DEFAULT_MODEL;

        return Inertia::render('Ask/ChatInterface', [
            'conversations' => $conversations,
            'models' => $models,
            'selectedModel' => $selectedModel,
            'mode' => 'list'
        ]);
    }

    /**
     * ENVOI DE MESSAGE DANS CONVERSATION EXISTANTE (NON-STREAMING)
     */
    public function sendMessage(Request $request, Conversation $conversation)
    {
        // Vérifier que la conversation appartient à l'utilisateur authentifié
        if ($conversation->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'message' => 'required|string',
            'model' => 'sometimes|string',
        ]);

        // Créer le message utilisateur
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $request->message,
        ]);

        // Mettre à jour la dernière activité
        $conversation->update([
            'last_activity_at' => now(),
            'model' => $request->model ?? $conversation->model,
        ]);

        try {
            // Préparer les messages pour l'API
            $apiMessages = $conversation
                ->messages()
                ->get()
                ->map(function ($message) {
                    return [
                        'role' => $message->role,
                        'content' => $message->content,
                    ];
                })
                ->toArray();

            $response = (new ChatService())->sendMessage(
                messages: $apiMessages,
                model: $request->model ?? $conversation->model
            );

            $assistantMessage = Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $response,
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'userMessage' => $message,
                    'assistantMessage' => $assistantMessage
                ]);
            }

            return redirect()->back();
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * SUPPRESSION D'UNE CONVERSATION
     */
    public function destroyConversation(Request $request, Conversation $conversation)
    {
        // Vérifier que la conversation appartient à l'utilisateur authentifié
        if ($conversation->user_id !== Auth::id()) {
            abort(403);
        }

        $conversation->delete();

        // Rediriger selon le contexte d'origine
        $fromPage = $request->input('from_page', '');

        if ($fromPage === 'list') {
            return redirect()->route('conversations.index')
                ->with('success', 'Conversation supprimée avec succès.');
        }

        return redirect()->route('conversations.create')
            ->with('success', 'Conversation supprimée avec succès.');
    }

    /**
     * MISE À JOUR DU MODÈLE D'UNE CONVERSATION
     */
    public function updateConversationModel(Request $request, Conversation $conversation)
    {
        // Vérifier que la conversation appartient à l'utilisateur authentifié
        if ($conversation->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'model' => 'required|string',
        ]);

        // Mettre à jour le modèle de la conversation
        $conversation->update([
            'model' => $request->model,
        ]);

        // Mettre à jour le modèle préféré de l'utilisateur
        Auth::user()->update(['preferred_model' => $request->model]);

        return response()->json([
            'success' => true,
            'model' => $request->model,
        ]);
    }

    /**
     * STREAMING DE MESSAGES EN TEMPS RÉEL - CŒUR DU SYSTÈME
     *
     * Cette méthode implémente le streaming Server-Sent Events (SSE)
     * pour afficher les réponses de l'IA en temps réel.
     *
     * Workflow technique :
     * 1. Validation et autorisation
     * 2. Sauvegarde du message utilisateur
     * 3. Préparation du contexte de conversation
     * 4. Streaming de la réponse IA chunk par chunk
     * 5. Sauvegarde finale du message complet
     *
     * Pattern Observer : Le client écoute les chunks via EventSource
     */
    public function sendMessageStream(Request $request, Conversation $conversation)
    {
        // CONTRÔLE D'ACCÈS STRICT
        if ($conversation->user_id !== Auth::id()) {
            abort(403);
        }

        // GESTION MULTI-PROTOCOLE : GET (EventSource) ET POST (Fetch)
        // Flexibilité pour différents clients frontend
        if ($request->isMethod('get')) {
            // EventSource : Paramètres dans l'URL (limitations des headers)
            $message = $request->query('message');
            $modelId = $request->query('model', $conversation->model);
        } else {
            // Fetch/Axios : Corps JSON avec headers personnalisés
            $data = $request->json()->all();
            $message = $data['message'] ?? null;
            $modelId = $data['model'] ?? $conversation->model;
        }

        // VALIDATION CRITIQUE DU MESSAGE
        if (empty($message)) {
            abort(400, 'Paramètre message requis');
        }

        // 1. SAUVEGARDE IMMÉDIATE DU MESSAGE UTILISATEUR
        // Important : Sauvegarder avant l'appel IA pour éviter les pertes
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $message,
        ]);

        // 2. MISE À JOUR DES MÉTADONNÉES DE CONVERSATION
        $conversation->update([
            'last_activity_at' => now(),        // Pour le tri chronologique
            'model' => $modelId,                 // Changement de modèle possible
        ]);

        // 3. SYNCHRONISATION DU MODÈLE PRÉFÉRÉ UTILISATEUR
        Auth::user()->update(['preferred_model' => $modelId]);

        // 4. PRÉPARATION DU CONTEXTE CONVERSATIONNEL
        // Récupération de TOUS les messages pour maintenir le contexte IA
        $apiMessages = $conversation
            ->messages()
            ->get()
            ->map(function ($message) {
                // Format requis par l'API OpenAI
                return [
                    'role' => $message->role,
                    'content' => $message->content,
                ];
            })
            ->toArray();

        // 5. STREAMING RESPONSE - TECHNIQUE SSE
        return response()->stream(function () use ($conversation, $apiMessages) {
            $fullResponse = ''; // Accumulation pour sauvegarde finale

            // APPEL SERVICE STREAMING
            $stream = $this->chatService->stream(
                messages: $apiMessages,
                model: $conversation->model,
                temperature: 0.7 // Équilibre créativité/précision
            );

            // ITÉRATION SUR CHAQUE CHUNK DE RÉPONSE
            foreach ($stream as $response) {
                $content = $response->choices[0]->delta->content ?? '';
                $fullResponse .= $content;

                // ENVOI IMMÉDIAT AU CLIENT
                echo $content; // Raw content pour compatibilité avec useStream
                ob_flush();    // Force l'envoi du buffer PHP
                flush();       // Force l'envoi du buffer serveur web
            }

            // 6. SAUVEGARDE FINALE DU MESSAGE COMPLET
            // Une fois le streaming terminé, on sauvegarde la réponse complète
            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $fullResponse,
            ]);
        }, 200, [
            // HEADERS ESSENTIELS POUR SSE
            'Cache-Control' => 'no-cache',           // Évite la mise en cache
            'Content-Type' => 'text/event-stream',   // Type MIME pour SSE
            'X-Accel-Buffering' => 'no',            // Nginx : pas de buffer
        ]);
    }

    /**
     * CRÉATION D'UNE CONVERSATION VIDE SANS MESSAGE INITIAL
     */
    public function createEmptyConversation(Request $request)
    {
        $request->validate([
            'model' => 'required|string',
        ]);

        // Sauvegarder le modèle choisi par l'utilisateur
        if (Auth::check()) {
            Auth::user()->update(['preferred_model' => $request->model]);
        }

        $conversation = Conversation::create([
            'user_id' => Auth::id(),
            'title' => 'Nouvelle conversation',
            'model' => $request->model,
            'last_activity_at' => now(),
        ]);

        // Recharger la conversation
        $conversation->load('messages');

        if ($request->wantsJson()) {
            return response()->json([
                'conversation' => $conversation
            ]);
        }

        return redirect()->route('conversations.show', $conversation);
    }

    /**
     * MISE À JOUR DU TITRE DE CONVERSATION BASÉ SUR LE PREMIER MESSAGE
     */
    public function updateConversationTitle(Request $request, Conversation $conversation)
    {
        // Vérifier que la conversation appartient à l'utilisateur authentifié
        if ($conversation->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'message' => 'required|string',
        ]);

        // Obtenir le premier message utilisateur et la réponse IA
        $userMessage = $request->message;

        // Obtenir la dernière réponse IA
        $aiResponse = $conversation->messages()
            ->where('role', 'assistant')
            ->latest()
            ->first();

        if ($aiResponse) {
            // Générer le titre en utilisant le premier message utilisateur et la réponse IA
            $this->generateConversationTitle($conversation, $userMessage, $aiResponse->content);

            // Retourner la conversation mise à jour
            return response()->json([
                'success' => true,
                'conversation' => $conversation->fresh()
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Aucune réponse IA trouvée pour générer le titre'
        ], 400);
    }

    /**
     * BASCULER LE STATUT FAVORI DE LA CONVERSATION
     */
    public function toggleFavorite(Request $request, Conversation $conversation)
    {
        // Vérifier que la conversation appartient à l'utilisateur authentifié
        if ($conversation->user_id !== Auth::id()) {
            abort(403);
        }

        $conversation->update([
            'is_favorite' => !$conversation->is_favorite,
        ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_favorite' => $conversation->is_favorite,
            ]);
        }

        return redirect()->back();
    }

    /**
     * SUPPRIMER PLUSIEURS CONVERSATIONS
     */
    public function destroyMultipleConversations(Request $request)
    {
        $request->validate([
            'conversation_ids' => 'required|array|min:1',
            'conversation_ids.*' => 'required|integer|exists:conversations,id',
        ]);

        $conversationIds = $request->conversation_ids;

        // Vérifier que toutes les conversations appartiennent à l'utilisateur authentifié
        $conversations = Conversation::whereIn('id', $conversationIds)
            ->where('user_id', Auth::id())
            ->get();

        if ($conversations->count() !== count($conversationIds)) {
            abort(403, 'Vous ne pouvez supprimer que vos propres conversations');
        }

        // Supprimer toutes les conversations
        Conversation::whereIn('id', $conversationIds)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Conversations supprimées avec succès',
            'deleted_count' => count($conversationIds)
        ]);
    }
}
