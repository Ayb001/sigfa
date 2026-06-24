# SIGFA — Instructions de démarrage

## Comptes de connexion (données de démo)

### Web app (staff) — http://localhost:3000

| Rôle | Email | Mot de passe |
|---|---|---|
| **Super Admin** | admin@sigfa.fr | password |
| **Admin BNA** | admin.bna@bna.dz | password |
| **Agent BNA 1** | sofiane.m@bna.dz | password |
| **Agent BNA 2** | nadia.c@bna.dz | password |
| **Agent BNA 3** | rachid.h@bna.dz | password |

Après login, chaque rôle est redirigé automatiquement vers son interface.

### Mobile app (clients)

| Nom | Email | Mot de passe |
|---|---|---|
| Karim Bensalem | karim.bensalem@gmail.com | password |
| Fatima Zahra | fatima.zahra@gmail.com | password |
| Test Client | test.client@test.fr | password |

Ou créez un nouveau compte depuis l'écran d'inscription.

---

## Démarrage en développement local

### Prérequis
- PHP 8.3+, Composer
- Node 18+, npm
- Docker (pour MySQL)
- Android Studio + émulateur Android (pour la mobile)

### 1. Base de données

```bash
# Démarrer le conteneur MySQL
docker start sigfa_mysql_dev

# Si le conteneur n'existe pas encore :
docker run -d --name sigfa_mysql_dev \
  -e MYSQL_ROOT_PASSWORD=secret \
  -e MYSQL_DATABASE=sigfa \
  -p 3306:3306 mysql:8.0
```

### 2. Backend Laravel

```bash
cd backend

# Installer les dépendances (si pas encore fait)
composer install

# Lancer les migrations + seeders (première fois seulement)
php artisan migrate --seed

# Démarrer l'API
php artisan serve --port=8000
```

L'API est disponible sur http://localhost:8000/api

### 3. Web app React

```bash
cd web
npm install          # première fois seulement
npm run dev          # démarre sur http://localhost:3000
```

### 4. App mobile React Native

```bash
cd mobile
npm install          # première fois seulement

# Démarrer le Metro bundler
npx react-native start

# Dans un autre terminal — lancer sur l'émulateur Android
npx react-native run-android
```

> **Note :** Dans `src/services/api.ts`, l'URL est `http://10.0.2.2:8000/api`.
> `10.0.2.2` pointe vers `localhost` de votre machine depuis l'émulateur Android.
> Sur appareil physique, remplacez par l'IP LAN de votre machine (ex: `http://192.168.1.x:8000/api`).

---

## Production via Docker Compose

```bash
# À la racine du projet (sigfa/)

# Générer les clés (une seule fois)
APP_KEY=$(cd backend && php artisan key:generate --show)
JWT_SECRET=$(cd backend && php artisan jwt:secret --show)

# Lancer tous les services
APP_KEY="$APP_KEY" JWT_SECRET="$JWT_SECRET" docker-compose up --build -d

# Migrations en production
docker exec sigfa_backend php artisan migrate --seed --force
```

Services :
- Web app : http://localhost:3000
- API : http://localhost:8000/api
- MySQL : port 3306

---

## Firebase / FCM (push notifications)

Les notifications push sont entièrement codées mais nécessitent un projet Firebase.

### Étapes pour activer FCM :

1. Allez sur https://console.firebase.google.com
2. Créez un projet (ou utilisez un existant)
3. Ajoutez une application Android :
   - Package name : `com.mobile` (ou cherchez dans `android/app/build.gradle` → `applicationId`)
4. Téléchargez `google-services.json` et placez-le dans `mobile/android/app/`
5. Dans Firebase Console → Paramètres du projet → Cloud Messaging :
   - Copiez la **Clé du serveur (legacy)**
6. Dans `backend/.env` :
   ```
   FCM_SERVER_KEY=votre_clé_serveur_ici
   ```
7. Recompilez l'app Android :
   ```bash
   cd mobile
   npx react-native run-android
   ```

Sans ces étapes, l'app fonctionne normalement — les notifications sont juste ignorées silencieusement.

---

## Planificateur de tâches (IA / prédictions)

Le modèle de régression linéaire tourne à 01h00 chaque nuit automatiquement dans Docker.

En local, pour le lancer manuellement :
```bash
cd backend
php artisan sigfa:predict
```

Pour activer le planificateur en local (optionnel) :
```bash
# Dans un terminal dédié
php artisan schedule:work
```

---

## Structure du projet

```
sigfa/
├── backend/          # Laravel 11 — API REST, SSE, FCM, PDF/Excel
├── web/              # React 18 + Vite — interface staff uniquement
├── mobile/           # React Native 0.74 — app client Android
└── docker-compose.yml
```

---

## Résumé des fonctionnalités par rôle

### Super Admin (web)
- Tableau de bord global (KPIs, graphiques, top entreprises)
- Gestion des entreprises (approuver, suspendre, logo)
- Gestion des utilisateurs (admins + agents)

### Admin Entreprise (web)
- Dashboard KPIs en temps réel (SSE)
- Sessions en cours — monitoring live de tous les agents
- Gestion : agences, employés, files d'attente
- Rapports PDF / Excel (tickets + performance agents)
- Paramètres : profil entreprise + logo
- Prédictions IA (volume demain par file, heure de pointe)

### Agent (web)
- Console de guichet : ouvrir/mettre en pause/fermer session
- Appeler le prochain ticket, marquer servi/passer
- Statistiques de session en temps réel (SSE)
- Historique de ses sessions

### Client (mobile Android)
- Inscription / connexion
- Annuaire des entreprises (recherche)
- Sélection agence → file → prise de ticket
- Suivi du ticket en temps réel (polling 5s)
- Notification push quand son tour arrive (si FCM configuré)
- Jeux pendant l'attente : Sudoku, Échecs
- Actualités (BBC World / Tech / Sport)
- Historique de ses tickets

---

## Points techniques importants

- **Multi-tenant** : toutes les requêtes métier sont filtrées par `tenant_id`
- **SSE** : EventSource avec token JWT en query param `?token=...`
- **JWT** : 2 guards séparés — `staff` (User) et `client` (Client)
- **Pas de CORS** en production (même serveur via nginx proxy)
- **i18n** : FR par défaut, bascule EN disponible sur toutes les interfaces
