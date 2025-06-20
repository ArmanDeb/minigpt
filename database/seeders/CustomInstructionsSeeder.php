<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class CustomInstructionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Trouver le premier utilisateur ou en créer un pour les tests
        $user = User::first();

        if (!$user) {
            $user = User::create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
                'email_verified_at' => now(),
            ]);
        }

        // Ajouter des instructions personnalisées d'exemple
        $user->update([
            'about_you' => "Je suis un développeur web spécialisé en Laravel et Vue.js. Je travaille principalement sur des applications e-commerce et des systèmes de gestion. J'aime apprendre de nouvelles technologies et je préfère les solutions pratiques et efficaces. Je travaille souvent avec des équipes agiles et j'apprécie les bonnes pratiques de développement.",

            'assistant_behavior' => "Utilise un ton professionnel mais accessible. Fournis des exemples de code pratiques quand c'est pertinent. Organise tes réponses avec des listes à puces ou des sections claires. Explique les concepts techniques de manière simple mais précise. N'hésite pas à suggérer des bonnes pratiques et des alternatives quand c'est approprié.",

            'custom_commands' => [
                [
                    'name' => 'Citation inspirante',
                    'command' => '/citation',
                    'description' => 'Fournit une citation inspirante liée au développement ou à la technologie'
                ]
            ]
        ]);

        $this->command->info('Instructions personnalisées ajoutées pour l\'utilisateur: ' . $user->email);
    }
}
