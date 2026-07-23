/**
 * Comptes Hardhat par défaut — réseau local UNIQUEMENT (soutenance).
 * Ne jamais utiliser ces clés sur un réseau public.
 */
const ACCOUNTS = [
  { index: 0, role: 'Super Admin (Moïse)', email: 'admin@autochain.local', address: '0xf39Fd6e51aad88F6F4ce6aB8827279cffFb92266', privateKey: '0xac0974bec39a17e36ba4a6f4e4d55bf7744e355e469fb4ef80fb897aedf01212' },
  { index: 1, role: 'Auditeur / Acheteur', email: 'auditeur@autochain.local', address: '0x70997970C51812dc3A010C7d01b50b0d17ef88C8', privateKey: '0x59c6995e998f97a5a0044966f0945389dc9e86dae88c7a8412f4603b6b7862d' },
  { index: 2, role: 'Chauffeur', email: 'chauffeur@autochain.local', address: '0x3C44CdDdB6a900fa2b585dd299e03d12FA4293BC', privateKey: '0x5de4111afa1a4b94908f83103eb1f1706367c2e68ca870fc3fb9a804153dd0ae' },
  { index: 3, role: 'Gestionnaire de parc', email: 'gestionnaire@autochain.local', address: '0x90F79bf6EB2c4f870365E785982E1f101E93b906', privateKey: '0x7c852118294e51e653712a872ad511f381062e0b208846513966783fcfbdf001' },
];

console.log('\n=== AutoChain Emma+ — Comptes MetaMask (Hardhat) ===\n');
ACCOUNTS.forEach((a) => {
  console.log(`#${a.index} ${a.role}`);
  console.log(`  Email   : ${a.email}`);
  console.log(`  Adresse : ${a.address}`);
  console.log(`  Clé     : ${a.privateKey}\n`);
});
console.log('MetaMask → Importer un compte → Coller la clé privée');
console.log('Réseau     : Hardhat Local | RPC http://127.0.0.1:8545 | Chain ID 31337\n');
