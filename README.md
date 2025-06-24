# MiniGPT

Une interface de chat élégante et moderne pour interagir avec l'IA, construite avec Laravel et Vue.js.

## 📋 À propos du projet

MiniGPT est une application web qui permet aux utilisateurs de créer des conversations avec l'IA dans une interface intuitive et professionnelle. L'application offre une expérience utilisateur fluide avec gestion des conversations, instructions personnalisées, et authentification sécurisée.

## ✨ Fonctionnalités principales

- 💬 **Interface de chat moderne** : Conversation fluide avec l'IA
- 📝 **Gestion des conversations** : Sauvegarde et organisation de vos discussions
- ⭐ **Conversations favorites** : Marquez vos conversations importantes
- 🎯 **Instructions personnalisées** : Configurez le comportement de l'IA selon vos besoins
- 🔐 **Authentification sécurisée** : Système d'authentification complet avec 2FA
- 🌙 **Mode sombre** : Interface adaptée à vos préférences
- 📱 **Design responsive** : Utilisation optimale sur tous les appareils
- 🤖 **Sélection de modèles** : Choisissez parmi différents modèles d'IA

## 🛠️ Technologies utilisées

- **Backend** : Laravel 11, PHP 8.2+
- **Frontend** : Vue.js 3, Inertia.js
- **Styling** : Tailwind CSS
- **Authentification** : Laravel Jetstream
- **Base de données** : MySQL/PostgreSQL
- **IA** : Intégration OpenAI API

## 📋 Prérequis

Assurez-vous d'avoir installé les éléments suivants :

- PHP 8.2 ou supérieur
- Composer
- Node.js 18+ et npm
- MySQL 8.0+ ou PostgreSQL 13+
- Extension PHP : BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML

## 🚀 Installation

### 1. Cloner le projet

```bash
git clone https://github.com/ArmanDeb/minigpt.git
cd minigpt
```

### 2. Installer les dépendances PHP

```bash
composer install
```

### 3. Installer les dépendances Node.js

```bash
npm install
```

### 4. Configuration de l'environnement

```bash
cp .env.example .env
php artisan key:generate
```

### 5. Configurer la base de données

Éditez le fichier `.env` et configurez vos paramètres de base de données :

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=minigpt
DB_USERNAME=votre_utilisateur
DB_PASSWORD=votre_mot_de_passe
```

### 6. Configurer l'API OpenAI

Ajoutez votre clé API OpenAI dans le fichier `.env` :

```env
OPENAI_API_KEY=votre_cle_api_openai
OPENAI_ORGANIZATION=votre_organisation_openai (optionnel)
```

### 7. Migrer la base de données

```bash
php artisan migrate
```

### 8. (Optionnel) Peupler la base de données

```bash
php artisan db:seed
```

## 🏃‍♂️ Lancement de l'application

### Mode développement

1. **Démarrer le serveur Laravel** :
```bash
php artisan serve
```

2. **Compiler les assets (dans un terminal séparé)** :
```bash
npm run dev
```

L'application sera accessible à l'adresse : `http://localhost:8000`

### Mode production

1. **Compiler les assets pour la production** :
```bash
npm run build
```

2. **Optimiser l'application** :
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## ⚙️ Configuration avancée

### Nettoyage automatique des sessions

Pour activer le nettoyage automatique des sessions expirées, ajoutez cette tâche cron :

```bash
* * * * * cd /chemin/vers/votre/projet && php artisan schedule:run >> /dev/null 2>&1
```

### Configuration du cache

Pour améliorer les performances, configurez Redis comme driver de cache :

```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

## 📖 Utilisation

1. **Créer un compte** : Inscrivez-vous via l'interface d'authentification
2. **Configurer vos instructions** : Accédez aux paramètres pour personnaliser le comportement de l'IA
3. **Démarrer une conversation** : Cliquez sur "Nouvelle conversation" et commencez à chater
4. **Gérer vos conversations** : Consultez, recherchez et organisez vos discussions depuis le panneau latéral

## 🧪 Tests

Exécuter les tests :

```bash
php artisan test
```

Ou avec PHPUnit :

```bash
./vendor/bin/phpunit
```

## 📝 Commandes utiles

```bash
# Nettoyer les sessions expirées
php artisan sessions:clean

# Vider le cache
php artisan cache:clear

# Reconstruire les assets
npm run build

# Vérifier le statut de l'application
php artisan about
```

## 🤝 Contribution

Les contributions sont les bienvenues ! Pour contribuer :

1. Forkez le projet
2. Créez une branche pour votre fonctionnalité (`git checkout -b feature/AmazingFeature`)
3. Committez vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Poussez vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrez une Pull Request

## 📄 License

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 📞 Support

Pour toute question ou problème, n'hésitez pas à :

- Ouvrir une issue sur GitHub
- Contacter l'équipe de développement

---

**MiniGPT** - Votre assistant IA personnel, simple et puissant.
