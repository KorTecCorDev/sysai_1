# Runner de todos los arneses de QA HTTP automatizado (items 3, 4, 5).
# Pasa los mismos parametros a cada script y resume el resultado global.
#
# USO (desde cualquier carpeta):
#   pwsh -File database\qa_all.ps1
#   pwsh -File database\qa_all.ps1 -BaseUrl http://localhost:3000 -MysqlExe "C:\xampp\mysql\bin\mysql.exe"
#
# PRERREQUISITOS:
#   - Servidor de desarrollo corriendo (local3000 = php -S localhost:3000 desde la raiz).
#   - MariaDB de XAMPP arriba; BD `sysai` con migraciones 001-017 aplicadas + seed/datos demo.
#   - Usuarios de prueba: coordinador@sysai.test / Test1234* (programa 1, con jerarquia y rubros),
#     contador@sysai.test / admin1234 (= admin robertokar97@gmail.com).
#   Cada arnes crea y limpia sus propios datos de prueba; no deja basura en la BD.
param(
    [string]$BaseUrl   = 'http://localhost:3000',
    [string]$MysqlExe  = 'C:/xampp/mysql/bin/mysql.exe',
    [string]$PassCoord = 'Test1234*',
    [string]$PassConta = 'admin1234'
)
$ErrorActionPreference = 'Continue'
$dir = $PSScriptRoot
$scripts = @('qa_poa_indicadores.ps1', 'qa_poa_presupuestal.ps1', 'qa_rendicion.ps1')
$fail = 0
foreach ($s in $scripts) {
    Write-Host "`n########## $s ##########" -ForegroundColor Magenta
    & (Join-Path $dir $s) -BaseUrl $BaseUrl -MysqlExe $MysqlExe -PassCoord $PassCoord -PassConta $PassConta
    if ($LASTEXITCODE -ne 0) { $fail++ }
}
Write-Host "`n==================================" -ForegroundColor Magenta
if ($fail -eq 0) { Write-Host "TODOS LOS ARNESES OK" -ForegroundColor Green }
else { Write-Host "$fail arnes(es) con FALLOS" -ForegroundColor Red }
exit $fail
