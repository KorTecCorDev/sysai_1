---
name: auth-recuperacion
description: Detalle del login, la sesión y la recuperación/activación de contraseña de Arca (/login, /chgpsswd, /token_verify, /updtepsswd, Login.php, LoginController, correo del código). Leer antes de tocar autenticación, sesiones, límites de intentos o el correo de activación.
---

# Autenticación — modelo y rediseño (✅ 2026-07-17, verificado y mergeado 2026-08-12)

- **Onboarding = invitación por correo** (decisión 2026-07-17): el Admin da de alta al usuario con una
  contraseña provisional **aleatoria y oculta** (`Usuario::crear()` → `generarCodigoAleatorioSimple`); el
  usuario **nunca** entra con una clave que alguien más conoce: activa la suya en `/chgpsswd` → código al
  correo → `/token_verify` → `/updtepsswd`. **Descartado** el modelo "contraseña temporal conocida +
  bandera `must_change`". Por eso el alta de usuarios no tiene campo de contraseña.
- **La identidad del cambio de contraseña vive en la SESIÓN, no en la URL.** `token_verify` fija
  `$_SESSION['pwd_reset_uid']` + `pwd_reset_ts` con `session_regenerate_id(true)`, y `updatePassword` solo
  confía en eso (prueba de un solo uso, TTL `Login::RECUP_TOKEN_TTL`); sin ella redirige a `/chgpsswd`.
  ⚠️ **No reintroducir `?id=` en `/updtepsswd`**: así era antes y permitía tomar cualquier cuenta (IDOR).
- Política: mínimo 8 caracteres + confirmación. Bloqueo por intentos (`login_intentos`, migr. 011) **por la
  pareja (IP, email)** con 5 fallos, más un tope de 30 por IP (2026-09-14: bloquear por email a secas dejaba
  que cualquiera dejara fuera a otra persona).
- **La sesión se revalida contra la BD en cada petición** (`Login::sesionSigueValida()`, 2026-09-14): usuario
  eliminado o con cambio de cargo, programa o contraseña ⇒ la sesión se cierra (`/login?revocada=1`).
  `/logout` solo por POST con token CSRF.
- Las 4 vistas (`login`, `chgpsswd`, `token_verify`, `updtepsswd`) usan los tokens `--sa-*` del tema y
  comparten el partial de avisos `views/partials/_auth_alertas.php`.
- ✅ **Revisión del flujo para Hostinger (2026-09-14)**: el código se normaliza (`strtolower` + sin espacios:
  el móvil capitaliza y al pegar se cuelan espacios) y **se consume al verificarse** (`Login::consumirToken`);
  límites de recuperación por IP **30/15 min** (`RECUP_MAX_IP`/`RECUP_MAX_VERIFY`; eran 5 y la oficina
  comparte una IP pública); `session.gc_maxlifetime=3600` en `index.php` (el `php_value` del `.htaccess` es
  inerte en LiteSpeed y 1440 s < TTL del código); al cambiar la contraseña se vacía la sesión (con sesión
  iniciada se veía "sesión revocada") y se limpian los `login_intentos` del email; email con `trim` +
  formato; clave ≤ 72 bytes (bcrypt trunca). Fuera: 5 métodos muertos de `Login`. Riesgo aceptado: la
  demora del SMTP síncrono permite distinguir por tiempo si un correo está registrado.
- **QA**: `qa_recuperacion.ps1` (**23**, 2026-09-14) cubre el flujo de punta a punta leyendo el código de
  Mailpit (IDOR, CSRF, respuesta neutra, normalización, un solo uso, validaciones, desbloqueo, sesión
  iniciada, límites por IP); la revalidación de sesión y el bloqueo por IP+email siguen en `qa_usuarios.ps1`.
  Antes de la suite, se verificó a mano el 2026-08-12 con un usuario desechable creado y borrado en la BD
  local — 10 pruebas (IDOR, código inválido/válido, confirmación, longitud, one-shot, login posterior, token
  limpiado, bloqueo por intentos). Repetirlo así si se toca el módulo.
- Configuración del correo (Mailpit en desarrollo, emisor de producción, bitácora, `smtp_test.php`): sección
  *Setup* de `CLAUDE.md`. Administrador inicial: `database/crear_admin.php`.
