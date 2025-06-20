<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CustomInstructionsController extends Controller
{
    /**
     * Display the custom instructions form.
     */
    public function index()
    {
        $user = Auth::user();

        return Inertia::render('CustomInstructions/Index', [
            'customInstructions' => [
                'about_you' => $user->about_you ?? '',
                'assistant_behavior' => $user->assistant_behavior ?? '',
                'custom_commands' => $user->custom_commands ?? [],
            ]
        ]);
    }

    /**
     * Update the user's custom instructions.
     */
    public function update(Request $request)
    {
        $request->validate([
            'about_you' => 'nullable|string|max:2000',
            'assistant_behavior' => 'nullable|string|max:2000',
            'custom_commands' => 'nullable|array',
            'custom_commands.*.name' => 'required|string|max:50',
            'custom_commands.*.command' => 'required|string|max:20|regex:/^\/[a-zA-Z0-9_-]+$/',
            'custom_commands.*.description' => 'required|string|max:200',
        ]);

        $user = Auth::user();

        $user->update([
            'about_you' => $request->about_you,
            'assistant_behavior' => $request->assistant_behavior,
            'custom_commands' => $request->custom_commands,
        ]);

        return redirect()->back()->with('success', 'Instructions personnalisées mises à jour avec succès !');
    }

    /**
     * Get the formatted system prompt for the user.
     */
    public function getSystemPrompt()
    {
        $user = Auth::user();
        $systemPrompt = '';

        if ($user->about_you) {
            $systemPrompt .= "À propos de l'utilisateur:\n" . $user->about_you . "\n\n";
        }

        if ($user->assistant_behavior) {
            $systemPrompt .= "Comportement souhaité de l'assistant:\n" . $user->assistant_behavior . "\n\n";
        }

        if ($user->custom_commands && count($user->custom_commands) > 0) {
            $systemPrompt .= "Commandes personnalisées disponibles:\n";
            foreach ($user->custom_commands as $command) {
                $systemPrompt .= "- {$command['command']}: {$command['description']}\n";
            }
            $systemPrompt .= "\n";
        }

        return $systemPrompt;
    }
}
