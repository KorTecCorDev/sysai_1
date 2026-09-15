# Gestión de secretos y hardening

> **Estado:** ✅ P0 y P1 implementados y verificados el **2026-08-14** (commits `2362431`, `b54272f`,
> `a7d357c`). P2 queda abierto hasta que exista dominio propio.
>
> Origen: la pregunta de cómo configurar el SMTP **una sola vez** sin arrastrar una contraseña de
> aplicación entre equipos. Al auditarlo aparecieron tres exposiciones activas, más graves que el
> problema original.

## 1. Contexto que condiciona las decisiones

| Dato | Valor | Consecuencia |
|---|---|---|
| Hosting | Hostinger **compartido** (hPanel) | Sin root ni systemd. El `.htaccess` es la única defensa de servidor. Sí se puede escribir fuera del `public_html`. |
| Accesos | **Un solo desarrollador** | No hacen falta secretos compartidos ni cifrado de equipo; basta un gestor de contraseñas personal. |
| Dominio propio | **Aún no contratado** | No hay `no-reply@<dominio>` todavía; producción sigue sin desplegar (greenfield). |
| Complejidad aceptada | **Mínima robusta** | Nada de Vault/SOPS/Doppler: sin herramientas nuevas ni dependencias de red. |

## 2. Hallazgos de la auditoría (2026-08-14)

Todos verificados en la máquina de desarrollo; los marcados como *estático* no pudieron probarse
contra un Apache real (el de XAMPP redirige a `/dashboard/`).

| # | Hallazgo | Severidad | Estado |
|---|---|---|---|
| 1 | `.env` **descargable desde la red local** vía BrowserSync | Crítico | ✅ corregido (P0) |
| 2 | Scripts de `database/` ejecutables por HTTP, sin guarda CLI | Alto | ✅ corregido (P0) |
| 3 | Token de recuperación **en claro** en `mail.log`, servible por HTTP | Alto | ✅ corregido (P0) |
| 4 | `.htaccess` con sintaxis Apache 2.2 (`Order allow,deny`) | Alto *(estático)* | ✅ corregido (P1) |
| 5 | Autorización anidada dentro de `<IfModule mod_rewrite.c>` | Medio *(estático)* | ✅ corregido (P1) |
| 6 | `<IfModule mod_php.c>` inerte bajo LiteSpeed/PHP-FPM | Medio *(estático)* | ⚠️ anotado, no corregible desde el repo |
| 7 | Modo log **fingía envíos exitosos** en producción | Alto | ✅ corregido (P1) |
| 8 | El `.env` pisaba las variables de entorno reales | Medio | ✅ corregido (P1) |
| 9 | `MAIL_SECURE=` vacío se convertía en `'tls'` | Medio | ✅ corregido |

### Detalle de los tres que importaban

**#1 — el `.env` en la LAN.** BrowserSync escuchaba en `::` (todas las interfaces) y su proxy no filtra
rutas. `curl http://192.168.x.x:3001/.env` devolvía las credenciales de la base de datos: **200, 1955
bytes**. El `.htaccess` no interviene porque `php -S` no lo procesa. Tras el arreglo, la misma petición
devuelve `000` (inalcanzable) y `127.0.0.1` sigue respondiendo `200`.

**#2 — scripts administrativos por HTTP.** La regla que bloquea directorios nombraba `db/`, pero la
carpeta se llama `database/`: nunca la cubrió. Con `register_argc_argv` activo —lo está— `$argv` se
puebla desde la *query string*, de modo que `smtp_test.php` era un **enviador de correo a un
destinatario arbitrario** usando las credenciales del `.env`, y `migrate.php` ejecutaba migraciones sin
autenticación.

**#3 — tokens en la bitácora.** Un token de recuperación es una credencial temporal: quien lo lee toma
la cuenta. Estaba en claro en un archivo descargable.

### Sobre la premisa inicial

«El `.env` guarda mucha información y es inseguro» es media verdad. El `.env` es el estándar de facto y
no tiene nada malo *como formato*; el riesgo está en **la alcanzabilidad**, **la vecindad** (hosting
compartido) y **el valor de lo que contiene**. Y una advertencia: **cifrar el `.env` sin resolver dónde
vive la clave es teatro de seguridad** — si la clave queda en el mismo disco, solo se añadió un paso y un
modo de fallo. Por eso la estrategia elegida ataca los otros dos vectores.

## 3. La arquitectura

**Principio rector: en desarrollo no debe existir ningún secreto que proteger.**

El `.env` de desarrollo contiene hoy `DB_USER=root`, `DB_PASS=` vacío y el correo apuntando a un catcher
local. **No hay un solo secreto**: el problema no se gestiona, se disuelve.

