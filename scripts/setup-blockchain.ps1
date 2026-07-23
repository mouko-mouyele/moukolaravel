# AutoChain Emma+ — Setup blockchain locale (Hardhat + MetaMask)
# Usage : .\scripts\setup-blockchain.ps1

$ErrorActionPreference = "Stop"
$Root = Split-Path -Parent $PSScriptRoot
$Blockchain = Join-Path $Root "blockchain"
$Php = "C:\laragon\bin\php\php-8.1.10-Win32-vs16-x64\php.exe"

Write-Host "`n=== AutoChain Emma+ — Setup Blockchain ===" -ForegroundColor Cyan

Push-Location $Blockchain

if (-not (Test-Path "node_modules")) {
    Write-Host "Installation des dependances Hardhat..." -ForegroundColor Yellow
    npm install --strict-ssl=false
}

Write-Host "Compilation du contrat AutoChainRegistry..." -ForegroundColor Yellow
npm run compile

Write-Host "Tests Solidity..." -ForegroundColor Yellow
npm test

Write-Host "Deploiement sur reseau Hardhat integre..." -ForegroundColor Yellow
npm run deploy:hardhat

Pop-Location

Write-Host "Synchronisation .env Laravel..." -ForegroundColor Yellow
& $Php artisan autochain:sync-blockchain --enable

Write-Host "`nComptes MetaMask a importer :" -ForegroundColor Green
node (Join-Path $Blockchain "scripts\print-accounts.js")

Write-Host @"

=== Demarrage pour la demo ===

Terminal 1 (blockchain) :
  cd blockchain
  npm run node

Terminal 2 (Laravel) :
  php artisan serve

Terminal 3 (Frontend) :
  npm run dev

URL : http://127.0.0.1:8000/login

Voir SOUTENANCE.md pour le scenario de presentation.

"@ -ForegroundColor Cyan
