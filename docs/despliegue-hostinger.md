# Despliegue en Hostinger

> Procedimiento verificado contra el código del 2026-09-15. Cada paso dice **qué** hacer y **cómo comprobar**
> que salió bien.
>
> ⚠️ **Lo que se hizo de verdad (2026-09-15):** la BD **no** se instaló vacía. Se importó por **phpMyAdmin** la BD
> de la laptop, con los datos de la capacitación y las contraseñas reemplazadas (§2). Por eso, en este despliegue,
> el §5 queda solo para `composer install`: **no** ejecutar baseline, `migrate.php`, `seed.sql` ni `crear_admin.php`
> sobre esa base. El camino greenfield por SSH se conserva como alternativa.

## 0. Antes de empezar (bloqueantes)

- [x] **Credenciales viejas rotadas** (H1 de `docs/auditoria-seguridad-2026-09.md`, cerrado 2026-09-15):
  - [x] App Passwords de `korteccor@gmail.com` y `pruebaskorteccorsmtp@gmail.com` revocadas (2026-09-15);
  - [x] BD vieja eliminada: el sitio anterior y su BD ya no existen (confirmado 2026-09-15);
  - [x] inbox de Mailtrap borrado (2026-09-15).
- [x] **Cuenta Gmail dedicada a Arca:** `cronosarca2024@gmail.com`, dos pasos activos (2026-09-15).
- [x] **App Password del servidor:** generada el 2026-09-15 (`Arca Hostinger produccion`), guardada en el gestor;
      la de prueba (`Arca servidor Hostinger`) revocada.
- [x] **SSL activo** en el dominio o subdominio temporal (hPanel → SSL). El `.htaccess` fuerza HTTPS: sin
      certificado el sitio no abre.
- [x] **PHP 8.3** elegido para el sitio (hPanel → Configuración de PHP) y **SSH habilitado** (hPanel → Acceso SSH).
- [x] **CDN:** en planes compartidos no se puede apagar (solo "modo desarrollo"); no afecta a la IP que ve Arca (§7).
- [ ] Rama `main` actualizada (merge de `dev`) y suite QA en verde en local.

## 1. Estructura en el servidor

```
/home/uXXXX/domains/<dominio>/
├── secrets/.env          ← permisos 600. La app lo busca aquí primero (rutaEnv()).
├── logs/                 ← bitácora de correo (MAIL_LOG_PATH), fuera del document root
└── public_html/          ← el proyecto
```

## 2. Base de datos (hPanel)

1. hPanel → Bases de datos MySQL: crear la BD y su usuario, y anotar nombre, usuario y contraseña (van al `.env`).
2. **MySQL remoto apagado** (es lo predeterminado: no agregar hosts remotos).
3. El usuario de hPanel no tiene SUPER. Por eso `schema_baseline.sql` ya **no lleva `DEFINER`** (retirado el
   2026-09-15): con él, la importación fallaba con `ERROR 1227`.
4. **Conocer el servidor** (phpMyAdmin de hPanel → SQL, solo lectura):
   `SELECT VERSION(), @@sql_mode, @@lower_case_table_names, @@character_set_server, @@collation_server;`
   El 2026-09-15: MariaDB 11.8.9, `lower_case_table_names=0` (distingue mayúsculas), `utf8mb4_unicode_ci` por defecto.
5. **Alinear la collation de la BD vacía:** phpMyAdmin → la BD → **Operaciones → Cotejamiento = `utf8mb3_general_ci`**.
   Comprobar con `SELECT @@character_set_database, @@collation_database;`. Así ninguna tabla futura hereda
   `utf8mb4_unicode_ci` (daría *Illegal mix of collations* contra las de Arca).

### 2.1 Cargar la BD con datos por phpMyAdmin (camino usado el 2026-09-15)

Sin tocar la BD local: todo sobre una **copia**.

1. Respaldo: `pwsh -File database\respaldo.ps1 guardar -Etiqueta pre-despliegue`.
2. Copia local: `CREATE DATABASE sysai_produccion CHARACTER SET utf8 COLLATE utf8_general_ci;` y
   `mysqldump --single-transaction --routines --events sysai | mysql sysai_produccion` (por `cmd /c`, para no recodificar).
