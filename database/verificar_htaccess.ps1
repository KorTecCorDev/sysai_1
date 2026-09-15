# Verificación HTTP del .htaccess y de las cabeceras de seguridad (2026-09-15).
#
# `php -S` NO procesa el .htaccess, así que los bloqueos solo pueden comprobarse
# contra un servidor web real. Este script se corre desde el equipo local:
#
#   Ensayo local (Apache de XAMPP, vhost :8080):
#     pwsh -File database\verificar_htaccess.ps1
#   Contra producción (Hostinger usa LiteSpeed: el ensayo local no basta):
#     pwsh -File database\verificar_htaccess.ps1 -BaseUrl https://<dominio>
#
# No inicia sesión ni escribe nada: solo hace GET anónimos. Usa curl.exe (incluido
# en Windows 10+) porque permite fijar la cabecera Host, necesaria para simular
# un dominio público contra localhost y probar la redirección a HTTPS.
param(
    [string]$BaseUrl = 'http://localhost:8080'
)
$base = $BaseUrl.TrimEnd('/')
$uri = [Uri]$base
$esLocal = $uri.Host -match '^(localhost|127\.0\.0\.1|\[::1\]|10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[01])\.)'
$global:ok = 0; $global:fail = 0

function Assert($cond, $msg) {
    if ($cond) { Write-Host "  [OK]   $msg" -ForegroundColor Green; $global:ok++ }
    else       { Write-Host "  [FAIL] $msg" -ForegroundColor Red;   $global:fail++ }
}

# GET sin seguir redirecciones. Devuelve código, destino, cabeceras y cuerpo.
$tmpCuerpo = [IO.Path]::GetTempFileName()
function Pedir([string]$ruta, [string[]]$cabeceras = @()) {
    # (no llamar a esta variable $args: es automática en PowerShell)
    $argumentos = @('-s', '-k', '-o', $tmpCuerpo, '-D', '-', '-w', "`n__CODIGO__%{http_code}|%{redirect_url}")
    foreach ($c in $cabeceras) { $argumentos += @('-H', $c) }
    $salida = & curl.exe @argumentos "$base$ruta"
    $ultima = ($salida | Where-Object { $_ -like '__CODIGO__*' } | Select-Object -Last 1) -replace '^__CODIGO__', ''
    $codigo, $destino = $ultima -split '\|', 2
    $heads = @{}
    foreach ($l in $salida) {
        if ($l -match '^([A-Za-z0-9-]+):\s*(.*)$') { $heads[$matches[1].ToLower()] = $matches[2].Trim() }
    }
    return @{ Codigo = [int]$codigo; Destino = $destino; Cabeceras = $heads; Cuerpo = [IO.File]::ReadAllText($tmpCuerpo) }
}

# Rastros de contenido sensible: si aparecen en el cuerpo hubo fuga, sea cual sea el código.
$marcasFuga = 'DB_HOST|DB_PASS|MAIL_PASSWORD|U2FsdGVk|<\?php|repositoryformatversion|\[core\]|CREATE TABLE|INSERT INTO|"require"\s*:|function conectarDB|namespace |require\(.gulp'

Write-Host "`n=== Verificación de $base ===" -ForegroundColor Cyan

Write-Host "`n=== 1) Rutas sensibles: 403/404 y sin contenido ===" -ForegroundColor Cyan
$sensibles = @(
    '/.env', '/.env.example', '/.gitignore', '/.git/config', '/.git/HEAD', '/.claude/',
    '/secrets/', '/secrets/.env.enc',
    '/CLAUDE.md', '/README.md', '/docs/', '/docs/despliegue-hostinger.md',
    '/database/', '/database/migrate.php', '/database/crear_admin.php', '/database/smtp_test.php',
    '/database/schema_baseline.sql', '/database/seed.sql',
    '/includes/', '/includes/logs/mail.log', '/includes/config/database.php', '/includes/funciones.php',
    '/controllers/LoginController.php', '/models/Usuario.php', '/views/', '/views/login.php',
    '/vendor/autoload.php', '/vendor/composer/installed.json',
    '/composer.json', '/composer.lock', '/package.json', '/package-lock.json', '/gulpfile.js',
    '/scripts/env.js', '/src/', '/node_modules/',
    '/iadmin.php', '/iconta.php', '/icoordi.php', '/Router.php', '/hash.php',
    '/build/', '/build/css/', '/.htaccess', '/includes/logs/.htaccess'
)
# Archivos que NO van en el paquete de despliegue (docs/despliegue-hostinger.md §3). En el servidor no
# existen, y LiteSpeed (Hostinger) solo aplica <FilesMatch> a archivos existentes: uno ausente cae en el
# front controller (302 -> /login, 0 bytes). Contra un servidor REMOTO eso se acepta solo si la respuesta
# es idéntica a la de un archivo inventado con la misma extensión (= "no existe", sin contenido).
# En local (repo completo presente) se sigue exigiendo 403/404: ahí un 302 fue un bug real (2026-09-15).
$noSubidos = '^/(\.env|\.env\.example|\.gitignore|\.git/|\.claude/|secrets/|CLAUDE\.md|README\.md|docs/|database/(?!smtp_test\.php)|package(-lock)?\.json|gulpfile\.js|scripts/|src/|node_modules/|hash\.php)'
foreach ($r in $sensibles) {
    $p = Pedir $r
    $fuga = $p.Cuerpo -match $marcasFuga
    $detalle = $(if ($fuga) { "  FUGA: '$($matches[0])'" } elseif ($p.Destino) { " -> $($p.Destino)" } else { '' })
    if (($p.Codigo -in 403, 404) -and -not $fuga) {
        Assert $true ("{0,-40} -> {1}{2}" -f $r, $p.Codigo, $detalle); continue
    }
    if (-not $esLocal -and -not $fuga -and $r -match $noSubidos) {
        $ext = [IO.Path]::GetExtension($r.TrimEnd('/'))
        $testigo = Pedir "/inventado-verificacion-qa$ext"
        if ($testigo.Codigo -eq $p.Codigo -and $testigo.Destino -eq $p.Destino -and $testigo.Cuerpo -eq $p.Cuerpo) {
            Assert $true ("{0,-40} -> {1}  (no está en el servidor: responde igual que un archivo inexistente)" -f $r, $p.Codigo); continue
        }
    }
    Assert $false ("{0,-40} -> {1}{2}" -f $r, $p.Codigo, $detalle)
}

