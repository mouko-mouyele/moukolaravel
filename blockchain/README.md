# AutoChain Emma+ — Smart Contracts

**Auteur : Moïse**

Contrat principal : `AutoChainRegistry.sol`

## Fonctions on-chain

- `registerVehicle` — enregistrement (admin)
- `certifyMileage` — compteur certifié anti-fraude
- `certifyMaintenance` — registre de maintenance
- `initiateSale` + `signSaleAsBuyer` — double signature vente

## Installation

```bash
cd blockchain
npm install
npm run compile
npm test
```

## Déploiement local

```bash
# Terminal 1
npm run node

# Terminal 2
npm run deploy:local
```

L'ABI est exportée vers `storage/blockchain/AutoChainRegistry.json` pour Laravel.

## Configuration Laravel

```env
BLOCKCHAIN_ENABLED=true
BLOCKCHAIN_RPC_URL=http://127.0.0.1:8545
BLOCKCHAIN_CONTRACT_ADDRESS=0x...
BLOCKCHAIN_CHAIN_ID=31337
```
