# Despliegue greenfield en Hostinger (con SSH)

> Procedimiento verificado contra el código del 2026-09-15. Instalación nueva: proyecto y BD desde cero,
> sin datos que migrar. Cada paso dice **qué** hacer y **cómo comprobar** que salió bien.

## 0. Antes de empezar (bloqueantes)

- [ ] **Credenciales viejas rotadas** (H1 de `docs/auditoria-seguridad-2026-09.md`):
  - [x] App Passwords de `korteccor@gmail.com` y `pruebaskorteccorsmtp@gmail.com` revocadas (2026-09-15);
  - [ ] contraseña de la BD vieja cambiada o la cuenta dada de baja;
  - [ ] usuario y clave de Mailtrap regenerados.
- [x] **Cuenta Gmail dedicada a Arca:** `cronosarca2024@gmail.com`, dos pasos activos (2026-09-15).
- [ ] **App Password del servidor:** generar una **nueva** en esa cuenta el día del despliegue (nombre
      `Arca servidor Hostinger`), guardarla en el gestor y **revocar la de prueba** del 2026-09-15.
- [ ] **SSL activo** en el dominio o subdominio temporal (hPanel → SSL). El `.htaccess` fuerza HTTPS: sin
      certificado el sitio no abre.
- [ ] **PHP 8.3** elegido para el sitio (hPanel → Configuración de PHP) y **SSH habilitado** (hPanel → Acceso SSH).
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

```bash
D=https://<dominio>
for p in /.env /CLAUDE.md /docs/ /iadmin.php /Router.php /database/migrate.php \
         /includes/logs/mail.log /.git/config /.claude/ /composer.json; do
  printf '%s -> ' "$p"; curl -s -o /dev/null -w '%{http_code}\n' "$D$p"
done                                     # todos 403 o 404
curl -sI http://<dominio>/login | head -3 # 301 hacia https://
curl -sI $D/login | grep -i content-security-policy
```

- **`phpinfo()` temporal** (crear, mirar y **borrar**): PHP 8.3, `session.gc_maxlifetime` = 3600,
  `session.save_path` propio de la cuenta y `REMOTE_ADDR` = tu IP real (si aparece la de un CDN, los límites
  por IP se comparten entre todos).
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
