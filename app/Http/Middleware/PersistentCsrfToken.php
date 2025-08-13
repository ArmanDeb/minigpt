<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;

class PersistentCsrfToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier si l'utilisateur vient de se connecter ou déconnecter
        $userIsAuthenticated = Auth::check();
        $wasAuthenticated = $request->session()->get('_was_authenticated', false);

        // Si l'état d'authentification a changé, régénérer le token
        if ($userIsAuthenticated !== $wasAuthenticated) {
            $request->session()->forget('_persistent_csrf_token');
            $request->session()->put('_was_authenticated', $userIsAuthenticated);
        }

        // Générer un token CSRF persistent s'il n'existe pas déjà
        if (!$request->session()->has('_persistent_csrf_token')) {
            $request->session()->put('_persistent_csrf_token', Str::random(40));
        }

        // Remplacer le token CSRF par notre token persistant
        $request->session()->put('_token', $request->session()->get('_persistent_csrf_token'));

        return $next($request);
    }
}
