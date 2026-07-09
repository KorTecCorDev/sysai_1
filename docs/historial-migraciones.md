# Historial — Migraciones de BD (001-019)

> **Referencia.** Extraído de `CLAUDE.md` el 2026-07-09. Tabla completa de migraciones + estado histórico
> de la BD local. `CLAUDE.md` conserva solo el resumen operativo. Ver también `database/README.md`.

## Enfoque vigente

**Runner** `database/migrate.php`, baseline `database/schema_baseline.sql`, tabla de control
`schema_migrations`. Sustituye al antiguo `db/schema.sql` + `db/seed.sql` + `db/migracion_*.sql`
(la carpeta `db/` ya no existe).

Despliegue **greenfield** (la producción previa fue dada de baja): importar `schema_baseline.sql` +
migraciones 001-019 + `seed.sql` en una BD nueva y vacía — **ya no hay que reconciliar** `schema_migrations`
contra un estado previo. Flujo validado end-to-end en BD limpia.

## Estado histórico de la BD local (`sysai`)

✅ **Al 2026-06-03 — migraciones 001-013 aplicadas** sobre la base reconciliada (que ya tenía las de
seguridad 010-012); la 013 amplía el catálogo `tipo_comprobante`. Verificado: `coordinador_programa` creada,
`poa.presupuesto`→`decimal(14,2)`, `rendicion` con `estado`+`poa_rendicion_id`, `otros_ingresos_egresos`
con `programa_id` (backfill OK, `poa_id` eliminada), `poa_indicadores`/`poa_rendicion`/`fuente_presupuesto_anual`
creadas, vista `cantidad_fuentes_rendicion` eliminada, `tipo_comprobante` con 10 filas. Se hizo `mysqldump`
previo (`database/sysai_schema_backup_pre001_*.sql`, gitignored). Posteriormente se añadieron 014-019
(vínculo/observaciones/rendición-rubro/saldos). **Estado corriente esperado: 001-019 aplicadas.**

## Migraciones `database/migrations/`

| # | Archivo | Qué hace |
|---|---|---|
| 001 | `crear_coordinador_programa.sql` | Tabla intermedia `coordinador_programa` (usuario_id, programa_id, activo). |
| 002 | `reescribir_login_session_vista.sql` | `login_session_vista` ahora deriva `programa_id` de `coordinador_programa`. |
| 003 | `crear_poa_indicadores.sql` | Entidad documento `poa_indicadores` con estados. |
| 004 | `ampliar_poa_presupuesto.sql` | `poa.presupuesto` → `decimal(14,2)`. |
| 005 | `crear_poa_rendicion.sql` | Entidad documento `poa_rendicion`. |
| 006 | `rendicion_estado_y_poa_rendicion.sql` | `rendicion` += `estado`, `poa_rendicion_id`. |
| 007 | `oie_desvincular_poa.sql` | `otros_ingresos_egresos`: `poa_id` → `programa_id`. |
| 008 | `crear_fuente_presupuesto_anual.sql` | Tabla `fuente_presupuesto_anual` (fuente_id, anio, monto_inicial, presupuesto_comprometido, presupuesto_contable). |
| 009 | `eliminar_cantidad_fuentes_rendicion.sql` | Elimina la vista `cantidad_fuentes_rendicion`. |
| 010 | `vistas_saldos_contables.sql` *(seguridad B1)* | Crea las 4 vistas de saldos (`vista_total_ingresos/egresos`, `vista_saldo_contable`, `vista_saldo_fuente_financiamiento`). |
| 011 | `login_intentos_rate_limit.sql` *(seguridad C2)* | Tabla `login_intentos` (rate-limit de login por IP/email). |
| 012 | `recuperacion_password_segura.sql` *(seguridad A3)* | `usuario.reset_token`→varchar(64) sha256 + `reset_token_expira`; tabla `recuperacion_intentos`. |
| 013 | `ampliar_tipo_comprobante.sql` | Catálogo `tipo_comprobante`: `TCM002 Boleta`→`Boleta de venta` + nuevos TCM005-010 (Boleta de viaje, Recibo de caja/servicio básico/viaje/pago de servicios/general). Decisión 2026-06-03. |
| 014 | `coordinador_programa_backfill_vistas.sql` *(Fase 1)* | Backfill de `coordinador_programa` desde `poa` (cargo 3) + reescritura de `programas_sin_coordinador_vista` y `usuario_id_disponible_programa_vista` para derivar del vínculo activo. |
| 015 | `poa_indicadores_observacion.sql` *(Fase 2)* | `poa_indicadores` += `observacion` varchar(500). El Contador, al **Observar** (devolver) el documento, registra el motivo para que el Coordinador sepa qué subsanar (cambia la regla "retorno sin comentario" del Grupo 11). |
| 016 | `poa_observacion.sql` *(Item 4)* | `poa` += `observacion` varchar(500). Mismo patrón que la 015, para el flujo del POA Presupuestal. |
| 017 | `rendicion_rubro.sql` *(Item 5)* | `rendicion`: += `rubro_id` (FK), **se elimina `actividad_id`**, se limpia la tabla. Reescribe las 5 vistas que dependían de `rendicion.actividad_id` para derivar la actividad vía rubro (conservan columnas de salida + agregan `rubro_id`). |
| 018 | `saldo_solo_rendiciones_aprobadas.sql` *(Item 6)* | Reescribe las 2 vistas de saldo **contable** (`vista_total_egresos`, `vista_saldo_fuente_financiamiento`) para restar **solo** rendiciones `estado=1` (Aprobada). Las vistas de reporte que suman rendiciones se mantienen sin filtrar. |
| 019 | `backfill_rendiciones_poa_aprobado.sql` *(Item 6)* | Backfill: pone `estado=1` a las rendiciones de programas cuyo POA Presupuestal ya está Aprobado (consistencia con la regla de la 018). Idempotente; sin efecto en greenfield. |

## Hallazgos del esquema real (confirmados al volcar la BD)
- `cantidad_fuentes_rendicion` y `login_session_vista` eran **VISTAS**, no tablas.
- `poa.estado` ya es `int(11)` → admite 0-3 sin cambio de tipo (solo lógica de app).
- El monto del OIE no falta: vive en `oie_comprobante.monto` (igual que `rendicion.monto`).
- `otros_ingresos_egresos_admin_vista` dependía de `oie.poa_id` → recreada apuntando a `programa`.

## Resumen de brechas y su resolución

| Tabla / Campo | Situación previa | Resolución |
|---|---|---|
| `poa.estado` | Solo 0/1 (insuficiente) | Lógica de app 0=Borrador,1=Enviado,2=Observado,3=Aprobado (campo ya int) |
| `poa.presupuesto` | decimal(7,2) | Ampliado a decimal(14,2) (migr. 004) |
| `poa_rendicion` | No existía | Creada (migr. 005) |
| `poa_indicadores` | No existía | Creada con estados (migr. 003) |
| `detalle_financiamiento` | Sin monto | Sin cambio — el presupuesto es de la fuente |
| `fuente_presupuesto_anual` | No existía | Creada (migr. 008); `fuente_financiamiento.presupuesto` pasa a monto de referencia |
| `rendicion` | Sin `estado`/`poa_rendicion_id`; solo RUC | Añadidos (migr. 006), sin DNI |
| `coordinador_programa` | No existía | Creada (migr. 001) |
| `cantidad_fuentes_rendicion` | Vista obsoleta | Eliminada (migr. 009) |
| `otros_ingresos_egresos` | Vinculado a `poa_id` | Vinculado a `programa_id` (migr. 007) |
