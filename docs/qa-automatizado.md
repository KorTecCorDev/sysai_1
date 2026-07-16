# QA HTTP automatizado — detalle

> **Referencia.** Extraído de `CLAUDE.md` el 2026-07-09. `CLAUDE.md` conserva el cómo-ejecutar mínimo.

> Arneses de **QA HTTP de extremo a extremo** (PowerShell + `Invoke-WebRequest`, sesiones reales por cookie +
> verificación en BD). Cubren lo verificable por programa (login, CSRF, autorización por rol, cross-tenant,
> flujos de estado, invariantes financieras). Lo **puramente visual** (banners, CSP, menús, árboles read-only)
> se valida aparte en el navegador. **Cada arnés crea y limpia sus propios datos de prueba** (no deja basura).

## Scripts (en `database/`)

| Script | Item | Cubre (nº de checks) |
|---|---|---|
| `qa_poa_indicadores.ps1` | 3 | Flujo POA Indicadores 0→1→2→3, CSRF 419, rol, cross-tenant, bloqueo de jerarquía, observación. **18** |
| `qa_poa_presupuestal.ps1` | 4 + 6 | Flujo POA Presupuestal, presupuesto calculado/congelado, bloqueo de rubros, observación + **item 6** (al aprobar, la rendición pasa a Aprobada(1) y se descuenta del saldo contable) + **tope por sobres** (enviar sobre el tope → 20; bajar el sobre tras enviar → aprobar bloquea) y **puerta de sobres** (programa 5 sin sobres: rubro/POA/rendición → 19; POA Indicadores y jerarquía permitidos; el Contador pasa) + **compuerta de TC** (sin cobertura aprobar bloquea → 22; al reponer tasas el aprobar recongela). **32** |
| `qa_rendicion.ps1` | 5 | Rendición imputada al rubro, límite Σ ≤ monto del rubro, cross-tenant, CSRF. **10** |
| `qa_oie.ps1` | 7 | OIE solo Contador (coordinador sin rutas), CSRF, ingreso híbrido (total de fuente = `programa_id` NULL / al sobre), egreso con programa obligatorio + sobre existente + tope por sobre (con exclusión del propio OIE al editar), sin comprobantes huérfanos, eliminar borra OIE+comprobante, saldos en vistas. **22** |
| `qa_reportes.ps1` | reportes (plan de montos F4-5) | Las 7 rutas de reporte + `/saldos_contables/saldos` **con TC y sin ningún TC**: 200 sin fatal siempre (antes: `DivisionByZeroError` con las tablas de TC vacías); saldos muestra la conversión al cierre con tasa visible y "sin tipo de cambio registrado" cuando falta. **19** |
| `qa_cierre_anual.ps1` | 8 | Cierre anual: rol (coordinador sin ruta), CSRF 419, snapshot correcto por fuente (inicial\|comprometido=Σ sobres\|contable), re-cierre upsert sin duplicar con cifras frescas y aviso de reemplazo, advertencia de rendiciones pendientes, y visibilidad del saldo de sobre solo con POA Aprobado (badge si no). **16** |
| `qa_usuarios.ps1` | 10 | CRUD de usuarios (solo Admin — el arnés crea su propio admin temporal, sin tocar al real): rol, CSRF, alta con password provisional bcrypt, email duplicado (validación + UNIQUE migr. 032) y formato, vínculo coordinador↔programa (alta y retiro al cambiar de cargo), anti mass-assignment del password (A2), y eliminación real que limpia `poa_indicadores` sin mentir. **14** |
| `qa_all.ps1` | — | **Runner**: corre los siete y resume (esperado: `TODOS LOS ARNESES OK`, 131 checks). |

## Cómo ejecutar (desde la raíz del proyecto)

```powershell
# 1) Arrancar el servidor de desarrollo (en otra terminal, desde la raíz):
local3000                 # = php -S localhost:3000

# 2) Correr todos los arneses:
pwsh -File database\qa_all.ps1

# Con un setup distinto (ruta de mysql.exe / URL / passwords) se pasan parámetros:
pwsh -File database\qa_all.ps1 -BaseUrl http://localhost:3000 -MysqlExe "C:\xampp\mysql\bin\mysql.exe" -PassCoord "Test1234*" -PassConta "admin1234"

# O un arnés individual:
pwsh -File database\qa_poa_presupuestal.ps1
```

## Prerrequisitos
- Servidor `local3000` corriendo y **MariaDB de XAMPP** arriba.
- BD `sysai` con migraciones **001-032** aplicadas + seed/datos demo (programa **1** con coordinador vinculado,
  jerarquía Resultado→Producto→Actividad y **rubros**; programa **5** para los tests cross-tenant).
- Usuarios de prueba: **coordinador@sysai.test / `Test1234*`** (programa 1) y **contador@sysai.test / `admin1234`**
  (esta última también es la del admin `robertokar97@gmail.com`).
- Parámetros configurables por script (`-BaseUrl`, `-MysqlExe`, `-PassCoord`, `-PassConta`) con defaults para el
  setup XAMPP documentado → portables a otra máquina cambiando solo la ruta de `mysql.exe` si difiere.
- ⚠️ Con la BD poblada por `seed_demo.sql` los usuarios son los `arcoiris.pe`; `qa_oie.ps1` acepta además
  `-EmailCoord`/`-EmailConta` para eso (verificado 22/22 con `contador@arcoiris.pe` / `coordinador.comunidad@arcoiris.pe`).
  Los otros tres arneses asumen datos del seed QA (`sysai.test`, programa 5, rubro 2) y no corren tal cual sobre el demo.

> Detalle de qué valida cada flujo y hallazgos corregidos durante el QA: ver
> `docs/historial-implementacion-items-2-6.md` (cada ítem tiene su bloque de QA).
