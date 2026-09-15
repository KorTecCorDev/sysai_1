# QA HTTP automatizado — detalle

> **Referencia.** Extraído de `CLAUDE.md` el 2026-07-09. `CLAUDE.md` conserva el cómo-ejecutar mínimo.

> Arneses de **QA HTTP de extremo a extremo** (PowerShell + `Invoke-WebRequest`, sesiones reales por cookie +
> verificación en BD). Cubren lo verificable por programa (login, CSRF, autorización por rol, cross-tenant,
> flujos de estado, invariantes financieras). Lo **puramente visual** (banners, CSP, menús, árboles read-only)
> se valida aparte en el navegador. **Cada arnés crea y limpia sus propios datos de prueba** (no deja basura).

## Scripts (en `database/`)

| Script | Item | Cubre (nº de checks) |
|---|---|---|
| `qa_poa_indicadores.ps1` | 3 | Flujo POA Indicadores 0→1→2→3, CSRF 403, rol, cross-tenant, bloqueo de jerarquía, observación. **18** |
| `qa_poa_presupuestal.ps1` | 4 + 6 | Flujo POA Presupuestal, presupuesto calculado/congelado, bloqueo de rubros, observación + **item 6** (al aprobar, la rendición pasa a Aprobada(1) y se descuenta del saldo contable) + **tope por sobres** (enviar sobre el tope → 20; bajar el sobre tras enviar → aprobar bloquea) y **puerta de sobres** (programa 5 sin sobres: rubro/POA/rendición → 19; POA Indicadores y jerarquía permitidos; el Contador pasa) + **compuerta de TC** (sin cobertura aprobar bloquea → 22; al reponer tasas el aprobar recongela). **32** |
| `qa_rendicion.ps1` | 5 | Rendición imputada al rubro, límite Σ ≤ monto del rubro, cross-tenant, CSRF + **lectura por URL** (2026-09-14): el coordinador recibe 403 al listar productos/actividades/rubros de otro programa cambiando el id, 200 en los suyos, y `/rendicionff/*` ya no existe + **endurecimiento** (2026-09-14): `/iadmin.php` directo → 404, `GET /logout` ya no cierra sesión, `POST /logout` sin token → 403 y con token → `/login`. **20** |
| `qa_oie.ps1` | 7 | OIE solo Contador (coordinador sin rutas), CSRF, ingreso híbrido (total de fuente = `programa_id` NULL / al sobre), egreso con programa obligatorio + sobre existente + tope por sobre (con exclusión del propio OIE al editar), sin comprobantes huérfanos, eliminar borra OIE+comprobante, saldos en vistas. **22** |
| `qa_reportes.ps1` | reportes (plan de montos F4-5 + item 9) | Las 7 rutas de reporte + `/saldos_contables/saldos` **con TC y sin ningún TC**: 200 sin fatal siempre (antes: `DivisionByZeroError` con las tablas de TC vacías); saldos muestra la conversión al cierre con tasa visible y "sin tipo de cambio registrado" cuando falta. Desde el item 9: la sección 4 lee el xlsx generado (`qa_leer_xlsx.php`) y asserta título RENDICIÓN, encabezado TOTAL RENDIDO, fila TOTAL etiquetada, suma aprobada **en la fila de su rubro**, exclusión de pendientes y fila de TRANSFERENCIA con su monto. Sección 5 (migr. 035): ingresos y egresos celda a celda. **Excel seguro** (2026-09-14): un rubro llamado `=1+1` llega como texto (tipo `s`, vía `qa_leer_xlsx.php --tipo`) y los TOTAL siguen siendo fórmulas `=SUM` (tipo `f`). **51** |
| `qa_cierre_anual.ps1` | 8 | Cierre anual: rol (coordinador sin ruta), CSRF 403, snapshot correcto por fuente (inicial\|comprometido=Σ sobres\|contable), re-cierre upsert sin duplicar con cifras frescas y aviso de reemplazo, advertencia de rendiciones pendientes, y visibilidad del saldo de sobre solo con POA Aprobado (badge si no). **16** |
| `qa_usuarios.ps1` | 10 | CRUD de usuarios (solo Admin — el arnés crea su propio admin temporal, sin tocar al real): rol, CSRF, alta con password provisional bcrypt, email duplicado (validación + UNIQUE migr. 032) y formato, vínculo coordinador↔programa (alta y retiro al cambiar de cargo), anti mass-assignment del password (A2), y eliminación real que limpia `poa_indicadores` sin mentir. **Sesión y login** (2026-09-14): la sesión se revalida contra la BD (cambiar contraseña o cargo la cierra; sin cambios sigue válida) y el bloqueo de login es por IP+email (5 fallos desde otra IP no bloquean al dueño; desde la misma IP sí). **19** |
| `qa_institucional.ps1` | 9 | Programa Institucional (migr. 033-034): sincronización del sobre derivado al crear/editar/eliminar transferencias, guardas de capacidad y de reducción bajo lo comprometido (27), bloqueos como destino de sobres (26) y contra eliminación (28), POA institucional operado por el Contador (crear/enviar/aprobar + rendición que nace Aprobada + saldo del sobre) y bloqueo del POA de programas normales (26). **15** |
| `qa_recuperacion.ps1` | login | Recuperación/activación de contraseña (`/chgpsswd → /token_verify → /updtepsswd`), leyendo el **código real desde la API de Mailpit** (requiere Mailpit arriba). Sin prueba no hay cambio (IDOR, `?id=` ignorado), CSRF 403, formato de email, respuesta neutra ante un correo no registrado, código guardado como sha256 con vencimiento, código en MAYÚSCULAS y con espacios aceptado, código consumido al verificarse (no sirve en otra sesión), validaciones de la clave (confirmación, ≥ 8, ≤ 72 bytes), cambio que limpia los bloqueos de login, prueba de un solo uso, login con la clave nueva y no con la vieja, cambio con la sesión iniciada (aviso de éxito, no "revocada") y límites por IP (6 pasan, 30 frenan). Crea y borra su propio usuario (id 91). **23** |
| `qa_all.ps1` | — | **Runner**: corre los nueve y resume (esperado: `TODOS LOS ARNESES OK`, **216 checks** — 176 hasta el 2026-08-14 + 5 de lectura por URL + 5 de endurecimiento + 2 de Excel seguro + 5 de sesión y login + 23 de recuperación el 2026-09-14). OJO: su acumulador se llama `$arnesesFallidos` — con `pwsh -File` el runner corre en scope global y un `$fail` propio era pisado por el `$global:fail = 0` de cada arnés (bug real corregido el 2026-07-16: una suite con fallos reportaba TODOS OK). |
| `verificar_htaccess.ps1` | despliegue | **Fuera de `qa_all`**: no toca la BD ni inicia sesión, y va contra un servidor que **sí** aplica el `.htaccess` (Apache :8080 en local, o `-BaseUrl https://<dominio>` en Hostinger; con `php -S` fallaría por diseño). 43 rutas sensibles → 403/404 sin rastros de contenido, lo público en 200, ruta inexistente atendida por la app, cabeceras de seguridad (CSP, `nosniff`, `X-Frame-Options`, `Referrer-Policy`, sin `X-Powered-By`, cookie `HttpOnly`) y HTTPS (en local simula un dominio público con `Host:` → 301, y HSTS/cookie `Secure` con `X-Forwarded-Proto`; en producción, HSTS real y `http://` → 301). **58** (2026-09-15, Apache de XAMPP). |

