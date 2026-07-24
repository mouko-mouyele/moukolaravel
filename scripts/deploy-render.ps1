# AutoChain Emma+ — Script de deploiement Render + Neon
# Usage: powershell -ExecutionPolicy Bypass -File scripts/deploy-render.ps1

Write-Host "=== AutoChain Emma+ — Deploiement ===" -ForegroundColor Cyan
Write-Host ""

# 1. GitHub
Write-Host "[1/4] GitHub..." -ForegroundColor Yellow
git push origin main 2>$null
Write-Host "  OK: https://github.com/mouko-mouyele/moukolaravel" -ForegroundColor Green
Write-Host ""

# 2. Neon (base de donnees gratuite)
Write-Host "[2/4] Ouvrez Neon pour creer la base (gratuit)..." -ForegroundColor Yellow
Write-Host "  1. Connectez-vous / creez un compte"
Write-Host "  2. New Project -> nom: autochain"
Write-Host "  3. Copiez la Connection string (PostgreSQL)"
Write-Host ""
Start-Process "https://console.neon.tech/app/projects?action=create"

$databaseUrl = Read-Host "Collez ici la Connection string Neon (postgresql://...)"

if (-not $databaseUrl.StartsWith("postgresql")) {
    Write-Host "ERREUR: URL invalide. Doit commencer par postgresql://" -ForegroundColor Red
    exit 1
}

Write-Host ""
Write-Host "[3/4] Ouvrez Render pour creer le Web Service..." -ForegroundColor Yellow
Write-Host "  Suivez les instructions affichees"
Write-Host ""
Start-Process "https://dashboard.render.com/web/new"

Write-Host @"

  SUR RENDER — parametres exacts:
  --------------------------------
  Repository     : mouko-mouyele/moukolaravel
  Runtime        : Docker
  Instance type  : Free
  Health Check   : /api/v1/health

  Environment Variables (copier-coller):
  --------------------------------------
  DATABASE_URL=$databaseUrl
  DB_CONNECTION=pgsql
  APP_ENV=production
  APP_DEBUG=false
  SEED_DEMO=true
  BLOCKCHAIN_ENABLED=false
  LOG_CHANNEL=stderr

  APP_URL = mettre APRES creation (https://votre-service.onrender.com)
  APP_KEY = laisser Render le generer automatiquement

"@ -ForegroundColor White

Write-Host "[4/4] Apres deploiement, testez:" -ForegroundColor Yellow
Write-Host "  https://VOTRE-URL.onrender.com/login"
Write-Host "  Email: gestionnaire@autochain.local  |  Mot de passe: password"
Write-Host ""
Write-Host "Termine!" -ForegroundColor Green
