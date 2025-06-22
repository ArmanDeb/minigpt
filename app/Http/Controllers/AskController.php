<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\ChatService;
use App\Http\Requests\StoreMessageRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class AskController extends Controller
{
    protected $chatService;

    public function __construct(ChatService $chatService)
    {
        $this->chatService = $chatService;
    }

    public function index()
    {
        // Rediriger directement vers la création d'une nouvelle conversation
        return redirect()->route('conversations.create');
    }

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
     * Show the form for creating a new conversation.
     */
    public function createConversation()
    {
        $models = (new ChatService())->getModels();
        $selectedModel = Auth::check() && Auth::user()->preferred_model
            ? Auth::user()->preferred_model
            : ChatService::DEFAULT_MODEL;

        // Get user's conversations for the sidebar
        $conversations = Auth::check()
            ? Auth::user()->conversations()
                ->with(['messages' => function ($query) {
                    $query->latest()->limit(1);
                }])
                ->orderBy('is_favorite', 'desc')
                ->orderBy('last_activity_at', 'desc')
                ->get()
            : [];

        return Inertia::render('Ask/ChatInterface', [
            'models' => $models,
            'selectedModel' => $selectedModel,
            'conversations' => $conversations,
            'mode' => 'create'
        ]);
    }

    /**
     * Store a newly created conversation.
     */
    public function storeConversation(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'model' => 'required|string',
        ]);

        // Sauvegarder le modèle choisi par l'utilisateur
        if (Auth::check()) {
            Auth::user()->update(['preferred_model' => $request->model]);
        }

        $conversation = Conversation::create([
            'user_id' => Auth::id(),
            'title' => substr($request->message, 0, 50) . (strlen($request->message) > 50 ? '...' : ''),
            'model' => $request->model,
            'last_activity_at' => now(),
        ]);

        // Create the user message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $request->message,
        ]);

        // Get AI response
        try {
            $messages = [[
                'role' => 'user',
                'content' => $request->message,
            ]];

            $response = (new ChatService())->sendMessage(
                messages: $messages,
                model: $request->model
            );

            // Create the assistant message
            $assistantMessage = Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $response,
            ]);

            // Générer un titre pour la conversation
            $this->generateConversationTitle($conversation, $request->message, $response);

            // Recharger la conversation avec ses messages
            $conversation->load('messages');

            if ($request->wantsJson()) {
                return response()->json([
                    'conversation' => $conversation,
                    'userMessage' => $message,
                    'assistantMessage' => $assistantMessage
                ]);
            }

            return redirect()->route('conversations.show', $conversation);
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Génère un titre pour la conversation en utilisant l'API
     */
    private function generateConversationTitle($conversation, $userMessage, $aiResponse)
    {
        try {
            $prompt = "Génère un titre court (maximum 5 mots) pour une conversation basée sur ce message utilisateur et cette réponse AI. Ne mets pas de guillemets autour du titre.\n\nMessage utilisateur: {$userMessage}\n\nRéponse AI: {$aiResponse}";

            $messages = [[
                'role' => 'user',
                'content' => $prompt,
            ]];

            $title = (new ChatService())->sendMessage(
                messages: $messages,
                model: $conversation->model
            );

            // Limiter le titre à 100 caractères
            $title = substr(trim($title), 0, 100);

            // Mettre à jour le titre de la conversation
            $conversation->update(['title' => $title]);
        } catch (\Exception $e) {
            // En cas d'erreur, on garde le titre par défaut
            logger()->error('Erreur lors de la génération du titre:', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified conversation.
     */
    public function showConversation(Request $request, Conversation $conversation)
    {
        // Check if the conversation belongs to the authenticated user
        if ($conversation->user_id !== Auth::id()) {
            abort(403);
        }

        $conversation->load('messages');
        $models = (new ChatService())->getModels();

        // Get user's conversations for the sidebar
        $conversations = Auth::user()->conversations()
            ->with(['messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->orderBy('is_favorite', 'desc')
            ->orderBy('last_activity_at', 'desc')
            ->get();

        // Récupérer le message initial s'il existe
        $initialMessage = $request->query('initial_message', '');

        return Inertia::render('Ask/ChatInterface', [
            'conversation' => $conversation,
            'models' => $models,
            'conversations' => $conversations,
            'mode' => 'show',
            'initialMessage' => $initialMessage
        ]);
    }

    /**
     * Display a listing of the conversations.
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
     * Send a message in the conversation.
     */
    public function sendMessage(Request $request, Conversation $conversation)
    {
        // Check if the conversation belongs to the authenticated user
        if ($conversation->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'message' => 'required|string',
            'model' => 'string',
        ]);

        // Si un modèle est spécifié, on met à jour le modèle de la conversation et le modèle préféré de l'utilisateur
        if ($request->has('model') && $request->model) {
            $conversation->update(['model' => $request->model]);
            Auth::user()->update(['preferred_model' => $request->model]);
        }

        // Create the user message
        $message = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $request->message,
        ]);

        // Update conversation last activity
        $conversation->update([
            'last_activity_at' => now(),
        ]);

        // Get AI response
        try {
            $messages = $conversation->messages()
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($msg) {
                    return [
                        'role' => $msg->role,
                        'content' => $msg->content,
                    ];
                })
                ->toArray();

            $response = (new ChatService())->sendMessage(
                messages: $messages,
                model: $conversation->model
            );

            // Create the assistant message
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
     * Remove the specified conversation.
     */
    public function destroyConversation(Request $request, Conversation $conversation)
    {
        // Check if the conversation belongs to the authenticated user
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
     * Update the model of a conversation.
     */
    public function updateConversationModel(Request $request, Conversation $conversation)
    {
        // Check if the conversation belongs to the authenticated user
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
     * Send a message in the conversation with streaming response.
     */
    public function sendMessageStream(Request $request, Conversation $conversation)
    {
        // Check if the conversation belongs to the authenticated user
        if ($conversation->user_id !== Auth::id()) {
            abort(403);
        }

        // Handle both POST and GET requests (GET for EventSource)
        if ($request->isMethod('get')) {
            // For EventSource requests, get message from query parameters
            $message = $request->query('message');
            $modelId = $request->query('model', $conversation->model);
        } else {
            // For POST requests (fetch/axios), get from JSON body
            $data = $request->json()->all();
            $message = $data['message'] ?? null;
            $modelId = $data['model'] ?? $conversation->model;
        }

        if (empty($message)) {
            abort(400, 'Message parameter is required');
        }

        // Créer le message utilisateur
        Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $message,
        ]);

        // Update conversation last activity
        $conversation->update([
            'last_activity_at' => now(),
            'model' => $modelId, // Update model if provided
        ]);

        // Update user's preferred model
        Auth::user()->update(['preferred_model' => $modelId]);

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

        return response()->stream(function () use ($conversation, $apiMessages) {
            $fullResponse = '';

            $stream = $this->chatService->stream(
                messages: $apiMessages,
                model: $conversation->model,
                temperature: 0.7
            );

            foreach ($stream as $response) {
                $content = $response->choices[0]->delta->content ?? '';
                $fullResponse .= $content;
                echo $content; // Send raw content without "data:" prefix for useStream
                ob_flush();
                flush();
            }

            // Créer le message de l'assistant avec la réponse complète
            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $fullResponse,
            ]);
        }, 200, [
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'text/event-stream',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * Create an empty conversation without initial message.
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
     * Update conversation title based on the first message.
     */
    public function updateConversationTitle(Request $request, Conversation $conversation)
    {
        // Check if the conversation belongs to the authenticated user
        if ($conversation->user_id !== Auth::id()) {
            abort(403);
        }

        $request->validate([
            'message' => 'required|string',
        ]);

        // Get the first user message and AI response
        $userMessage = $request->message;

        // Get the last AI response
        $aiResponse = $conversation->messages()
            ->where('role', 'assistant')
            ->latest()
            ->first();

        if ($aiResponse) {
            // Generate title using the first user message and AI response
            $this->generateConversationTitle($conversation, $userMessage, $aiResponse->content);

            // Return the updated conversation
            return response()->json([
                'success' => true,
                'conversation' => $conversation->fresh()
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'No AI response found to generate title'
        ], 400);
    }

    /**
     * Toggle favorite status of the conversation.
     */
    public function toggleFavorite(Request $request, Conversation $conversation)
    {
        // Check if the conversation belongs to the authenticated user
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
     * Remove multiple conversations.
     */
    public function destroyMultipleConversations(Request $request)
    {
        $request->validate([
            'conversation_ids' => 'required|array|min:1',
            'conversation_ids.*' => 'required|integer|exists:conversations,id',
        ]);

        $conversationIds = $request->conversation_ids;

        // Check if all conversations belong to the authenticated user
        $conversations = Conversation::whereIn('id', $conversationIds)
            ->where('user_id', Auth::id())
            ->get();

        if ($conversations->count() !== count($conversationIds)) {
            abort(403, 'You can only delete your own conversations');
        }

        // Delete all the conversations
        Conversation::whereIn('id', $conversationIds)
            ->where('user_id', Auth::id())
            ->delete();

        return redirect()->route('conversations.index')
            ->with('success', count($conversationIds) . ' conversation(s) supprimée(s) avec succès.');
    }
}
