# Modelo de datos — detalle (vistas SQL, discrepancias, deuda)

> **Referencia extendida.** Extraído de `CLAUDE.md` el 2026-07-09. El resumen caliente (jerarquía,
> movimientos contables, catálogos, identidad) sigue en `CLAUDE.md → [SECCION: MODELO DE DATOS]`.
> Aquí queda la lista completa de vistas + la deuda de esquema para consulta puntual.

## Vistas SQL principales (alimentan los modelos `*Vista`)
- **`login_session_vista`** → modelo `Login`. Reescrita (migración 002) usando `coordinador_programa` para
  dar `programa_id` a los coordinadores. Incluye `password` y `reset_token`.
- **`otros_ingresos_egresos_admin_vista`** → recreada apuntando a `programa` (antes dependía de `oie.poa_id`).
- Listados: `usuario_admin_vista`, `rendicion_admin`, `rubro_admin_vista`.
- Reportes: `reporte_poa_rubros`, `reporte_poa_rubros_sumas`, `reporte_poa_rendicion`, `reporte_rendiciones`,
  `reporte_ingresos` (oie_tipo=1), `reporte_egresos` (oie_tipo=2), `reporte_fuentes`,
  `reporte_fuentes_programa`, `reporte_fuentes_programa_rendicion`.
- Auxiliares: `fuente_por_actividad_vista`, `fuentes_por_poa_id`, `vista_fuentes_financiamiento_por_actividad`,
  `programa_poa_vista`, `programas_sin_coordinador_vista`, `usuarios_coordinador_vista`,
  `usuario_id_disponible_programa_vista`, `tipo_rubro_vista`, `vista_dolar`, `vista_euro`,
  `total_monto_rendiciones_por_actividad`.
  - ⚠️ `programas_sin_coordinador_vista` y `usuario_id_disponible_programa_vista` fueron **reescritas**
    (migración 014, Fase 1) para derivar de `coordinador_programa` (vínculo activo) en vez de `poa.usuario_id`.
- **Eliminada** (migración 009): `cantidad_fuentes_rendicion` (era VISTA, no tabla) — una rendición usa una sola fuente.

## ⚠️ Discrepancias esquema ↔ código / deuda de datos
1. `usuario` **no tiene** columnas `intentos`/`estado` pero código histórico de `Login.php` las referenciaba.
   > En la rama de seguridad esto se resolvió (control por sesión + tabla `login_intentos`). **En la rama
   > actual, verificar** si ese código muerto sigue presente antes de confiar en el bloqueo por intentos.
2. **Auditoría sin implementar:** existe tabla `auditoria` y `ActiveRecord::setUsuarioActual()`, pero el dump
   no trae triggers y `auditoria.id` no era AUTO_INCREMENT → la tabla nunca se llena automáticamente.
3. **Overflow de montos (dump original):** `monto`/`presupuesto` eran `decimal(7,2)` (máx 99 999.99) en `rubro`,
   `rendicion`, `oie_comprobante`, `poa`; `fuente_financiamiento.presupuesto` `decimal(8,2)`.
   > **`poa.presupuesto` ya se amplió a `decimal(14,2)`** (migración 004). (La rama de seguridad proponía
   > `decimal(12,2)` en `db/schema.sql`; el valor vigente es el de la migración.) Pendiente revisar overflow
   > en `rubro`/`rendicion`/`oie_comprobante` si no lo cubre otra migración.
4. `avance` era `decimal(2,2)` (rango máx 0.99 — no admite 100%) en `avance_actividad`/`avance_resultado`. Tablas de uso futuro.
5. `rendicion.fecha_original` era `varchar(500)` mientras `oie_comprobante.fecha_original` es `date` (inconsistencia).
6. `email` de `usuario` no es UNIQUE.
7. `reporte_poa_rubros_sumas` usaba `SUM(DISTINCT u.monto)` y joins con fan-out cartesiano por fuentes →
   posible **bug de reporte** (inflado). Corregido en la rama de seguridad; verificar en producción.

## Notas técnicas puntuales (misceláneas, ya reflejadas en migraciones)
- `cantidad_fuentes_rendicion`: vista eliminada (migr. 009). Una rendición solo tiene una fuente.
- `login_session_vista`: rediseñada usando `coordinador_programa` (tabla intermedia con `activo`) — migr. 002.
- `poa.presupuesto`: ampliado a `decimal(14,2)` (migr. 004).
- `fuente_financiamiento.presupuesto`: pasa a ser solo monto de referencia. Los saldos anuales van en `fuente_presupuesto_anual`.
- `detalle_actividad`: tabla base de los indicadores del POA Indicadores (`indicador_medido`, `medio_verificacion`, `supuesto`, `responsable`).
- `avance_actividad`, `avance_producto`, `avance_resultado`: tablas de seguimiento de avances para uso futuro.
- `rendicion.dni`: no agregar — se usa solo RUC para cualquier proveedor (empresa o persona natural).
- Las vistas del dump original traían `DEFINER=root@localhost` → ajustar al importar en Hostinger.
- `php -S` no procesa `.htaccess` → en dev no aplican los bloqueos de archivos/carpetas; cuidar secretos.
