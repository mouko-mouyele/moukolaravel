# AutoChain Emma+ — Guide de soutenance

**Auteur : Moïse** | **Projet :** Gestion de parc automobile + traçabilité blockchain

---

## Préparation (15 min avant)

### 1. Setup automatique

```powershell
cd c:\laragon\www\moukolaravel
.\scripts\setup-blockchain.ps1
php artisan db:seed --force
```

### 2. Lancer les 3 terminaux

| Terminal | Commande | Rôle |
|----------|----------|------|
| 1 | `cd blockchain && npm run node` | Nœud Hardhat (blockchain locale) |
| 2 | `php artisan serve` | API Laravel |
| 3 | `npm run dev` | Frontend Vue.js |

**URL :** http://127.0.0.1:8000/login

### 3. MetaMask

1. Installer l’extension MetaMask (Chrome/Edge)
2. **Importer les comptes Hardhat** :
   ```bash
   cd blockchain && npm run accounts
   ```
3. Ajouter le réseau **Hardhat Local** :
   - RPC : `http://127.0.0.1:8545`
   - Chain ID : `31337`
   - Symbole : ETH

| Compte | Email | Usage démo |
|--------|-------|------------|
| #0 | admin@autochain.local | Super Admin, vente (signature admin) |
| #1 | auditeur@autochain.local | Acheteur (double signature vente) |
| #2 | chauffeur@autochain.local | Relevé km mobile |
| #3 | gestionnaire@autochain.local | Gestion parc, documents |

Mot de passe email pour tous : **`password`**

---

## Scénario de démonstration (10–12 min)

### Partie 1 — Vue d’ensemble (2 min)

1. Connexion **gestionnaire@autochain.local**
2. **Dashboard** : flotte, alertes, véhicules **en panne**
3. Mentionner : 5 rôles, API REST, RGPD (UUID + hash sur chaîne, pas de données nominatives)

### Partie 2 — Traçabilité véhicule (3 min)

1. **Véhicules** → Renault Clio **AB-123-CD**
2. Onglet **Timeline** : historique certifié
3. Onglet **Documents** : upload assurance (hash SHA-256)
4. Onglet **Carburant** : consommation L/100 km
5. Onglet **Affectations** : chauffeur assigné

### Partie 3 — Blockchain on-chain (3 min)

> Prérequis : `BLOCKCHAIN_ENABLED=true` (fait par `autochain:sync-blockchain --enable`)

1. Se connecter en **chauffeur@autochain.local**
2. Ouvrir **/mobile** → relevé kilométrique
3. MetaMask demande signature → **tx visible** dans Hardhat node
4. Retour timeline : hash transaction affiché

**Alternative garagiste :** `garagiste@autochain.local` → maintenance + pièces changées

### Partie 4 — Vente double signature (3 min)

1. **gestionnaire@autochain.local** (MetaMask compte #3)
2. Détail véhicule → onglet **Vente ⛓**
3. Acheteur : `0x70997970C51812dc3A010C7d01b50b0d17ef88C8` (compte #1)
4. Signer avec MetaMask → vente en attente
5. Connexion **auditeur@autochain.local** (compte #1 MetaMask)
6. Menu **Ventes** → signer en tant qu’acheteur
7. Véhicule passe en statut **Vendu** + enregistrement on-chain

### Partie 5 — Audit & admin (2 min)

1. **auditeur@autochain.local** : consultation seule (pas de boutons modifier)
2. **admin@autochain.local** :
   - **Admin → Utilisateurs**
   - **Admin → Blockchain** : config contrat, stats archivage

---

## Points techniques à mentionner au jury

| Sujet | Détail |
|-------|--------|
| **Smart contract** | `AutoChainRegistry.sol` — registerVehicle, certifyMileage, certifyMaintenance, vente |
| **Double numérique** | Backend Laravel + preuve blockchain infalsifiable |
| **Anti-fraude km** | Kilométrage ne peut pas diminuer |
| **RGPD** | UUID véhicule + hash SHA-256, pas de nom sur chaîne |
| **IPFS** | Certificats CT publics (option `IPFS_ENABLED=true`) |
| **PWA** | Module mobile chauffeur installable |
| **Tests** | 30 tests PHPUnit (API, rôles, blockchain, documents) |

---

## Dépannage rapide

| Problème | Solution |
|----------|----------|
| MetaMask « mauvais réseau » | L’app bascule auto sur Hardhat (31337) |
| Tx échoue | Vérifier que `npm run node` tourne |
| Login invalide | `php artisan db:seed --force` |
| Contrat introuvable | `php artisan autochain:sync-blockchain --enable` |
| Port 5173 occupé | Vite utilise 5174 — normal |

---

## Commandes utiles

```bash
# Sync contrat après déploiement
php artisan autochain:sync-blockchain --enable

# Tests complets
php artisan test

# Contrat Solidity
cd blockchain && npm test

# Comptes demo MetaMask
cd blockchain && npm run accounts
```

---

*Bonne soutenance, Moïse !*