| Entorno | Correo | Secretos | Ubicación del `.env` |
|---|---|---|---|
| **Desarrollo** | Mailpit (`127.0.0.1:1025`, bandeja `:8025`) | **ninguno** | raíz del proyecto |
| **Prueba de entrega real** (excepcional) | App Password **efímera**: generar → probar → **revocar** | vive minutos | sin persistir |
| **Producción** | **Cuenta Gmail dedicada a Arca** con App Password (587/tls) — *decisión 2026-09-14*; `no-reply@<dominio>` en Hostinger cuando haya dominio | 1 App Password de una cuenta que no guarda nada más | `../secrets/.env`, permisos 600 |

> ⚠️ **Enmienda 2026-08-14 — el emisor definitivo es una cuenta Gmail, no `no-reply@<dominio>`.**
> Decisión tomada sabiendo que el dominio no está contratado y que la capacitación es inminente. Lo
> que esto implica, para no redescubrirlo más adelante:
> - La credencial es una **App Password**, que abre la cuenta de Google **completa** (incluido IMAP) y
>   no es recuperable: Google la muestra una sola vez. Hay que generar **una distinta por equipo** y
>   revocar la que se pierda.
> - **Límite ~500 envíos/día.** Irrelevante para 9 participantes; a tener en cuenta si el sistema
>   crece o si alguien automatiza reenvíos.
> - **SPF y DKIM los pone Google**, porque el correo sale por `smtp.gmail.com` autenticado — no por el
>   servidor de Hostinger. Eso es *bueno*: el mensaje va firmado por un emisor con reputación. El
>   riesgo de spam no viene del dominio sino del **remitente con aspecto personal** y de mandar varios
>   correos casi idénticos seguidos.
> - Al desplegar en Hostinger, **verificar que el puerto 587 saliente no esté bloqueado** — en hosting
>   compartido a veces lo está, y entonces no hay correo. Es la comprobación que puede tumbar el
>   despliegue.

> ⚠️ **Enmienda 2026-09-14 — cuenta Gmail DEDICADA, no `korteccor@gmail.com`; buzón propio cuando haya dominio.**
> Revisión del flujo de cambio de contraseña. Sigue sin haber dominio a la vista, así que Gmail se
> mantiene, pero no con una cuenta personal:
> - **Qué se protege:** la App Password abre la cuenta entera. En una cuenta creada solo para Arca, un
>   `.env` filtrado expone un buzón que no guarda nada más, no el correo de una persona.
> - **Continuidad:** la cuenta es de la organización. Si quien la administra se va o cambia su contraseña
>   personal, la activación de cuentas no se cae. Ojo: cambiar la contraseña de **esta** cuenta revoca sus
>   App Passwords, y el correo deja de salir hasta generar otra.
> - **Remitente:** nombre institucional (`MAIL_FROM_NAME=Arca - Arco Iris`), en vez de un alias personal.
> - **Siguiente paso previsto:** `no-reply@<dominio>` en `smtp.hostinger.com:465/ssl` con SPF, DKIM y
>   DMARC. Se cambia solo el `.env`, porque el código ya admite ambos.
> - **Verificación en el servidor:** `php database/crear_admin.php … --probar-correo` envía un código real
>   por el mismo camino que la web.
>
> ✅ **Ejecución 2026-09-15:** cuenta creada, **`cronosarca2024@gmail.com`**, con verificación en dos pasos.
> Se llegó a considerar reutilizar `korteccor@gmail.com` (solo se usaba para Arca), pero el usuario prefirió
> una cuenta nueva por su carácter exclusivo. La App Password se probó con `smtp_test.php` leyendo un archivo
> **fuera del repo** vía `SYSAI_ENV_FILE` (entrega real OK; archivo borrado después). Como esa clave pasó por
> una sesión de chat, el servidor recibirá **otra** generada el día del despliegue.

### Por qué Mailpit y no una App Password permanente

Una contraseña de aplicación de Gmail **abre la cuenta personal entera** (incluido IMAP), no es
recuperable —Google la muestra una vez—, obliga a generar una nueva por cada equipo, tiene límite de
~500 envíos/día y Google la está restringiendo por política. Replicarla en varias máquinas multiplica la
exposición del activo más valioso para resolver un problema de desarrollo.

Mailpit acepta todo, **no reenvía nada a Internet**, no pide credenciales, funciona sin conexión y
muestra el correo renderizado —HTML, cabeceras, acentos— que la bitácora nunca mostró. En un equipo
nuevo: `winget install Axllent.Mailpit` y `npm run dev`.

> Ambos puertos se atan a `127.0.0.1` **a propósito**: por defecto Mailpit escucha en todas las
> interfaces, y su bandeja contiene los tokens de recuperación.

