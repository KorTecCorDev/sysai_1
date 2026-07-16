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
> **Barrido de verificación 2026-07-16 contra la BD viva (`information_schema`):** los puntos 1, 3, 4, 5 y 6
> quedaron CERRADOS; quedan abiertos el 2 (auditoría, diferida a v1.1) y el 7 (reportes Excel, item 9 v1.1).
> Desde el mismo día `conectarDB()` activa `STRICT_TRANS_TABLES` por sesión → cualquier truncamiento futuro
> es error ruidoso, no dato corrupto.
1. ~~`usuario` sin columnas `intentos`/`estado` que `Login.php` referenciaba~~ — **CERRADO 2026-07-15:**
   resuelto en `main` (migr. 011 `login_intentos`; `models/Login.php` la usa).
2. **Auditoría sin implementar (⏸ DIFERIDA a v1.1, decisión 2026-07-16):** existe la tabla `auditoria` y
   `setUsuarioActual()` se invoca desde ~20 controladores, pero **no hay ningún trigger** que consuma
   `@usuario_actual` → la tabla nunca se llena. Hallazgos para cuando se implemente: `auditoria.usuario` es
   `varchar(8)` y la identidad de auditoría es el **email** desde la migr. 024 (truncaría bajo STRICT →
   redimensionar a `varchar(191)`), y verificar `auditoria.id` AUTO_INCREMENT.
3. ~~Overflow de montos~~ — **CERRADO (verificado en BD viva 2026-07-16):** `rubro.monto`, `rendicion.monto`,
   `oie_comprobante.monto` = `decimal(12,2)`; `poa.presupuesto`, `fuente_financiamiento.presupuesto`,
   `detalle_financiamiento.monto_asignado` = `decimal(14,2)` (migr. 004/020/026).
4. ~~`avance decimal(2,2)`~~ — **CERRADO (verificado en BD viva 2026-07-16):** `avance_actividad.avance` y
   `avance_resultado.avance` son `decimal(5,2)` y `avance_producto.avance` `decimal(7,2)` → sí admiten 100.00.
   La nota "decimal(2,2)" venía de un dump anterior al baseline versionado. Tablas de uso futuro (v1.1).
5. ~~`rendicion.fecha_original` `varchar(500)`~~ — **CERRADO:** ya es `date` (verificado 2026-07-15).
6. ~~`usuario.email` sin UNIQUE~~ — **CERRADO:** migr. 032 (item 10, 2026-07-16).
7. ~~`reporte_poa_rubros_sumas` con `SUM(DISTINCT)` + fan-out~~ — **CERRADO 2026-07-16 (item 9 = B2):**
   el `SUM(DISTINCT)` ya no existía en la vista vigente y ningún consumidor la usaba (modelo huérfano
   eliminado; la vista SQL queda sin consumidores). El residuo real —sumas de rendición agrupadas a nivel
   actividad y desalineadas en el Excel— se resolvió con `reporte_poa_rendicion` por **rubro×fuente**
   (migr. 034: solo aprobadas, ejercicio vigente) + `ReporteRendicionXlsxBuilder`. Nueva tabla
   `transferencia_institucional` (migr. 033): transferencias al programa Institucional, UNIQUE
   (fuente, programa origen); su Σ se materializa como sobre del Institucional en `detalle_financiamiento`.

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
