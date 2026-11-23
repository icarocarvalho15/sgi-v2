# Script utilitário para iniciar o PostgreSQL no ambiente local (Scoop)
$pgData = "$env:USERPROFILE\scoop\persist\postgresql\data"
$pgCtl = "$env:USERPROFILE\scoop\apps\postgresql\current\bin\pg_ctl.exe"

if (-not (Test-Path $pgCtl)) {
    Write-Error "Binário pg_ctl não encontrado em $pgCtl"
    exit 1
}

$isReady = & "$env:USERPROFILE\scoop\apps\postgresql\current\bin\pg_isready.exe" 2>$null
if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] PostgreSQL já está em execução e aceitando conexões na porta 5432." -ForegroundColor Green
    exit 0
}

Write-Host "[...] Iniciando o servidor PostgreSQL na porta 5432..." -ForegroundColor Yellow
Start-Process -FilePath $pgCtl -ArgumentList "start -D `"$pgData`" -l `"$pgData\pg.log`"" -WindowStyle Hidden

Start-Sleep -Seconds 3

$isReady = & "$env:USERPROFILE\scoop\apps\postgresql\current\bin\pg_isready.exe" 2>$null
if ($LASTEXITCODE -eq 0) {
    Write-Host "[OK] PostgreSQL iniciado com sucesso na porta 5432!" -ForegroundColor Green
} else {
    Write-Host "[AVISO] Verifique o arquivo de log em $pgData\pg.log se o serviço demorar a responder." -ForegroundColor Yellow
}