3. Sanear la copia: contraseñas `password_hash(bin2hex(random_bytes(32)))` por usuario (cada cuenta se reactiva por
   `/chgpsswd`), `reset_token`/`reset_token_expira` a NULL, `TRUNCATE` de `login_intentos` y `recuperacion_intentos`.
4. Exportar `sysai_produccion` con phpMyAdmin local (Personalizado, SQL, utf-8): **sin** CREATE DATABASE/USE, **con**
   DROP TABLE/VIEW, desactivar revisión de FKs, **sin** "exportar vistas como tablas", compatibilidad NONE.
5. **Quitar `DEFINER=`root`@`localhost`` del archivo** (las 29 vistas). Revisar que no queden `DEFINER=`,
   `CREATE DATABASE`, `USE`, nombres de BD locales ni identificadores con mayúsculas; que haya 40 tablas `utf8` y 29 vistas.
6. **Ensayo local antes de subir:** importar como un usuario MariaDB **sin SUPER** (`GRANT ALL ON sysai_ensayo.*`),
   con conexión `utf8mb4_unicode_ci` y el `sql_mode` de Hostinger; comparar filas, leer las 29 vistas y levantar la app
   contra esa BD con `SYSAI_ENV_FILE`. El 2026-09-15: 22 OK / 0 FAIL.
7. phpMyAdmin de hPanel → la BD → **Importar** el archivo limpio (utf-8, SQL, NONE).
8. Verificar en hPanel (solo lectura), comparando con la copia local:
   ```sql
   SELECT COUNT(1) FROM schema_migrations;                         -- 35
   SELECT tabla, filas, esperado, IF(filas = esperado, 'OK', 'REVISAR') AS estado FROM (
     SELECT 'usuario' AS tabla, (SELECT COUNT(1) FROM usuario) AS filas, 5 AS esperado
     -- UNION ALL una línea por tabla, con el conteo de la copia local
   ) x ORDER BY estado DESC;
   SELECT table_type, table_collation, COUNT(1) AS objetos       -- BASE TABLE utf8mb3_general_ci 40 / VIEW NULL 29
   FROM information_schema.tables
   WHERE table_schema NOT IN ('information_schema', 'performance_schema', 'mysql', 'sys')
   GROUP BY table_type, table_collation;
   SELECT definer, COUNT(1) AS vistas                             -- el usuario de hPanel, 29
   FROM information_schema.views
   WHERE table_schema NOT IN ('information_schema', 'performance_schema', 'mysql', 'sys')
   GROUP BY definer;
   ```
   ⚠️ En el phpMyAdmin de Hostinger, dos consultas de catálogo devolvieron **0 filas sin error** y las mismas, reescritas,
   dieron el resultado correcto (`DATABASE()` funciona allí: verificado). Causa probable, **no confirmada**: sus comentarios
   contenían la flecha `->`. No usar `->` en comentarios de consultas para phpMyAdmin; si una consulta de catálogo da
   0 filas, repetirla sin comentarios antes de sacar conclusiones.
9. Borrar los `.sql` exportados (tienen datos personales) y las BD/usuario de ensayo locales.

## 3. Preparar y subir el paquete

En local, desde `main`:

```powershell
git checkout main; git pull
npx gulp build              # build/ está versionado; esto solo confirma que está al día
```

Subir a `public_html/` (SCP, SFTP o el administrador de archivos) **solo**:
- `index.php`, `Router.php`, `iadmin.php`, `iconta.php`, `icoordi.php`, `.htaccess`, `composer.json`, `composer.lock`;
- `build/`, `controllers/`, `includes/`, `models/`, `views/`;
- `database/` — **temporal**: se borra en el paso 6.

No subir: `.git/`, `.claude/`, `node_modules/`, `vendor/` (se instala en el servidor), `src/`, `docs/`,
`scripts/`, `secrets/`, `*.md`, `hash.php`, `gulpfile.js`, `package*.json`, `.env*`.

