# AutoChain Emma+ — Architecture technique

**Auteur : Moïse** | Projet de fin d'année — Gestion de parc automobile + blockchain

---

## 1. Vue d'ensemble (double numérique)

```mermaid
flowchart TB
    subgraph Client
        SPA[Vue.js 3 SPA]
        PWA[PWA Chauffeur /mobile]
        MM[MetaMask]
    end

    subgraph Backend
        API[Laravel 10 API REST]
        SVC[Services métier]
        DB[(MySQL / PostgreSQL)]
    end

    subgraph Blockchain
        HH[Hardhat Node :8545]
        SC[AutoChainRegistry.sol]
        IPFS[IPFS optionnel]
    end

    SPA --> API
    PWA --> API
    MM --> SPA
    SPA -->|tx signées| HH
    HH --> SC
    API --> DB
    API -->|hash + UUID| SC
    API --> IPFS
```

---

## 2. Acteurs et rôles

```mermaid
flowchart LR
    SA[Super Admin]
    FM[Gestionnaire parc]
    DR[Chauffeur]
    ME[Garagiste agréé]
    AU[Auditeur]

    SA -->|users, blockchain config| API
    FM -->|véhicules, ventes, alertes| API
    DR -->|km, missions| API
    ME -->|maintenance| API
    AU -->|lecture seule + registre| API
```

---

## 3. Flux vente double signature

```mermaid
sequenceDiagram
    participant G as Gestionnaire
    participant MM as MetaMask Admin
    participant API as Laravel API
    participant SC as Smart Contract
    participant A as Acheteur MetaMask

    G->>API: POST /blockchain/sales/prepare
    G->>MM: Signer message admin
    G->>API: POST /blockchain/sales/initiate
    G->>SC: initiateSale(on-chain)
    A->>API: GET vente pending
    A->>A: Signer message acheteur
    A->>API: POST /blockchain/sales/sign
    A->>SC: signSaleAsBuyer(on-chain)
    API->>API: Véhicule status = sold
```

---

## 4. Flux certification kilométrage

```mermaid
sequenceDiagram
    participant C as Chauffeur
    participant API as Laravel
    participant MM as MetaMask
    participant SC as Contrat

    C->>API: POST /mileage-readings
    API->>API: Anti-fraude km ≥ précédent
    API->>API: BlockchainRecord pending
    C->>MM: certifyMileage()
    MM->>SC: Transaction
    C->>API: POST /blockchain/records/confirm
```

---

## 5. Stack technique

| Couche | Technologies |
|--------|--------------|
| Frontend | Vue 3, Pinia, Vue Router, Vite, ethers.js 6 |
| Backend | Laravel 10, Sanctum, Eloquent |
| BDD | PostgreSQL (prod) / MySQL (dev Laragon) |
| Blockchain | Solidity 0.8.20, Hardhat, chainId 31337 |
| Storage | Filesystem documents + IPFS (certificats publics) |
| Mobile | PWA (manifest + service worker) |

---

## 6. Sécurité & RGPD

- **On-chain** : UUID véhicule + hash SHA-256 uniquement
- **Off-chain** : données nominatives, documents chiffrés par intégrité hash
- **API** : middleware rôles, Sanctum Bearer tokens
- **RGPD** : `GET /api/v1/rgpd/export`, `DELETE /api/v1/rgpd/account`, politique `/privacy`

---

## 7. Modules backend

```
app/Services/
├── BlockchainService.php    # Preuves, hash, confirmation tx
├── AlertEngineService.php   # Alertes + email
├── DocumentService.php      # Upload, intégrité
├── IpfsService.php          # Pin IPFS
├── SaleSignatureService.php # Double signature vente
├── TimelineService.php      # Historique agrégé
├── ExportService.php        # CSV + rapport flotte
└── Web3AuthService.php      # Auth wallet MetaMask
```

---

## 8. Déploiement local (soutenance)

```bash
# Terminal 1
cd blockchain && npm run node

# Terminal 2
php artisan serve

# Terminal 3
npm run dev

# Activer blockchain
php artisan autochain:sync-blockchain --enable
```

Voir **SOUTENANCE.md** pour le scénario jury complet.
