<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client;
use OpenAI\Responses\StreamResponse;

/**
 * SERVICE PRINCIPAL - GESTION DES INTERACTIONS AVEC L'IA
 *
 * Ce service implémente le pattern Service Layer pour isoler
 * toute la logique métier liée aux appels d'API IA.
 *
 * Responsabilités :
 * - Communication avec l'API OpenRouter
 * - Gestion des modèles IA disponibles (avec cache)
 * - Envoi de messages (mode normal et streaming)
 * - Configuration des prompts système personnalisés
 */
class ChatService
{
    private $baseUrl;
    private $apiKey;
    private $client;

    // Modèle par défaut - Llama gratuit d'OpenRouter
    public const DEFAULT_MODEL = 'meta-llama/llama-3.2-11b-vision-instruct:free';

    /**
     * CONSTRUCTEUR - INITIALISATION DU SERVICE
     *
     * Pattern Dependency Injection : Les configs sont injectées
     * via le container Laravel, permettant une meilleure testabilité
     */
    public function __construct()
    {
        $this->baseUrl = config('services.openrouter.base_url', 'https://openrouter.ai/api/v1');
        $this->apiKey = config('services.openrouter.api_key');
        $this->client = $this->createOpenAIClient();
    }

    /**
     * RÉCUPÉRATION DES MODÈLES IA DISPONIBLES
     *
     * Pattern Cache-Aside : Les modèles sont mis en cache pendant 1h
     * car ils changent rarement et l'appel API est coûteux.
     *
     * @return array<array-key, array{
     *     id: string,
     *     name: string,
     *     context_length: int,
     *     max_completion_tokens: int,
     *     pricing: array{prompt: int, completion: int}
     * }>
     */
    public function getModels(): array
    {
        try {
            // CACHE STRATÉGIQUE - Évite les appels répétés à l'API
            return cache()->remember('openai.models', now()->addHour(), function () {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])->withoutVerifying()->get($this->baseUrl . '/models');

                // TRANSFORMATION ET TRI DES DONNÉES
                // Collection Laravel pour un code plus élégant
                return collect($response->json()['data'])
                    ->sortBy('name')
                    ->map(function ($model) {
                        // Extraction des données essentielles pour l'UI
                        return [
                            'id' => $model['id'],
                            'name' => $model['name'],
                            'context_length' => $model['context_length'],
                            'max_completion_tokens' => $model['top_provider']['max_completion_tokens'],
                            'pricing' => $model['pricing'],
                        ];
                    })
                    ->values()
                    ->all()
                ;
            });
        } catch (\Exception $e) {
            // GESTION D'ERREURS GRACIEUSE
            // En cas d'échec API, on retourne un modèle par défaut
            logger()->error('Erreur lors de la récupération des modèles:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Fallback pour éviter que l'app crash
            return [
                [
                    'id' => self::DEFAULT_MODEL,
                    'name' => 'Modèle par défaut',
                    'context_length' => 8192,
                    'max_completion_tokens' => 4096,
                    'pricing' => ['prompt' => 0, 'completion' => 0],
                ]
            ];
        }
    }

    /**
     * ENVOI DE MESSAGE CLASSIQUE (NON-STREAMING)
     *
     * Utilisé pour :
     * - Génération automatique des titres de conversation
     * - Messages simples où le streaming n'est pas nécessaire
     *
     * @param array{role: 'user'|'assistant'|'system'|'function', content: string} $messages
     * @param string|null $model
     * @param float $temperature Créativité de l'IA (0-1)
     * @return string
     */
    public function sendMessage(array $messages, string $model = null, float $temperature = 0.7): string
    {
        try {
            logger()->info('Envoi du message', [
                'model' => $model,
                'temperature' => $temperature,
            ]);

            // VALIDATION ET FALLBACK DU MODÈLE
            $models = collect($this->getModels());
            if (!$model || !$models->contains('id', $model)) {
                $model = self::DEFAULT_MODEL;
                logger()->info('Modèle par défaut utilisé:', ['model' => $model]);
            }

            // AJOUT AUTOMATIQUE DU PROMPT SYSTÈME
            // Le prompt système est toujours en première position
            $messages = [$this->getChatSystemPrompt(), ...$messages];

            // APPEL API OPENAI/OPENROUTER
            $response = $this->client->chat()->create([
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
            ]);

            logger()->info('Réponse reçue:', ['response' => $response]);

            $content = $response->choices[0]->message->content;

            return $content;
        } catch (\Exception $e) {
            logger()->error('Erreur dans sendMessage:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * ENVOI DE MESSAGE EN STREAMING (TEMPS RÉEL)
     *
     * Cœur du système de chat temps réel.
     * Utilise Server-Sent Events pour envoyer les chunks progressivement.
     *
     * Pattern Observer : Le contrôleur écoute chaque chunk
     * et les transmet immédiatement au client.
     *
     * @param array{role: 'assistant'|'function'|'system'|'user', content: string} $messages
     * @param string|null $model
     * @param float $temperature
     * @return StreamResponse
     */
    public function stream(array $messages, ?string $model = null, float $temperature = 0.7): StreamResponse
    {
        try {
            logger()->info('Envoi du message en streaming', [
                'model' => $model,
                'temperature' => $temperature,
            ]);

            // VALIDATION DU MODÈLE
            $models = collect($this->getModels());
            if (!$model || !$models->contains('id', $model)) {
                $model = self::DEFAULT_MODEL;
                logger()->info('Modèle par défaut utilisé:', ['model' => $model]);
            }

            // INJECTION DU PROMPT SYSTÈME
            $messages = [$this->getChatSystemPrompt(), ...$messages];

            // APPEL API STREAMING
            // stream: true active le mode streaming côté OpenAI
            $stream = $this->client->chat()->createStreamed([
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'stream' => true, // 🔥 Activation du streaming
            ]);

            return $stream;
        } catch (\Exception $e) {
            logger()->error('Erreur dans sendMessageStream:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * FACTORY PATTERN - CRÉATION DU CLIENT OPENAI
     *
     * Centralise la configuration du client HTTP.
     * withoutVerifying() désactive la vérification SSL pour le dev local.
     */
    private function createOpenAIClient(): \OpenAI\Client
    {
        return \OpenAI::factory()
            ->withApiKey($this->apiKey)
            ->withBaseUri($this->baseUrl)
            ->withHttpClient(new \GuzzleHttp\Client(['verify' => false]))
            ->make()
        ;
    }

    /**
     * GÉNÉRATION DU PROMPT SYSTÈME PERSONNALISÉ
     *
     * Strategy Pattern : Le comportement de l'IA change selon
     * les instructions personnalisées de l'utilisateur.
     *
     * Fonctionnalités :
     * - Instructions "À propos de l'utilisateur"
     * - Comportement souhaité de l'assistant
     * - Commandes personnalisées définies par l'utilisateur
     *
     * @return array{role: 'system', content: string}
     */
    private function getChatSystemPrompt(): array
    {
        $user = Auth::check() ? Auth::user() : (object)['name' => 'Invité'];
        $now = now()->locale('fr')->format('l d F Y H:i');

        // PROMPT DE BASE AVEC CONTEXTE TEMPOREL
        $systemPrompt = "Tu es un assistant de chat. La date et l'heure actuelle est le {$now}.\n";
        $systemPrompt .= "Tu es actuellement utilisé par {$user->name}.\n\n";

        // PERSONNALISATION DYNAMIQUE DU COMPORTEMENT
        if (Auth::check()) {
            // Instructions sur l'utilisateur
            if ($user->about_you) {
                $systemPrompt .= "À propos de l'utilisateur:\n" . $user->about_you . "\n\n";
            }

            // Comportement souhaité de l'assistant
            if ($user->assistant_behavior) {
                $systemPrompt .= "Comportement souhaité de l'assistant:\n" . $user->assistant_behavior . "\n\n";
            }

            // Commandes personnalisées avancées
            if ($user->custom_commands && count($user->custom_commands) > 0) {
                $systemPrompt .= "Commandes personnalisées disponibles:\n";
                foreach ($user->custom_commands as $command) {
                    $systemPrompt .= "- {$command['command']}: {$command['description']}\n";
                }
                $systemPrompt .= "Quand l'utilisateur utilise une de ces commandes, exécute l'action correspondante selon la description.\n\n";
            }
        }

        return [
            'role' => 'system',
            'content' => trim($systemPrompt),
        ];
    }
}