## 4. Configuración (`secrets/.env`)

Por SSH:

```bash
cd ~/domains/<dominio>
mkdir -p secrets logs && touch secrets/.env && chmod 600 secrets/.env
nano secrets/.env
```

Contenido, con `.env.example` como plantilla:
- `APP_ENV=production`
- `DB_HOST=localhost`, `DB_USER`, `DB_PASS`, `DB_NAME` (los de hPanel)
- `MAIL_TRANSPORT=smtp`, `MAIL_HOST=smtp.gmail.com`, `MAIL_PORT=587`, `MAIL_SECURE=tls`
- `MAIL_USERNAME` y `MAIL_FROM_EMAIL` = la cuenta dedicada; `MAIL_PASSWORD` = su App Password
- `MAIL_FROM_NAME=Arca - Arco Iris`
- `MAIL_LOG_PATH=/home/uXXXX/domains/<dominio>/logs/mail.log`
- `SBS_API_URL=` (vacío salvo que haya endpoint)

## 5. Instalar dependencias y la base de datos (SSH)

> ⚠️ **Si la BD se cargó por phpMyAdmin (§2.1), ejecutar SOLO `php -v` y `composer install`.** El baseline fallaría
> sobre tablas existentes, `seed.sql` no aporta nada y `crear_admin.php` crearía un segundo admin: el admin ya viene
> en la BD y se activa por `/chgpsswd`, que además prueba la salida al 587. Usar `php database/smtp_test.php <correo>`
> si el correo no llega.

```bash
cd ~/domains/<dominio>/public_html
php -v                                   # debe decir 8.3; si no, usar /opt/alt/php83/usr/bin/php
composer install --no-dev --optimize-autoloader

mysql -u <DB_USER> -p <DB_NAME> < database/schema_baseline.sql
php database/migrate.php                 # debe terminar con "Listo: 35 migración(es) aplicada(s)."
mysql -u <DB_USER> -p <DB_NAME> < database/seed.sql

php database/crear_admin.php --email <correo real> --nombres "<nombres>" \
    --apellido-paterno "<apellido>" --dni <numero> --probar-correo
```

- **Si `crear_admin.php` falla al enviar**, el problema es la salida al 587 o la App Password. Pruebas:
  - `php database/smtp_test.php <correo>`;
  - si el 587 está bloqueado, `MAIL_PORT=465` y `MAIL_SECURE=ssl`.

  Sin correo nadie puede activar su cuenta: **no seguir** hasta resolverlo.
- **Nunca** ejecutar `seed_demo.sql` ni `seed_qa.sql` en producción: borran todos los usuarios salvo el id 1.

## 6. Retirar lo temporal

```bash
rm -rf ~/domains/<dominio>/public_html/database
chmod 755 ~/domains/<dominio>/public_html/includes/logs
```

## 7. Verificación antes de dar acceso

**Desde tu equipo local** (Windows, en la raíz del proyecto), el comprobador completo:

```powershell
pwsh -File database\verificar_htaccess.ps1 -BaseUrl https://<dominio>
```

Pide 43 rutas sensibles (todas deben dar **403/404 sin contenido**), comprueba que lo público se sirve,
las cabeceras de seguridad (CSP, `nosniff`, `X-Frame-Options`, sin `X-Powered-By`), HSTS, la cookie
`Secure` y que `http://` redirige a `https://`. Debe terminar en **0 FAIL**. ⚠️ Hostinger usa
**LiteSpeed**, no Apache: el ensayo local contra XAMPP no sustituye esta corrida. El 2026-09-15 dio **59/0** en
producción. Allí `<FilesMatch>` solo bloquea archivos **existentes**: los que no se suben (`CLAUDE.md`,
`package.json`, `hash.php`…) responden 302 → `/login` sin contenido, como cualquier ruta inexistente, y el script
los acepta solo si la respuesta es idéntica a la de un archivo inventado.

Alternativa rápida por SSH, si no tienes el equipo local a mano:

