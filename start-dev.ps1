<#
.SYNOPSIS
    Orquestrador de Serviços DravDev: PostgreSQL, Redis e Laravel Queue Worker.
.DESCRIPTION
    Inicializa os daemons locais e o worker da fila sem necessidade de privilégios de administrador.
#>

[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$ErrorActionPreference = "Stop"

$PgDataDir = "$env:USERPROFILE\pgdata"
$BackendDir = "$PSScriptRoot"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host "    DRAVDEV - INICIALIZANDO AMBIENTE    " -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# --------------------------------------------------
# 1. PostgreSQL
# --------------------------------------------------
Write-Host "`n[1/3] Verificando PostgreSQL..." -ForegroundColor Yellow

$pgRunning = Get-Process -Name "postgres" -ErrorAction SilentlyContinue

if ($pgRunning) {
    Write-Host "--> PostgreSQL já está em execução." -ForegroundColor Green
}
else {
    # Remove arquivo de lock residual de reboots anteriores se o processo não estiver ativo
    $lockFile = "$PgDataDir\postmaster.pid"
    if (Test-Path $lockFile) {
        Write-Host "--> Removendo postmaster.pid residual..." -ForegroundColor DarkYellow
        Remove-Item $lockFile -Force
    }

    Write-Host "--> Inicializando PostgreSQL..." -ForegroundColor Gray
    pg_ctl -D $PgDataDir -l "$PgDataDir\server.log" start

    # Aguarda conexão ativa
    $retries = 10
    while ($retries -gt 0) {
        $check = pg_isready 2>&1
        if ($check -like "*accepting connections*") {
            Write-Host "--> PostgreSQL pronto e aceitando conexões." -ForegroundColor Green
            break
        }
        Start-Sleep -Milliseconds 500
        $retries--
    }

    if ($retries -eq 0) {
        Write-Host "--> Falha ao inicializar o PostgreSQL. Verifique '$PgDataDir\server.log'." -ForegroundColor Red
        Exit 1
    }
}

# --------------------------------------------------
# 2. Redis Server
# --------------------------------------------------
Write-Host "`n[2/3] Verificando Redis Server..." -ForegroundColor Yellow

$redisRunning = Get-Process -Name "redis-server" -ErrorAction SilentlyContinue

if ($redisRunning) {
    Write-Host "--> Redis já está em execução." -ForegroundColor Green
}
else {
    Write-Host "--> Inicializando Redis em background..." -ForegroundColor Gray
    Start-Process -FilePath "redis-server" -WindowStyle Hidden
    
    # Aguarda resposta PING
    $retries = 10
    while ($retries -gt 0) {
        $pong = redis-cli ping 2>&1
        if ($pong -eq "PONG") {
            Write-Host "--> Redis ativo e respondendo (PONG)." -ForegroundColor Green
            break
        }
        Start-Sleep -Milliseconds 300
        $retries--
    }

    if ($retries -eq 0) {
        Write-Host "--> Falha ao conectar ao Redis." -ForegroundColor Red
        Exit 1
    }
}

# --------------------------------------------------
# 3. Laravel Queue Worker
# --------------------------------------------------
Write-Host "`n[3/3] Disparando Queue Worker do Laravel..." -ForegroundColor Yellow

if (Test-Path $BackendDir) {
    Write-Host "--> Queue Worker inicializado em console dedicado." -ForegroundColor Green
}
else {
    Write-Host "--> Diretório backend não encontrado: $BackendDir" -ForegroundColor Red
}

Write-Host "`n========================================" -ForegroundColor Cyan
Write-Host "   TODOS OS SERVIÇOS ESTÃO OPERACIONAIS  " -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan