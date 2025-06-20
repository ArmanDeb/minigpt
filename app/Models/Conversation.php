<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * MODÈLE ELOQUENT - CONVERSATION
 *
 * Représente une conversation entre un utilisateur et l'IA.
 *
 * Pattern Active Record : Chaque instance de cette classe
 * correspond à une ligne dans la table 'conversations'.
 *
 * Relations :
 * - BelongsTo : User (une conversation appartient à un utilisateur)
 * - HasMany : Messages (une conversation contient plusieurs messages)
 *
 * Fonctionnalités :
 * - Gestion des modèles IA par conversation
 * - Système de favoris pour l'organisation
 * - Titre auto-généré par l'IA
 * - Suivi de la dernière activité pour le tri
 */
class Conversation extends Model
{
    /**
     * Assignation en masse : Attributs modifiables en masse
     *
     * Sécurité : Seuls ces champs peuvent être remplis
     * via create() ou update() avec un tableau.
     */
    protected $fillable = [
        'user_id',
        'title',            // Titre auto-généré par l'IA
        'model',           // Modèle IA utilisé (peut changer en cours)
        'last_activity_at', // Pour tri chronologique des conversations
        'is_favorite',     // Système de favoris utilisateur
    ];

    /**
     * Conversion automatique des types de données
     *
     * Laravel convertit automatiquement :
     * - last_activity_at en objet Carbon pour manipulation dates
     * - is_favorite en boolean pour éviter les erreurs de type
     */
    protected $casts = [
        'last_activity_at' => 'datetime',
        'is_favorite' => 'boolean',
    ];

    /**
     * RELATION ELOQUENT : APPARTENANCE À UN UTILISATEUR
     *
     * Pattern One-to-Many inversé : Plusieurs conversations
     * appartiennent à un utilisateur.
     *
     * Génère automatiquement les requêtes JOIN nécessaires.
     *
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * RELATION ELOQUENT : POSSESSION DE MESSAGES
     *
     * Pattern One-to-Many : Une conversation contient
     * plusieurs messages triés par ordre chronologique.
     *
     * L'ordre ASC garantit l'affichage chronologique correct
     * dans l'interface de chat.
     *
     * @return HasMany
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'asc');
    }

    /**
     * MÉTHODE UTILITAIRE : DERNIER MESSAGE
     *
     * Récupère le message le plus récent de la conversation.
     * Utilisé pour l'aperçu dans la liste des conversations.
     *
     * Pattern Query Builder : Construit une requête optimisée
     * au lieu de charger tous les messages.
     *
     * @return Message|null
     */
    public function lastMessage()
    {
        return $this->messages()->latest()->first();
    }
}
