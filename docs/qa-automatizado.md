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
| `qa_poa_presupuestal.ps1` | 4 + 6 | Flujo POA Presupuestal, presupuesto calculado/congelado, bloqueo de rubros, observación + **item 6** (al aprobar, la rendición pasa a Aprobada(1) y se descuenta del saldo contable). **21** |
| `qa_rendicion.ps1` | 5 | Rendición imputada al rubro, límite Σ ≤ monto del rubro, cross-tenant, CSRF. **10** |
| `qa_all.ps1` | — | **Runner**: corre los tres y resume (esperado: `TODOS LOS ARNESES OK`, 49 checks). |

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
- BD `sysai` con migraciones **001-019** aplicadas + seed/datos demo (programa **1** con coordinador vinculado,
  jerarquía Resultado→Producto→Actividad y **rubros**; programa **5** para los tests cross-tenant).
- Usuarios de prueba: **coordinador@sysai.test / `Test1234*`** (programa 1) y **contador@sysai.test / `admin1234`**
  (esta última también es la del admin `robertokar97@gmail.com`).
- Parámetros configurables por script (`-BaseUrl`, `-MysqlExe`, `-PassCoord`, `-PassConta`) con defaults para el
  setup XAMPP documentado → portables a otra máquina cambiando solo la ruta de `mysql.exe` si difiere.

> Detalle de qué valida cada flujo y hallazgos corregidos durante el QA: ver
> `docs/historial-implementacion-items-2-6.md` (cada ítem tiene su bloque de QA).
