# POC Chat — Your Car Your Way

Preuve de concept de la fonctionnalité de tchat pour l’application Your Car Your Way.

Le périmètre est volontairement limité : envoyer un message, le recevoir en temps réel, le conserver en base, puis retrouver l’historique après rechargement.

## Stack

- Frontend : React 19 (Vite)
- Backend / API : Laravel 13
- Temps réel : Laravel Reverb (WebSockets)
- Base de données : PostgreSQL 16
- Conteneurisation : Docker Compose

## Architecture technique

Le POC reprend les choix principaux de l’architecture cible, sans reproduire l’application complète.

```text
React
  ↓  HTTP JSON
Laravel API / service Chat
  ↓
PostgreSQL

React
  ↕  WebSocket
Laravel Reverb
```

Le backend Laravel joue le rôle de service de chat dédié : il expose uniquement les endpoints nécessaires, enregistre les messages et les diffuse aux clients connectés.

Modèle minimal, cohérent avec le diagramme d’entité-relation :

- `users` : deux utilisateurs simulés (Utilisateur 1 / Utilisateur 2)
- `conversations` : une conversation de démonstration
- `conversation_user` : participants
- `messages` : contenu, expéditeur (`sender_id`) et date d’envoi (`sent_at`)

Aucune inscription, réservation, paiement ou gestion de véhicule n’est implémentée.

Le schéma de la base est créé par les migrations Laravel (`backend/database/migrations`). Au premier `docker compose up --build`, `php artisan migrate` et le seeder de démo sont exécutés. Un script SQL équivalent, déjà seedé, est aussi fourni pour lecture ou import manuel : [`database/schema.sql`](database/schema.sql).

## Lancer le projet

Prérequis : Docker et Docker Compose.

```bash
docker compose up --build
```

Une fois les services prêts :

- Frontend : [http://localhost:5173](http://localhost:5173)
- API Laravel : [http://localhost:8000](http://localhost:8000)
- WebSockets Reverb : `ws://localhost:8080`

Le premier démarrage exécute les migrations et insère une conversation avec deux messages d’exemple.

Pour remettre les données de démo (2 utilisateurs, 1 conversation, 2 messages) :

```bash
docker compose exec backend php artisan db:seed --force
```

## Tester le chat

1. Ouvrir [http://localhost:5173](http://localhost:5173).
2. Vérifier que l’historique s’affiche (`Bonjour`, puis `Bonjour, comment allez-vous ?`).
3. Choisir **Utilisateur 1** dans le sélecteur.
4. Envoyer un message.
5. Recharger la page : le message doit toujours être visible.

## Tester le temps réel avec deux navigateurs

1. Fenêtre A : ouvrir [http://localhost:5173](http://localhost:5173) et rester **Utilisateur 1**.
2. Fenêtre B : ouvrir la même URL dans un autre navigateur (ou une fenêtre privée) et choisir **Utilisateur 2**.
3. Depuis A, envoyer `Bonjour`.
4. B doit recevoir `Bonjour` immédiatement, sans rechargement.
5. Depuis B, répondre.
6. A doit recevoir la réponse immédiatement.
7. Recharger A ou B : l’historique est conservé.

Le statut `Temps réel : connecté` confirme que le client WebSocket est bien relié à Reverb.

## Tests automatisés

Les tests couvrent la récupération de l’historique, l’enregistrement d’un message et la diffusion de l’événement temps réel.

```bash
docker compose exec backend php artisan test
```

## Ce que le POC démontre

Par rapport à l’architecture cible, ce POC valide :

- une application web React consommant une API Laravel ;
- un service de chat isolé, sans le reste du métier (locations, paiements, profils) ;
- la persistance des messages dans PostgreSQL ;
- la communication temps réel par WebSockets, nécessaire au tchat client / conseiller ;
- un démarrage simple et reproductible via Docker.

Il ne cherche pas à reproduire l’infrastructure de production (API Gateway, multi-instances, Kubernetes, microservices multiples).
