# AutoChain Emma+ — Backend Laravel

**Auteur : Moïse**  
**Projet :** Gestion de parc automobile avec traçabilité blockchain

## Description

Backend API REST pour **AutoChain Emma+**, solution de double numérique infalsifiable des véhicules. Ce module couvre la logique métier, la sécurité, la gestion documentaire, le moteur d'alertes et l'intégration avec la couche blockchain.

## Fonctionnalités

| Module | Description |
|--------|-------------|
| **Authentification** | Sanctum (email/mot de passe) + liaison wallet Web3 |
| **Gestion des rôles** | Super Admin, Gestionnaire, Chauffeur, Garagiste, Auditeur |
| **Véhicules** | CRUD, statuts flotte, enregistrement blockchain |
| **Affectations** | Attribution véhicule ↔ chauffeur, prise en charge |
| **Documents** | Stockage sécurisé + hash SHA-256 (intégrité RGPD) |
| **Maintenances** | Certification garagiste + ancrage blockchain |
| **Kilométrage** | Compteur certifié, anti-fraude (km ne peut diminuer) |
| **Carburant** | Calcul consommation L/100 km automatique |
| **Alertes** | Assurance, CT, vidange, entretien (commande planifiée) |
| **Timeline** | Historique combiné blockchain + backend |
| **Blockchain** | Registre des preuves, double signature vente |

## Installation (Laragon)

```bash
cd c:\laragon\www\moukolaravel
copy .env.example .env
# Configurer PostgreSQL dans .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Créer le dossier documents :
```bash
mkdir storage\app\documents
```

## Comptes de démonstration

| Email | Rôle | Mot de passe |
|-------|------|--------------|
| admin@autochain.local | Super Admin | password |
| gestionnaire@autochain.local | Gestionnaire de parc | password |
| chauffeur@autochain.local | Chauffeur | password |
| garagiste@autochain.local | Garagiste | password |
| auditeur@autochain.local | Auditeur | password |

## API — Base URL

```
http://localhost:8000/api/v1
```

### Endpoints principaux

```
GET    /health
POST   /auth/login
POST   /auth/register
GET    /dashboard                    (auth)
GET    /vehicles                     (auth)
POST   /vehicles                     (gestionnaire+)
GET    /vehicles/{id}/timeline       (auth)
POST   /vehicles/{id}/assignments    (gestionnaire+)
POST   /mileage-readings             (chauffeur)
POST   /maintenances                 (garagiste)
POST   /vehicles/{id}/documents      (gestionnaire+)
POST   /fuel-records                 (gestionnaire+)
GET    /alerts                       (auth)
GET    /blockchain/records           (auth)
POST   /blockchain/sales/initiate    (gestionnaire+)
```

Header d'authentification :
```
Authorization: Bearer {token}
```

## Commandes Artisan

```bash
php artisan autochain:generate-alerts   # Génère les alertes
php artisan schedule:work                 # Planificateur (alertes quotidiennes 06h)
```

## Architecture

```
app/
├── Enums/           # Rôles, statuts, types
├── Http/
│   ├── Controllers/Api/
│   ├── Middleware/  # EnsureUserHasRole
│   └── Requests/
├── Models/          # Eloquent (Vehicle, Maintenance, etc.)
└── Services/        # Blockchain, Alertes, Documents, Timeline
```

## Sécurité & RGPD

- Aucune donnée nominative sur la blockchain (UUID + hash uniquement)
- Hash SHA-256 sur chaque document uploadé
- Double signature admin + acheteur pour la vente de véhicule
- Middleware de rôles sur toutes les routes sensibles

## Intégration blockchain

Configurer dans `.env` :
```
BLOCKCHAIN_ENABLED=true
BLOCKCHAIN_RPC_URL=http://127.0.0.1:8545
BLOCKCHAIN_CONTRACT_ADDRESS=0x...
BLOCKCHAIN_STRICT_SIGNATURES=false
```

### Setup rapide (soutenance)

```powershell
.\scripts\setup-blockchain.ps1
php artisan db:seed --force
```

Guide complet : **`SOUTENANCE.md`** (scénario 10 min, comptes MetaMask, dépannage).

```bash
cd blockchain && npm run node          # Terminal 1 — nœud Hardhat
php artisan autochain:sync-blockchain --enable
cd blockchain && npm run accounts      # Clés privées demo MetaMask
```

En mode développement (`BLOCKCHAIN_ENABLED=false`), les transactions sont simulées localement.

---

## Frontend Vue.js 3 (SPA)

```bash
npm install
npm run dev          # Terminal 1
php artisan serve    # Terminal 2
```

Pages : Login, Dashboard, Véhicules, Timeline, Alertes — http://localhost:8000

---

## Smart Contracts (Solidity + Hardhat)

```bash
cd blockchain && npm install && npm test && npm run deploy:local
```

Contrat : `blockchain/contracts/AutoChainRegistry.sol`

---

## Tests PHPUnit

```bash
mysql -u root -e "CREATE DATABASE autochain_test;"
php artisan test
```

16 tests — auth, véhicules, kilométrage, maintenance, rôles.

---

## MetaMask (Web3)

### Connexion par wallet
1. Onglet **MetaMask** sur la page login
2. Signer le message de challenge
3. Comptes seed avec wallet : `admin@autochain.local`, `auditeur@autochain.local`, `chauffeur@autochain.local`

### Lier MetaMask à un compte email
Connecté → bouton **MetaMask** dans la barre latérale → **Lier**

### API Web3
```
POST /api/v1/auth/wallet/challenge   { wallet_address }
POST /api/v1/auth/wallet/login       { wallet_address, message, signature }
POST /api/v1/auth/wallet/link        (auth) — lier wallet
```

---

## Module mobile chauffeur (PWA)

URL : **http://localhost:8000/mobile**

- Accueil mission active
- Déclaration prise en charge / fin de mission
- Relevé kilométrique certifié blockchain
- Navigation bottom bar (mobile-first)
- Installable : **Ajouter à l'écran d'accueil** (Android/iOS)

Le chauffeur est redirigé vers `/mobile` après connexion.

### Vente véhicule — double signature MetaMask

1. **Gestionnaire** : Détail véhicule → onglet **Vente ⛓** → signer avec MetaMask (admin)
2. **Acheteur** : Menu **Ventes** → signer avec le wallet acheteur désigné
3. Si blockchain activée : transactions `initiateSale` + `signSaleAsBuyer` on-chain automatiques

```
POST /api/v1/blockchain/sales/prepare
POST /api/v1/blockchain/sales/initiate    { admin_signature }
POST /api/v1/blockchain/sales/{id}/sign  { buyer_signature }
GET  /api/v1/blockchain/sales/pending
```

```bash
npm install   # inclut ethers.js pour MetaMask
npm run dev
```
