# Runner de todos los arneses de QA HTTP automatizado (items 3, 4, 5 y 7 + reportes).
# Pasa los mismos parametros a cada script y resume el resultado global.
#
# USO (desde cualquier carpeta):
#   pwsh -File database\qa_all.ps1
#   pwsh -File database\qa_all.ps1 -BaseUrl http://localhost:3000 -MysqlExe "C:\xampp\mysql\bin\mysql.exe"
#   pwsh -File database\qa_all.ps1 -SkipSeed        # NO reinstala el fixture (usa el estado actual)
#
# PRERREQUISITOS:
#   - Servidor de desarrollo corriendo (local3000 = php -S localhost:3000 desde la raiz del proyecto).
#     OJO: la app se sirve en la RAIZ del dominio (RewriteBase /); bajo Apache/XAMPP en un
#     subdirectorio (/sysai) el ruteo se rompe. Usar php -S localhost:3000.
#   - MariaDB de XAMPP arriba; BD `sysai` con migraciones 001-025 aplicadas.
#   - Por defecto este runner APLICA database/seed_qa.sql (fixture determinista y versionado),
#     de modo que la suite corre igual en cualquier maquina (portatil). Esto RESETEA los datos
#     de `sysai` (borra el demo si estaba); para volver al escenario visual: seed_demo.sql.
#     Fixture de QA: contador@sysai.test / admin1234 (cargo 2),
#                    coordinador@sysai.test / Test1234* (coordinador del programa 1).
#   Cada arnes ademas crea y limpia sus propios datos de prueba; no deja basura en la BD.
param(
    [string]$BaseUrl   = 'http://localhost:3000',
    [string]$MysqlExe  = 'C:/xampp/mysql/bin/mysql.exe',
    [string]$PassCoord = 'Test1234*',
    [string]$PassConta = 'admin1234',
    [switch]$SkipSeed
)
$ErrorActionPreference = 'Continue'
$dir = $PSScriptRoot

if (-not $SkipSeed) {
    $seed = Join-Path $dir 'seed_qa.sql'
    Write-Host "Aplicando fixture de QA: seed_qa.sql (resetea la BD sysai)..." -ForegroundColor Yellow
    Get-Content -Raw $seed | & $MysqlExe -u root sysai
    if ($LASTEXITCODE -ne 0) { Write-Host "ERROR aplicando seed_qa.sql (codigo $LASTEXITCODE). Abortando." -ForegroundColor Red; exit 2 }
    Write-Host "Fixture de QA instalado." -ForegroundColor DarkGray
}

$scripts = @('qa_poa_indicadores.ps1', 'qa_poa_presupuestal.ps1', 'qa_rendicion.ps1', 'qa_oie.ps1', 'qa_reportes.ps1', 'qa_cierre_anual.ps1', 'qa_usuarios.ps1')
# OJO: NO llamar $fail a este acumulador. Con `pwsh -File` el runner corre en el
# scope GLOBAL, y cada arnés hace `$global:fail = 0` al arrancar: un arnés
# pisaba el conteo del runner y una suite con fallos reportaba "TODOS OK"
# (bug real detectado 2026-07-16).
$arnesesFallidos = 0
foreach ($s in $scripts) {
    Write-Host "`n########## $s ##########" -ForegroundColor Magenta
    & (Join-Path $dir $s) -BaseUrl $BaseUrl -MysqlExe $MysqlExe -PassCoord $PassCoord -PassConta $PassConta
    if ($LASTEXITCODE -ne 0) { $arnesesFallidos++ }
}
Write-Host "`n==================================" -ForegroundColor Magenta
if ($arnesesFallidos -eq 0) { Write-Host "TODOS LOS ARNESES OK" -ForegroundColor Green }
else { Write-Host "$arnesesFallidos arnes(es) con FALLOS" -ForegroundColor Red }
exit $arnesesFallidos
