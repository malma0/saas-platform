# ============================================================================
# TT-Service — запуск демо одной командой:
#   1) php artisan serve (порт 8000)
#   2) ssh-туннель serveo.net
#   3) автообновление APP_URL в .env на выданный туннелем адрес
#
# Запуск:  powershell -ExecutionPolicy Bypass -File .\start-demo.ps1
# Остановка: закрыть окна "php" и "ssh" (или Stop-Process -Name php,ssh)
# ============================================================================

$ErrorActionPreference = 'Stop'

$phpDir  = 'C:\laragon\bin\php\php-8.3.30-Win32-vs16-x64'
$project = $PSScriptRoot
$port    = 8000

if (-not (Test-Path "$phpDir\php.exe")) {
    Write-Host "PHP не найден: $phpDir — поправь путь в start-demo.ps1" -ForegroundColor Red
    exit 1
}

# --- 1. Laravel dev-сервер -------------------------------------------------
$busy = Get-NetTCPConnection -LocalPort $port -State Listen -ErrorAction SilentlyContinue
if ($busy) {
    Write-Host "Порт $port уже занят — сервер уже запущен, пропускаю." -ForegroundColor Yellow
} else {
    Start-Process -FilePath "$phpDir\php.exe" `
        -ArgumentList "artisan", "serve", "--host=127.0.0.1", "--port=$port" `
        -WorkingDirectory $project `
        -WindowStyle Minimized
    Write-Host "Запускаю Laravel-сервер на http://127.0.0.1:$port ..."
}

# Ждём, пока сервер ответит
$deadline = (Get-Date).AddSeconds(20)
$serverOk = $false
while ((Get-Date) -lt $deadline) {
    try {
        $null = Invoke-WebRequest "http://127.0.0.1:$port/up" -UseBasicParsing -TimeoutSec 3
        $serverOk = $true
        break
    } catch { Start-Sleep -Milliseconds 700 }
}
if (-not $serverOk) {
    Write-Host "Сервер не ответил на /up за 20 секунд." -ForegroundColor Red
    exit 1
}
Write-Host "Сервер работает." -ForegroundColor Green

# --- 2. Туннель serveo -------------------------------------------------------
$log = Join-Path $env:TEMP 'serveo-demo.log'
Remove-Item $log -ErrorAction SilentlyContinue

Start-Process -FilePath 'ssh' `
    -ArgumentList '-o', 'StrictHostKeyChecking=accept-new', '-o', 'ServerAliveInterval=60', `
                  '-R', "80:localhost:$port", 'serveo.net' `
    -RedirectStandardOutput $log `
    -WindowStyle Minimized

Write-Host 'Открываю туннель serveo.net ...'

# --- 3. Ждём публичный URL ---------------------------------------------------
$url      = $null
$deadline = (Get-Date).AddSeconds(40)
while (-not $url -and (Get-Date) -lt $deadline) {
    Start-Sleep -Seconds 1
    if (Test-Path $log) {
        $m = Select-String -Path $log -Pattern 'https://[^\s\x1b]+' -ErrorAction SilentlyContinue |
             Select-Object -First 1
        if ($m) { $url = $m.Matches[0].Value }
    }
}

if (-not $url) {
    Write-Host "Не дождался URL от serveo. Лог: $log" -ForegroundColor Red
    exit 1
}

# --- 4. Обновляем APP_URL в .env ---------------------------------------------
$envFile = Join-Path $project '.env'
$lines   = (Get-Content $envFile) -replace '^APP_URL=.*', "APP_URL=$url"
[System.IO.File]::WriteAllLines($envFile, $lines)   # UTF-8 без BOM

# --- 5. Итог -------------------------------------------------------------------
Write-Host ''
Write-Host '=============================================================' -ForegroundColor Cyan
Write-Host "  Публичный URL:  $url" -ForegroundColor Green
Write-Host "  Админка:        $url/admin"
Write-Host '  ---------------------------------------------------------'
Write-Host '  owner:      owner@tt-service.local      / owner123'
Write-Host '  admin:      admin@tt-service.local      / admin123'
Write-Host '  superadmin: superadmin@tt-service.local / superadmin123'
Write-Host '=============================================================' -ForegroundColor Cyan