```bash
D=https://<dominio>
for p in /.env /CLAUDE.md /docs/ /iadmin.php /Router.php /database/migrate.php \
         /includes/logs/mail.log /.git/config /.claude/ /composer.json /secrets/.env.enc; do
  printf '%s -> ' "$p"; curl -s -o /dev/null -w '%{http_code}\n' "$D$p"
done                                     # todos 403 o 404 (un 302 = el bloqueo pasa por la app: revisar)
curl -sI http://<dominio>/login | head -3 # 301 hacia https://
curl -sI $D/login | grep -iE 'content-security-policy|strict-transport|x-powered-by'   # sin x-powered-by
```

- **`phpinfo()` temporal** (crear, mirar y **borrar**): PHP 8.3, `session.gc_maxlifetime` = 3600,
  `session.save_path` propio de la cuenta y `REMOTE_ADDR` = tu IP real (si aparece la de un CDN, los límites
  por IP se comparten entre todos).
- **IP real sin `phpinfo()`:** Hostinger pone su CDN (`Server: hcdn`) delante y en planes compartidos **no se puede
  apagar** (solo "modo desarrollo"). Comprobado el 2026-09-15 que aun así `REMOTE_ADDR` trae la IP real: pedir un
  código en `/chgpsswd` y comparar `SELECT ip FROM recuperacion_intentos ORDER BY id DESC LIMIT 1;` con
  https://api64.ipify.org (puede ser IPv6).
- **En el navegador:**
  - [ ] activar el admin con el código recibido;
  - [ ] login;
  - [ ] alta de un Contador y un Coordinador (les llega su código);
  - [ ] "Cerrar sesión";
  - [ ] flujo POA → rendición → aprobación;
  - [ ] descargar un reporte Excel.
- Recorrer `docs/qa-frontend-navegador.md` y marcarla.

## 8. Operación

- **Respaldos:** hPanel → Copias de seguridad (diarias) activas **antes** de cargar datos reales. Antes de
  cada migración, descargar un volcado de la BD.
- **Nuevas migraciones:** subir `database/` temporalmente, `php database/migrate.php` y borrarlo de nuevo.
- **Correo:** si alguien cambia la contraseña de la cuenta Gmail dedicada, sus App Passwords se revocan y el
  correo deja de salir (queda `[ERROR]` en `logs/mail.log`). Generar otra y actualizar `secrets/.env`.

## 9. Actualizaciones (despliegue continuo desde GitHub)

**Flujo:** trabajar en `dev` → **merge a `main` = publicar** →
`.github/workflows/paquete-produccion.yml` arma la rama **`produccion`** (lista blanca del §3 + `vendor/` ya
instalado + `database/migrate.php` y sus migraciones) → **webhook** → Hostinger clona esa rama en `public_html`.

Por qué no se despliega `main` directamente: Hostinger clona la rama **tal cual**, y `main` lleva `docs/`,
`CLAUDE.md`, `database/seed_qa.sql` (borra usuarios), `secrets/.env.enc`, `src/`, `.claude/`… quedarían en el
document root protegidos solo por el `.htaccess`. Además `vendor/` no está versionado: sin él la app no arranca,
y cada cambio de `composer.lock` exigiría un `composer install` manual.

### 9.1 Configuración (una sola vez) — ✅ hecha el 2026-09-16

> Estado: rama `produccion` creada por el workflow, *Hostinger GitHub App* autorizada **solo** para `sysai_1`,
> despliegue de `produccion` en `public_html` y despliegue automático encendido. La primera publicación se verificó
> con `verificar_htaccess.ps1` (59/0) y con las rutas propias del paquete (`vendor/`, `database/migrate.php`,
> `.git/`, `.github/`) devolviendo 403/404 sin contenido. Los pasos quedan para reconstruirlo o repetirlo en otro sitio.

1. **Generar la rama:** mergear a `main` el workflow y esperar a que termine (GitHub → Actions). Debe aparecer la
   rama `produccion`. Comprobar que **no** contiene `docs/`, `CLAUDE.md` ni `database/seed*.sql`.