### Por qué el `.env` fuera del document root

Dentro del directorio publicado, que el archivo no se descargue depende de que la configuración del
servidor sea correcta: un módulo ausente, una regla que no casa, un servidor que no procesa `.htaccess`.
**Fuera del docroot, ningún request puede alcanzarlo**: desaparece la clase entera de fallo. El
`.htaccess` pasa a ser la segunda capa, no la única.

```
/home/uXXXX/domains/<dominio>/
├── secrets/.env          ← 600, fuera del alcance de Apache
└── public_html/          ← el proyecto: sin .env, sin database/, sin .git/
```

Orden de búsqueda: `$SYSAI_ENV_FILE` → `../secrets/.env` → `.env` del proyecto.

## 4. Qué se implementó

**P0 — exposición activa** (`b54272f`)
- `listen: 127.0.0.1` en BrowserSync.
- Guarda `PHP_SAPI !== 'cli'` → **404** en los cuatro scripts de `database/` (404 y no un mensaje:
  responder 200 confirmaba que el script existe).
- El token deja de escribirse en la bitácora; `MAIL_LOG_PATH` permite sacarla del docroot;
  `includes/logs/.htaccess` deniega directamente. El log existente, que tenía un token, fue purgado.

**P1 — despliegue** (`a7d357c`)
- `rutaEnv()` con prioridad fuera del docroot.
- `.htaccess`: autorización fuera de `<IfModule>`, `Require all denied` con respaldo 2.2, `FilesMatch`
  para nombres con punto inicial, `db` → `database`.
- `APP_ENV=production` + sin transporte ⇒ `false` y `[ERROR]`, en vez de fingir éxito.
- `cargarEnv()`: el entorno real manda sobre el archivo.

**Correo** (`2362431`)
- `MAIL_TRANSPORT` (`smtp` | `log` | `auto`), SMTP sin autenticar cuando no hay usuario, `MAIL_SECURE`
  vacío = sin cifrado (desactivando el STARTTLS automático de PHPMailer).
- Mailpit integrado en `npm run dev`, con degradación limpia si no está instalado.

## 5. Pendiente (P2 — despliegue)

- [x] ~~Definir el emisor definitivo~~ — ✅ **2026-09-14: cuenta Gmail dedicada a Arca con App Password**
      (587/tls), y `no-reply@<dominio>` cuando haya dominio (sustituye la decisión del 2026-08-14 por
      `korteccor@gmail.com`). `MAIL_TRANSPORT=smtp` explícito en el `.env`; `MAIL_FROM_EMAIL` =
      `MAIL_USERNAME`, que Gmail exige o reescribe el remitente. Ver las enmiendas de la §3.
- [x] ~~Crear la cuenta dedicada (verificación en dos pasos) y guardarla en el gestor de contraseñas.~~
      ✅ 2026-09-15: `cronosarca2024@gmail.com`.
- [ ] **Al desplegar: comprobar que Hostinger permite salida al 587** (`smtp.gmail.com`). Si está
      bloqueado, probar 465/ssl; si tampoco, no hay correo y nadie puede activar su cuenta.
- [ ] Generar una App Password **propia del servidor** en vez de copiar la del equipo de desarrollo,
      y revocarla si el `.env` de producción se retira.
- [ ] ~~`no-reply@<dominio>` + SPF/DKIM~~ — **descartado por ahora** (sin dominio contratado). Si más
      adelante se contrata, es una mejora de imagen y entregabilidad, no un requisito técnico: Gmail
      ya firma con su propio SPF/DKIM.
- [ ] Colocar el `.env` en `secrets/` con permisos 600 y guardar copia en el gestor de contraseñas.
- [ ] Excluir del despliegue: `.git/`, `src/`, `node_modules/`, `docs/`. `database/` se sube **solo de forma
      temporal** para instalar la BD y crear el admin, y se borra después (`docs/despliegue-hostinger.md`).
- [ ] **Verificar el `.htaccess` contra el Apache real** — los hallazgos 4, 5 y 6 son análisis estático.
      ⚠️ La auditoría del 2026-09-14 amplió el `.htaccess` (HTTPS forzado; `*.md`, `docs/` y los PHP de la raíz
      denegados; CSP alineada con `index.php`): verificar también eso. Checklist completa en
      `docs/auditoria-seguridad-2026-09.md`.
      Comprobación mínima tras desplegar: `/.env`, `/database/migrate.php`, `/includes/logs/mail.log` y
      `/.git/config` deben devolver 403/404.
