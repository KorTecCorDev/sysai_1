# Respaldo y restauracion de la base de datos `sysai`.
#
# USO (desde cualquier carpeta):
#   pwsh -File database\respaldo.ps1 guardar
#   pwsh -File database\respaldo.ps1 guardar -Etiqueta antes-tanda-2
#   pwsh -File database\respaldo.ps1 listar
#   pwsh -File database\respaldo.ps1 restaurar                 # el mas reciente (pide confirmacion)
#   pwsh -File database\respaldo.ps1 restaurar -Archivo <ruta>
#   pwsh -File database\respaldo.ps1 restaurar -Si             # sin preguntar (para automatizar)
#
# PARA QUE SIRVE
#   La capacitacion se da en varias TANDAS de participantes sobre el mismo equipo. Entre
#   una y otra hay que devolver la base al estado inicial: los participantes crean POAs,
#   rendiciones y aprobaciones que la siguiente tanda no debe encontrar. Restaurar un
#   volcado es la unica forma fiable de hacerlo -borrar a mano deja rastros: correlativos
#   avanzados, vinculos coordinador-programa desactivados, tokens de recuperacion vivos-.
#
#   Tambien sirve como red de seguridad antes de cualquier operacion arriesgada
#   (migraciones nuevas, seeds, pruebas destructivas).
#
# QUE NO HACE
#   No toca el .env, ni los archivos del proyecto, ni la configuracion de Apache. Solo la
#   base de datos. Un volcado NO sustituye al repositorio.
#
# DONDE GUARDA
#   database/respaldos/  -- IGNORADA POR GIT a proposito: tras la capacitacion contendra
#   datos personales reales de los participantes (nombres y correos). Un volcado de la BD
#   no tiene por que acabar en el historial del repositorio.

[CmdletBinding()]
param(
    [Parameter(Position = 0)]
    [ValidateSet('guardar', 'listar', 'restaurar')]
    [string]$Accion = 'listar',

    # Sufijo legible para reconocer el volcado despues ("antes-tanda-2", "pre-migracion-035").
    [string]$Etiqueta = '',

    # Volcado concreto a restaurar. Sin esto se usa el mas reciente.
    [string]$Archivo = '',

    # Restaurar sin pedir confirmacion. Solo para automatizacion.
    [switch]$Si,

    [string]$MysqlBin = 'C:\xampp\mysql\bin'
)

$ErrorActionPreference = 'Stop'

$raiz      = Split-Path -Parent (Split-Path -Parent $MyInvocation.MyCommand.Path)
$carpeta   = Join-Path $raiz 'database\respaldos'
$mysqldump = Join-Path $MysqlBin 'mysqldump.exe'
$mysql     = Join-Path $MysqlBin 'mysql.exe'

foreach ($exe in @($mysqldump, $mysql)) {
    if (-not (Test-Path $exe)) {
        Write-Host "[respaldo] No se encontro $exe" -ForegroundColor Red
        Write-Host "           Indica la carpeta con -MysqlBin <ruta>"
        exit 1
    }
}

# ---------------------------------------------------------------------------
# Credenciales: del .env, nunca escritas aqui
# ---------------------------------------------------------------------------
# Mismo criterio que includes/config/database.php. Si el .env no esta (equipo
# recien clonado), se avisa de como obtenerlo en vez de fallar con un error de
# conexion incomprensible.
function Leer-Env {
    $rutaEnv = if ($env:SYSAI_ENV_FILE -and (Test-Path $env:SYSAI_ENV_FILE)) {
        $env:SYSAI_ENV_FILE
    } else {
        Join-Path $raiz '.env'
    }
    if (-not (Test-Path $rutaEnv)) {
        Write-Host "[respaldo] No se encontro el .env." -ForegroundColor Red
        Write-Host "           Ejecuta:  npm run env:pull"
        exit 1
    }
    $valores = @{}
    foreach ($linea in Get-Content $rutaEnv) {
        $t = $linea.Trim()
        if ($t -eq '' -or $t.StartsWith('#') -or -not $t.Contains('=')) { continue }
        $i = $t.IndexOf('=')
        $valores[$t.Substring(0, $i).Trim()] = $t.Substring($i + 1).Trim()
    }
    return $valores
}

$env_ = Leer-Env
$bd   = if ($env_['DB_NAME']) { $env_['DB_NAME'] } else { 'sysai' }
$usr  = if ($env_['DB_USER']) { $env_['DB_USER'] } else { 'root' }
$pwd_ = $env_['DB_PASS']

# La contrasena se pasa por MYSQL_PWD y no por --password=: los argumentos de un
# proceso son legibles por otros procesos de la maquina. En local suele estar
# vacia, pero el script tiene que ser correcto tambien donde no lo este.
if ($pwd_) { $env:MYSQL_PWD = $pwd_ }

function Argumentos-Conexion { @('-u', $usr, '-h', ($(if ($env_['DB_HOST']) { $env_['DB_HOST'] } else { 'localhost' }))) }