## Cómo ejecutar (desde la raíz del proyecto)

```powershell
# 1) Arrancar el servidor de desarrollo (en otra terminal, desde la raíz):
php -S localhost:3000     # (otros documentos lo llaman "local3000"; ese alias no existe)

# 2) Correr todos los arneses:
pwsh -File database\qa_all.ps1

# Con un setup distinto (ruta de mysql.exe / URL / passwords) se pasan parámetros:
pwsh -File database\qa_all.ps1 -BaseUrl http://localhost:3000 -MysqlExe "C:\xampp\mysql\bin\mysql.exe" -PassCoord "Test1234*" -PassConta "admin1234"

# O un arnés individual:
pwsh -File database\qa_poa_presupuestal.ps1
```

## Prerrequisitos
- Servidor `php -S localhost:3000` corriendo y **MariaDB de XAMPP** arriba. Los arneses corren con el PHP
  del `PATH` (**8.3**, `C:\php`), que es la versión oficial del proyecto.
- BD `sysai` con migraciones **001-034** aplicadas + seed/datos demo (programa **1** con coordinador vinculado,
  jerarquía Resultado→Producto→Actividad y **rubros**; programa **5** para los tests cross-tenant; programa
  **Institucional** con `es_institucional=1` — lo re-siembra el fixture).
- Usuarios de prueba: **coordinador@sysai.test / `Test1234*`** (programa 1) y **contador@sysai.test / `admin1234`**
  (esta última también es la del admin `robertokar97@gmail.com`).
- Parámetros configurables por script (`-BaseUrl`, `-MysqlExe`, `-PassCoord`, `-PassConta`) con defaults para el
  setup XAMPP documentado → portables a otra máquina cambiando solo la ruta de `mysql.exe` si difiere.
- ⚠️ Con la BD poblada por `seed_demo.sql` los usuarios son los `arcoiris.pe`; `qa_oie.ps1` acepta además
  `-EmailCoord`/`-EmailConta` para eso (verificado 22/22 con `contador@arcoiris.pe` / `coordinador.comunidad@arcoiris.pe`).
  Los otros tres arneses asumen datos del seed QA (`sysai.test`, programa 5, rubro 2) y no corren tal cual sobre el demo.

> Detalle de qué valida cada flujo y hallazgos corregidos durante el QA: ver
> `docs/historial-implementacion-items-2-6.md` (cada ítem tiene su bloque de QA).
