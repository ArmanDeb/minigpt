<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Message;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ConversationController extends Controller
{
    /**
     * Display a listing of the conversations.
     */
    public function index()
    {
        $conversations = Auth::user()->conversations()
            ->with(['messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->orderBy('is_favorite', 'desc')
            ->orderBy('last_activity_at', 'desc')
            ->get();

        return Inertia::render('Conversations/Index', [
            'conversations' => $conversations,
        ]);
    }

    /**
     * Show the form for creating a new conversation.
     */
    public function create()
    {
        $models = (new ChatService())->getModels();
        $selectedModel = ChatService::DEFAULT_MODEL;

        return Inertia::render('Conversations/Create', [
            'models' => $models,
            'selectedModel' => $selectedModel,
        ]);
    }

    /**
     * Store a newly created conversation.
     */
    public function store(Request $request)
    {
        $request->validate([
            'message' => 'required|string',
            'model' => 'required|string',
        ]);

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
            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $response,
            ]);

            return redirect()->route('conversations.show', $conversation);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified conversation.
     */
    public function show(Conversation $conversation)
    {
        // Check if the conversation belongs to the authenticated user
        if ($conversation->user_id !== Auth::id()) {
            abort(403);
        }

        $conversation->load('messages');
        $models = (new ChatService())->getModels();

        return Inertia::render('Conversations/Show', [
            'conversation' => $conversation,
            'models' => $models,
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
        ]);

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
            Message::create([
                'conversation_id' => $conversation->id,
                'role' => 'assistant',
                'content' => $response,
            ]);

            return redirect()->back();
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur: ' . $e->getMessage());
        }
    }

    /**
     * Toggle favorite status of the conversation.
     */
    public function toggleFavorite(Conversation $conversation)
    {
        // Check if the conversation belongs to the authenticated user
        if ($conversation->user_id !== Auth::id()) {
            abort(403);
        }

        $conversation->update([
            'is_favorite' => !$conversation->is_favorite,
        ]);

        return redirect()->back();
    }

    /**
     * Remove the specified conversation.
     */
    public function destroy(Conversation $conversation)
    {
        // Check if the conversation belongs to the authenticated user
        if ($conversation->user_id !== Auth::id()) {
            abort(403);
        }

        $conversation->delete();

        return redirect()->route('conversations.index');
    }
}