Write-Host "`n=== 2) Lo público sigue sirviéndose ===" -ForegroundColor Cyan
foreach ($r in '/build/css/app.css', '/build/js/bundle.min.js', '/build/img/arca_favicon.png') {
    $p = Pedir $r
    Assert ($p.Codigo -eq 200 -and $p.Cuerpo.Length -gt 0) ("{0,-40} -> {1}" -f $r, $p.Codigo)
}
$login = Pedir '/login'
Assert ($login.Codigo -eq 200) "/login -> $($login.Codigo)"
# Una ruta que no existe la atiende la app (front controller), no el ErrorDocument literal.
$inexistente = Pedir '/ruta-que-no-existe-qa'
Assert ($inexistente.Cuerpo -notmatch '404 - No encontrado') "Ruta inexistente de la app la atiende index.php ($($inexistente.Codigo) -> $($inexistente.Destino))"

Write-Host "`n=== 3) Cabeceras de seguridad en /login ===" -ForegroundColor Cyan
$h = $login.Cabeceras
Assert ($h['content-security-policy'] -match "default-src 'self'" -and $h['content-security-policy'] -match "frame-ancestors 'self'") 'Content-Security-Policy presente'
Assert ($h['x-content-type-options'] -eq 'nosniff') 'X-Content-Type-Options: nosniff'
Assert ($h['x-frame-options'] -match 'SAMEORIGIN') 'X-Frame-Options: SAMEORIGIN'
Assert ($h['referrer-policy'] -eq 'strict-origin-when-cross-origin') 'Referrer-Policy'
Assert (-not $h.ContainsKey('x-powered-by')) "Sin X-Powered-By (versión de PHP oculta)$(if ($h['x-powered-by']) { ": $($h['x-powered-by'])" })"
Assert ($h['set-cookie'] -notmatch 'PHPSESSID' -or $h['set-cookie'] -match 'HttpOnly') 'Cookie de sesión con HttpOnly'

Write-Host "`n=== 4) HTTPS ===" -ForegroundColor Cyan
if ($esLocal) {
    # Un dominio público simulado debe ser llevado a HTTPS; localhost y la LAN quedan exentos.
    $red = Pedir '/login' @('Host: arca.ejemplo.com')
    Assert ($red.Codigo -eq 301 -and $red.Destino -like 'https://arca.ejemplo.com/login') "Dominio público simulado por http -> $($red.Codigo) $($red.Destino)"
    $sim = Pedir '/login' @('X-Forwarded-Proto: https')
    Assert ($sim.Cabeceras['strict-transport-security'] -match 'max-age=\d+') 'HSTS presente cuando la petición llega por HTTPS (simulado)'
    Assert ($sim.Cabeceras['set-cookie'] -notmatch 'PHPSESSID' -or $sim.Cabeceras['set-cookie'] -match 'secure') 'Cookie de sesión con Secure bajo HTTPS (simulado)'
    Assert (-not $login.Cabeceras.ContainsKey('strict-transport-security')) 'Sin HSTS por http:// local (no bloquea desarrollo ni la LAN)'
} else {
    if ($uri.Scheme -eq 'https') {
        Assert ($h['strict-transport-security'] -match 'max-age=\d+') 'HSTS presente'
        Assert ($h['set-cookie'] -notmatch 'PHPSESSID' -or $h['set-cookie'] -match 'secure') 'Cookie de sesión con Secure'
        $httpBase = "http://$($uri.Authority)"
        $w = & curl.exe -s -o NUL -w '%{http_code}|%{redirect_url}' "$httpBase/login"
        $c, $d = $w -split '\|', 2
        Assert ($c -eq '301' -and $d -like 'https://*') "http:// redirige a HTTPS -> $c $d"
    } else {
        Assert $false "La URL de producción debe ser https:// (se recibió $base)"
    }
}

Remove-Item $tmpCuerpo -Force -ErrorAction SilentlyContinue
Write-Host "`n=== RESULTADO: $($global:ok) OK, $($global:fail) FAIL ===" -ForegroundColor $(if ($global:fail -eq 0) { 'Green' } else { 'Red' })
exit $global:fail
