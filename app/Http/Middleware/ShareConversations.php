<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ShareConversations
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        // Partager les conversations de l'utilisateur avec toutes les vues
        if (Auth::check()) {
            $conversations = Auth::user()->conversations()
                ->with(['messages' => function ($query) {
                    $query->latest()->limit(1);
                }])
                ->orderBy('last_activity_at', 'desc')
                ->limit(15) // Limite à 15 conversations pour la sidebar
                ->get();

            Inertia::share('conversations', $conversations);
        }

        return $next($request);
    }
}