# ---------------------------------------------------------------------------
switch ($Accion) {

    'guardar' {
        if (-not (Test-Path $carpeta)) { New-Item -ItemType Directory -Path $carpeta -Force | Out-Null }

        $sello  = Get-Date -Format 'yyyyMMdd_HHmmss'
        $sufijo = if ($Etiqueta) { '_' + ($Etiqueta -replace '[^\w\-]', '-') } else { '' }
        $salida = Join-Path $carpeta "$bd`_$sello$sufijo.sql"

        Write-Host "[respaldo] Volcando '$bd' ..." -ForegroundColor Cyan

        # --routines --events --triggers: el esquema usa VISTAS y triggers de auditoria;
        # un volcado sin ellos restauraria una base que parece bien y falla al primer
        # reporte. --single-transaction para no bloquear (InnoDB).
        & $mysqldump @(Argumentos-Conexion) `
            --routines --events --triggers --single-transaction `
            --default-character-set=utf8 `
            --result-file="$salida" $bd

        if ($LASTEXITCODE -ne 0) {
            Write-Host "[respaldo] mysqldump fallo (codigo $LASTEXITCODE)." -ForegroundColor Red
            if (Test-Path $salida) { Remove-Item $salida -Force }
            exit 1
        }

        $kb = [math]::Round((Get-Item $salida).Length / 1KB, 1)
        Write-Host "[respaldo] OK  ->  $salida  ($kb KB)" -ForegroundColor Green
    }

    'listar' {
        if (-not (Test-Path $carpeta)) {
            Write-Host "[respaldo] Todavia no hay respaldos. Crea uno con:  pwsh -File database\respaldo.ps1 guardar"
            exit 0
        }
        $items = Get-ChildItem $carpeta -Filter '*.sql' | Sort-Object LastWriteTime -Descending
        if ($items.Count -eq 0) {
            Write-Host "[respaldo] Todavia no hay respaldos."
            exit 0
        }
        Write-Host "[respaldo] Respaldos disponibles (el mas reciente primero):" -ForegroundColor Cyan
        $items | ForEach-Object {
            '   {0}  {1,8:N1} KB  {2}' -f $_.LastWriteTime.ToString('yyyy-MM-dd HH:mm'), ($_.Length / 1KB), $_.Name
        }
    }

    'restaurar' {
        if ($Archivo) {
            if (-not (Test-Path $Archivo)) {
                Write-Host "[respaldo] No existe: $Archivo" -ForegroundColor Red
                exit 1
            }
            $elegido = Get-Item $Archivo
        } else {
            if (-not (Test-Path $carpeta)) {
                Write-Host "[respaldo] No hay respaldos que restaurar." -ForegroundColor Red
                exit 1
            }
            $elegido = Get-ChildItem $carpeta -Filter '*.sql' | Sort-Object LastWriteTime -Descending | Select-Object -First 1
            if (-not $elegido) {
                Write-Host "[respaldo] No hay respaldos que restaurar." -ForegroundColor Red
                exit 1
            }
        }

        Write-Host ''
        Write-Host "  Se va a REEMPLAZAR por completo la base '$bd'." -ForegroundColor Yellow
        Write-Host "  Todo lo que exista ahora en ella se PIERDE." -ForegroundColor Yellow
        Write-Host "  Origen: $($elegido.Name)  ($($elegido.LastWriteTime))"
        Write-Host ''

        if (-not $Si) {
            # Se exige escribir el nombre de la base, no un "s": esta operacion destruye
            # datos y un enter distraido no deberia bastar.
            $r = Read-Host "  Escribe el nombre de la base para confirmar ($bd)"
            if ($r -ne $bd) {
                Write-Host "[respaldo] Cancelado. No se toco nada." -ForegroundColor Cyan
                exit 0
            }
        }

        Write-Host "[respaldo] Recreando '$bd' ..." -ForegroundColor Cyan
        $sql = "DROP DATABASE IF EXISTS ``$bd``; CREATE DATABASE ``$bd`` CHARACTER SET utf8 COLLATE utf8_general_ci;"
        $sql | & $mysql @(Argumentos-Conexion)
        if ($LASTEXITCODE -ne 0) {
            Write-Host "[respaldo] No se pudo recrear la base (codigo $LASTEXITCODE)." -ForegroundColor Red
            exit 1
        }

        Write-Host "[respaldo] Importando ..." -ForegroundColor Cyan
        & $mysql @(Argumentos-Conexion) --default-character-set=utf8 $bd -e "source $($elegido.FullName.Replace('\','/'))"
        if ($LASTEXITCODE -ne 0) {
            Write-Host "[respaldo] La importacion fallo (codigo $LASTEXITCODE). La base quedo INCOMPLETA." -ForegroundColor Red
            exit 1
        }

        $tablas = (& $mysql @(Argumentos-Conexion) -N -B $bd -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$bd'").Trim()
        Write-Host "[respaldo] OK  ->  '$bd' restaurada desde $($elegido.Name)  ($tablas tablas/vistas)" -ForegroundColor Green
    }
}
