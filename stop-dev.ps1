# stop-dev.ps1
$PgDataDir = "$env:USERPROFILE\pgdata"

Write-Host "Finalizando serviços DravDev..." -ForegroundColor Yellow

# Parar Postgres
pg_ctl -D $PgDataDir stop

# Parar Redis
redis-cli shutdown

# Parar workers de fila do PHP
Get-Process -Name "php" -ErrorAction SilentlyContinue | Where-Object { $_.MainWindowTitle -like "*artisan*" } | Stop-Process -Force

Write-Host "Todos os serviços foram finalizados com segurança." -ForegroundColor Green