# =====================================================================
# Script d'initialisation Laravel pour Docker sous Windows (PowerShell)
# =====================================================================

Write-Host "Demarrage des conteneurs Docker..." -ForegroundColor Cyan
docker compose up -d

Write-Host "Attente du demarrage complet de la base de donnees (10 secondes)..." -ForegroundColor Yellow
Start-Sleep -Seconds 10

Write-Host "Installation des dependances Composer..." -ForegroundColor Cyan
docker compose exec app composer install

Write-Host "Generation de la cle d'application Laravel..." -ForegroundColor Cyan
docker compose exec app php artisan key:generate

Write-Host "Execution des migrations et seeders de base de donnees..." -ForegroundColor Cyan
docker compose exec app php artisan migrate:fresh --seed

Write-Host "Installation et configuration terminees avec succes !" -ForegroundColor Green