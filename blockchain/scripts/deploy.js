const hre = require("hardhat");
const fs = require("fs");
const path = require("path");

async function main() {
  const [deployer] = await hre.ethers.getSigners();
  console.log("Deploy AutoChainRegistry with:", deployer.address);

  const Registry = await hre.ethers.getContractFactory("AutoChainRegistry");
  const registry = await Registry.deploy();
  await registry.waitForDeployment();

  const address = await registry.getAddress();
  console.log("AutoChainRegistry deployed to:", address);

  const artifact = await hre.artifacts.readArtifact("AutoChainRegistry");
  const output = {
    project: "AutoChain Emma+",
    author: "Moïse",
    network: hre.network.name,
    chainId: (await hre.ethers.provider.getNetwork()).chainId.toString(),
    contractAddress: address,
    abi: artifact.abi,
    deployedAt: new Date().toISOString(),
  };

  const outDir = path.join(__dirname, "..", "..", "storage", "blockchain");
  fs.mkdirSync(outDir, { recursive: true });
  fs.writeFileSync(path.join(outDir, "AutoChainRegistry.json"), JSON.stringify(output, null, 2));
  console.log("ABI saved to storage/blockchain/AutoChainRegistry.json");
  console.log("\n--- Prochaines étapes ---");
  console.log("1. Terminal Hardhat : npm run node  (laisser ouvert)");
  console.log("2. Redéployer si besoin : npm run deploy:local");
  console.log("3. Laravel : php artisan autochain:sync-blockchain --enable");
  console.log("4. Comptes MetaMask : npm run accounts\n");
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