2. **Conectar GitHub (repo privado):** hPanel → Avanzado → **GIT** → **Connect with GitHub**. Instala la *Hostinger
   GitHub App*: en la autorización, **"Only select repositories" → `sysai_1`**, no "All repositories". hPanel no
   lista los repos hasta ese paso (si falta alguno: *Refresh repositories*). La variante por **deploy key** (clave
   SSH de hPanel añadida en *Settings → Deploy keys*, sin permiso de escritura) sigue disponible para repos que no
   estén en GitHub o para conexiones antiguas.
3. **Vaciar `public_html`** (el despliegue exige carpeta vacía). Con el sitio ya publicado, sin perder nada:
   ```bash
   cd ~/domains/<dominio>
   mv public_html public_html_anterior && mkdir public_html
   ```
   `secrets/` y `logs/` no se tocan: viven fuera.
4. **hPanel → GIT → Crear:** repositorio `git@github.com:KorTecCorDev/sysai_1.git`, rama **`produccion`**,
   directorio `public_html`. Desplegar.
5. **Verificar** antes de borrar el respaldo: `pwsh -File database\verificar_htaccess.ps1 -BaseUrl https://<dominio>`
   en 0 FAIL, login, un reporte Excel y "Cerrar sesión". Luego `rm -rf ~/domains/<dominio>/public_html_anterior`.
6. **Despliegue automático:** con la conexión por GitHub App **viene activado y el webhook lo gestiona Hostinger**
   (*"On by default (webhook managed for you)"*), así que no hay que crear nada en GitHub. Comprobar que la opción
   aparece encendida en hPanel → GIT. Solo la variante por SSH/deploy key exige pegar la URL del webhook a mano.

### 9.2 Cada actualización

- **Sin migraciones:** merge `dev` → `main`. En ~1-2 min la rama `produccion` cambia y Hostinger despliega. Si el
  merge solo tocó documentación, el paquete no cambia y **no se despliega nada**.
  ✅ **Ambos caminos verificados el 2026-09-16:** con cambios (merges `754ab62` y `02fbc01`) la rama se actualizó y
  Hostinger publicó solo; sin cambios de app (merge `e6047cc`) Actions terminó en verde con *"El paquete no cambió:
  no se publica nada"*, la rama se quedó en `b3e1ad6`, hPanel **no** registró despliegue y el sitio respondió byte
  por byte igual (`/login` 200, 4189 bytes; comprobador 59/0). Si alguna vez republicara sin cambios de app, el
  sospechoso es `vendor/composer/installed.php` (ver el comentario del workflow).
- **Con migraciones:** desplegar y **acto seguido** aplicarlas por SSH (van en el paquete):
  ```bash
  cd ~/domains/<dominio>/public_html && php database/migrate.php
  ```
  Entre el despliegue y la migración hay una ventana de minutos con el código nuevo sobre el esquema viejo: hacerlo
  fuera del horario de las usuarias y **descargar antes un volcado de la BD** (hPanel → phpMyAdmin → Exportar).
- **Qué NO se despliega:** el `.env` (vive en `../secrets/`), los datos y las migraciones aplicadas.

### 9.3 Si algo sale mal

- **El workflow falla:** GitHub → Actions → el paso rojo dice qué faltó (sintaxis PHP, un archivo prohibido en el
  paquete, Composer). La rama `produccion` **no se toca**: producción sigue con la versión anterior.
- **Volver atrás:** `git revert` del merge en `main` (se regenera el paquete anterior) o, más rápido, hPanel → GIT →
  desplegar de nuevo eligiendo el commit anterior de `produccion`.
- **No se despliega tras un merge:** primero mirar GitHub → Actions (si el workflow falló, la rama `produccion` no
  cambió). Si la rama sí cambió, revisar en hPanel → GIT que el despliegue automático siga encendido y el historial
  de despliegues; con la GitHub App el webhook lo gestiona Hostinger (en GitHub → Settings → GitHub Apps →
  *Hostinger* se ve el acceso concedido). Siempre se puede desplegar a mano con el botón de hPanel.
