# Follow-ups técnicos (deuda pendiente)

> **Referencia.** Extraído de `CLAUDE.md` el 2026-07-09; sincronizado el 2026-07-16. Aquí se conserva el
> detalle completo (hechos y pendientes). El `CLAUDE.md` mantiene solo un resumen de los pendientes abiertos.

## Hechos ✅
- [x] ✅ **SMTP externalizado al `.env`** (integrado en `integ/seguridad`). `LoginController` ya no tiene credenciales; el envío usa el helper `enviarTokenRecuperacion()` y `includes/config/mail.php` lee las claves `MAIL_*` del `.env`. Sin credenciales hardcodeadas en código trackeado.
- [x] ✅ **Seed data** para despliegue desde cero: `database/seed.sql` (idempotente, `INSERT IGNORE`) con catálogos (`cargo`, `tipo_programa`, `tipo_rubro`, `oie_tipo`, `oie_tipo_comprobante`, `tipo_comprobante`, `categoria_rubro`, `subcategoria_rubro`) + usuario admin inicial (`admin@arcoiris.pe` / `Arcoiris2026*`, temporal). Validado en BD limpia. Documentado en `database/README.md`.
- [x] ✅ Relación rendición↔rubro resuelta (migr. 017): `rendicion.rubro_id` reemplaza a `actividad_id`; límite Σ rendiciones ≤ monto del rubro. Ver item 5 del backlog (`docs/historial-implementacion-items-2-6.md`).

## Pendientes abiertos
- [x] ~~`usuario` no tiene columnas `intentos`/`estado` pero `Login.php` histórico las referencia~~ — **CERRADO 2026-07-15:** resuelto en `main` (migr. 011 creó `login_intentos`; `models/Login.php` la usa).
- [x] ~~Retirar/limpiar modelo `RendicionFuentesCantidadVista`~~ — **HECHO 2026-07-15** (plan de montos §5.4): modelo eliminado junto con sus llamadas en `ReportePoaRubrosController`; `$ffnro` no se usaba en ninguna vista.
- [ ] **B2 — Reportes POA/Excel inflados** (fan-out por fuentes) — ya existe la cifra correcta libre de
  fan-out (`comprometido` = Σ sobres por fuente; `vista_saldo_sobre` por sobre); falta que los **reportes
  Excel** (item 9, diferido a v1.1) la consuman en vez de repetir `fuente.presupuesto` por actividad.
- [ ] **B3 — Esquema desalineado** — ✅ resueltos: overflow de montos (migr. 026 + `montoNumerico()`/`MONTO_MAXIMO`),
  `rendicion.fecha_original` (ya era `date`) y `usuario.email` **UNIQUE** (migr. 032, item 10, 2026-07-16).
  **Quedan:** `avance decimal(2,2)` y auditoría sin triggers.
- [x] ~~**B4 — MAYÚSCULAS forzadas**~~ — **HECHO 2026-07-16:** se retiró `convertirAMayusculas()` y
  `$columnasSinMayuscula` de `ActiveRecord` (los datos se guardan tal como se ingresan) y los
  `style="text-transform: uppercase"` de los formularios (8 vistas). Los códigos autogenerados
  (`siguienteCodigoCorrelativo`/`Jerarquico`) no dependían del forzado (prefijos constantes en mayúsculas).
  Comparaciones contra catálogos en MAYÚSCULAS: sin impacto (collation `utf8_general_ci`, case-insensitive).
  Suite QA 131/131.
- [ ] **B5 — Código muerto / de otro proyecto:** `includes/templates/formulario_propiedades.php`, `formulario_vendedores.php`, `anuncios.php` (parecen de bienes raíces); `setImagen/borrarImagen` sin validar archivo.
- [ ] **B6 — `validarPropiedadArray()`** sin `isset` (warnings).
- [ ] **B7 — Deuda de build:** `@import` Sass deprecated (migrar a `@use/@forward`); SVGs commiteados en `build/css/`.
- [ ] Confirmar con el **contador de la organización** el mapeo compra/venta del TC (ingreso→compra,
  gasto→venta, saldo→compra): está derivado por lógica NIC 21, no por norma interna (plan de montos §5.3).
- [x] ~~**`sql_mode` sin `STRICT_TRANS_TABLES`**~~ — **HECHO 2026-07-16:** `conectarDB()` fija
  `SET SESSION sql_mode = 'STRICT_TRANS_TABLES,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'` (por sesión →
  portable a Hostinger sin `my.cnf`; cubre app + `migrate.php`). También se hizo explícito
  `mysqli_report(MYSQLI_REPORT_OFF)` (estaba documentado pero no existía en el código; sin él, PHP 8.2 lanza
  excepciones y el patrón del código es comprobar valores de retorno). Verificado: replay greenfield
  (baseline + 001-032 + seed/seed_demo/seed_qa) bajo estricto sin errores + suite QA 131/131.
  Nota: `includes/config/database.php` ya estaba versionado (sin secretos) — se corrigió la doc que decía "gitignored".
- [ ] Revisar las discrepancias restantes del modelo de datos (ver `docs/modelo-datos-detalle.md`) y
  planificar las correcciones pendientes. (La verificación "contra producción" ya no aplica: la instancia
  de Hostinger fue dada de baja el 2026-06-03; el próximo despliegue es greenfield.)
