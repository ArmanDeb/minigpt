<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MODÈLE ELOQUENT - MESSAGE DE CHAT
 *
 * Représente un message individuel dans une conversation.
 *
 * Structure de Chat IA :
 * - 'system' : Instructions/contexte pour l'IA (prompts)
 * - 'user' : Messages envoyés par l'utilisateur
 * - 'assistant' : Réponses générées par l'IA
 *
 * Pattern Active Record : Chaque message est persisté
 * individuellement pour maintenir l'historique complet.
 *
 * Fonctionnalités :
 * - Stockage chronologique des échanges
 * - Comptage des tokens (pour facturation future)
 * - Relation forte avec les conversations
 */
class Message extends Model
{
    /**
     * Assignation en masse : Champs modifiables
     *
     * Sécurité Laravel : Empêche l'assignation de masse
     * sur des champs non déclarés.
     */
    protected $fillable = [
        'conversation_id', // Clé étrangère vers la conversation parente
        'role',           // Type de message : 'system', 'user', 'assistant'
        'content',        // Contenu textuel du message
        'tokens',         // Nombre de tokens OpenAI (pour coût/monitoring)
    ];

    /**
     * RELATION ELOQUENT : APPARTENANCE À UNE CONVERSATION
     *
     * Pattern Many-to-One : Plusieurs messages appartiennent
     * à une conversation unique.
     *
     * Utilisé pour :
     * - Récupérer le contexte d'une conversation
     * - Suppression en cascade (si conversation supprimée)
     * - Requêtes optimisées avec JOIN
     *
     * @return BelongsTo
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