- [x] ~~Revocar la App Password de Gmail anterior si sigue viva~~ — ✅ 2026-09-15: **seguía viva** (en el
      `.env` de la laptop y cifrada en `secrets/.env.enc`). Revocadas todas las de `korteccor@gmail.com` y
      `pruebaskorteccorsmtp@gmail.com`; `.env` de desarrollo pasado a Mailpit y re-cifrado con passphrase nueva.

## 6. Procedimiento de rotación

1. Generar la credencial nueva en el proveedor.
2. Actualizarla en `secrets/.env` (permisos 600) y en el gestor de contraseñas.
3. Verificar: `php database/smtp_test.php <destinatario>`.
4. Revocar la anterior en el proveedor.
5. Comprobar `mail.log`: debe registrar `[OK]`, nunca un token.

Rotar **siempre** que: se filtre o se sospeche filtración, se cambie de equipo, o alguien más haya
tenido acceso temporal al servidor.

---

## 7. Transporte del `.env` entre equipos (2026-08-14)

**Problema real, no hipotético.** El `.env` está en `.gitignore`, así que no viaja. Al trabajar desde
varios equipos contra el mismo repo, uno de ellos quedó con un `.env` antiguo y **siguió funcionando
con la configuración vieja** —otro remitente de correo— sin que nada lo indicara. Un desajuste de
configuración silencioso se diagnostica mirando el síntoma equivocado durante horas.

**Decisión: el `.env` viaja en el repositorio, CIFRADO.** Es lo que hacen git-crypt, SOPS y blackbox.
Enmienda la §1 ("basta un gestor de contraseñas personal"): el copiar-y-pegar manual es justamente
lo que falló.

> ⚠️ **En claro, jamás.** El historial de git es permanente: un secreto commiteado no se retira con
> otro commit, obliga a **rotar la credencial**. Además el repo se clona a cada equipo y GitHub
> escanea secretos. Cifrado, el repo lo transporta sin exponerlo.

### Cómo funciona

| Comando | Qué hace |
|---|---|
| `npm run env:pull` | `secrets/.env.enc` → `.env`  (tras clonar o hacer `git pull`) |
| `npm run env:push` | `.env` → `secrets/.env.enc`  (tras editar; luego commitear) |

- **`secrets/.env.enc` se commitea**; el `.env` en claro sigue ignorado.
- **AES-256-CBC + PBKDF2, 600 000 iteraciones** (recomendación OWASP para PBKDF2-SHA256), salida
  base64 para que git lo trate como texto. Los parámetros están fijados en `scripts/env.js` y deben
  coincidir al cifrar y descifrar.
- **OpenSSL y no age/sops/git-crypt**: viene con Git for Windows, así que está garantizado en
  cualquier equipo donde puedas clonar. Cero instalaciones al dar de alta una máquina.
- **Node y no un `.sh`**: los scripts de npm corren con `cmd.exe` en Windows.
- La passphrase **nunca pasa por argumentos** (son legibles por otros procesos): el prompt lo hace
  OpenSSL. `SYSAI_ENV_PASSPHRASE` permite automatizar sin teclear.
- Cifrar y descifrar escriben a un **temporal** y solo reemplazan si OpenSSL terminó bien: una
  passphrase equivocada no puede destruir el `.env` que ya funcionaba (verificado).

### Lo que no puede viajar

**La passphrase.** Si viajara junto al archivo cifrado, el cifrado no protegería nada. Es lo único
que se lleva aparte —en la cabeza o en el gestor de contraseñas— y basta teclearla una vez por equipo.

⚠️ Una vez que `secrets/.env.enc` está en el historial, **la passphrase es lo único que protege la
App Password de Gmail**. Que sea larga. Si se sospecha filtración: rotar la App Password *y*
re-cifrar con una passphrase nueva — el archivo viejo sigue en el historial para siempre.

### Guarda contra el fallo silencioso

`comprobarEnvDesactualizado()` (en `includes/config/database.php`) compara las fechas: si
`secrets/.env.enc` es más nuevo que tu `.env`, hiciste `git pull` y falta `npm run env:pull`.
Por HTTP **aborta** con un mensaje explícito (HTTP 500); por CLI **avisa por STDERR sin abortar**,
para no romper la suite de QA. Solo en `APP_ENV=development` y solo cuando el `.env` en uso es el
local del proyecto — en producción el archivo vive fuera del docroot y no se gestiona con estos
scripts.

### Protección de la carpeta

`secrets/` está en la lista de directorios bloqueados del `.htaccess` raíz y además lleva su **propio
`.htaccess`** de denegación directa, sin depender de `mod_rewrite`. El patrón `<FilesMatch>` existente
**no** cubría `.env.enc` (exige que el nombre *termine* en `.env`), de ahí la regla propia.
