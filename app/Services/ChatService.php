<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use GuzzleHttp\Client;
use OpenAI\Responses\StreamResponse;

class ChatService
{
    private $baseUrl;
    private $apiKey;
    private $client;
    public const DEFAULT_MODEL = 'meta-llama/llama-3.2-11b-vision-instruct:free';

    public function __construct()
    {
        $this->baseUrl = config('services.openrouter.base_url', 'https://openrouter.ai/api/v1');
        $this->apiKey = config('services.openrouter.api_key');
        $this->client = $this->createOpenAIClient();
    }

    /**
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
            return cache()->remember('openai.models', now()->addHour(), function () {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $this->apiKey,
                ])->withoutVerifying()->get($this->baseUrl . '/models');

                return collect($response->json()['data'])
                    ->sortBy('name')
                    ->map(function ($model) {
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
            logger()->error('Error fetching models:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Return a fallback model if API call fails
            return [
                [
                    'id' => self::DEFAULT_MODEL,
                    'name' => 'Default Model',
                    'context_length' => 8192,
                    'max_completion_tokens' => 4096,
                    'pricing' => ['prompt' => 0, 'completion' => 0],
                ]
            ];
        }
    }

    /**
     * @param array{role: 'user'|'assistant'|'system'|'function', content: string} $messages
     * @param string|null $model
     * @param float $temperature
     *
     * @return string
     */
    public function sendMessage(array $messages, string $model = null, float $temperature = 0.7): string
    {
        try {
            logger()->info('Envoi du message', [
                'model' => $model,
                'temperature' => $temperature,
            ]);

            $models = collect($this->getModels());
            if (!$model || !$models->contains('id', $model)) {
                $model = self::DEFAULT_MODEL;
                logger()->info('Modèle par défaut utilisé:', ['model' => $model]);
            }

            $messages = [$this->getChatSystemPrompt(), ...$messages];
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
     * Version streaming qui retourne un StreamResponse pour les SSE.
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

            $models = collect($this->getModels());
            if (!$model || !$models->contains('id', $model)) {
                $model = self::DEFAULT_MODEL;
                logger()->info('Modèle par défaut utilisé:', ['model' => $model]);
            }

            $messages = [$this->getChatSystemPrompt(), ...$messages];

            $stream = $this->client->chat()->createStreamed([
                'model' => $model,
                'messages' => $messages,
                'temperature' => $temperature,
                'stream' => true,
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
     * @return array{role: 'system', content: string}
     */
    private function getChatSystemPrompt(): array
    {
        $user = Auth::check() ? Auth::user() : (object)['name' => 'Guest'];
        $now = now()->locale('fr')->format('l d F Y H:i');

        $systemPrompt = "Tu es un assistant de chat. La date et l'heure actuelle est le {$now}.\n";
        $systemPrompt .= "Tu es actuellement utilisé par {$user->name}.\n\n";

        // Ajouter les instructions personnalisées si l'utilisateur est connecté
        if (Auth::check()) {
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
                $systemPrompt .= "Quand l'utilisateur utilise une de ces commandes, exécute l'action correspondante selon la description.\n\n";
            }
        }

        return [
            'role' => 'system',
            'content' => trim($systemPrompt),
        ];
    }
}
