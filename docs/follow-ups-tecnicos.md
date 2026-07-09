# Follow-ups técnicos (deuda pendiente)

> **Referencia.** Extraído de `CLAUDE.md` el 2026-07-09. Aquí se conserva el detalle completo (hechos y
> pendientes). El `CLAUDE.md` mantiene solo un resumen de los pendientes abiertos.

## Hechos ✅
- [x] ✅ **SMTP externalizado al `.env`** (integrado en `integ/seguridad`). `LoginController` ya no tiene credenciales; el envío usa el helper `enviarTokenRecuperacion()` y `includes/config/mail.php` lee las claves `MAIL_*` del `.env`. Sin credenciales hardcodeadas en código trackeado.
- [x] ✅ **Seed data** para despliegue desde cero: `database/seed.sql` (idempotente, `INSERT IGNORE`) con catálogos (`cargo`, `tipo_programa`, `tipo_rubro`, `oie_tipo`, `oie_tipo_comprobante`, `tipo_comprobante`, `categoria_rubro`, `subcategoria_rubro`) + usuario admin inicial (`admin@arcoiris.pe` / `Arcoiris2026*`, temporal). Validado en BD limpia. Documentado en `database/README.md`.
- [x] ✅ Relación rendición↔rubro resuelta (migr. 017): `rendicion.rubro_id` reemplaza a `actividad_id`; límite Σ rendiciones ≤ monto del rubro. Ver item 5 del backlog (`docs/historial-implementacion-items-2-6.md`).

## Pendientes abiertos
- [ ] `usuario` no tiene columnas `intentos`/`estado` pero `Login.php` histórico las referencia (bloqueo por intentos) → confirmar si es código muerto o falta migración antes de confiar en el bloqueo. *(Resuelto en la rama de seguridad con `login_intentos`; verificar en la actual.)*
- [ ] Retirar/limpiar modelo `RendicionFuentesCantidadVista` (su vista `cantidad_fuentes_rendicion` fue eliminada en migración 009).
- [ ] **B2 — Reportes POA inflados en producción** (fan-out por fuentes) — corregido en la rama de seguridad, falta portar/migrar.
- [ ] **B3 — Esquema desalineado** (overflow de montos en `rubro`/`rendicion`/`oie_comprobante`; `avance decimal(2,2)`; `rendicion.fecha_original varchar`; `email` no UNIQUE; auditoría sin triggers/AUTO_INCREMENT) — revisar qué cubren las migraciones actuales vs. lo corregido en la rama de seguridad.
- [ ] **B4 — MAYÚSCULAS forzadas** indiscriminadas (degrada calidad de datos; origen del bug C1).
- [ ] **B5 — Código muerto / de otro proyecto:** `includes/templates/formulario_propiedades.php`, `formulario_vendedores.php`, `anuncios.php` (parecen de bienes raíces); `setImagen/borrarImagen` sin validar archivo.
- [ ] **B6 — `validarPropiedadArray()`** sin `isset` (warnings).
- [ ] **B7 — Deuda de build:** `@import` Sass deprecated (migrar a `@use/@forward`); SVGs commiteados en `build/css/`.
- [ ] Confirmar contra **producción** todas las discrepancias del modelo de datos (ver `docs/modelo-datos-detalle.md`) y planificar la migración de las correcciones pendientes.
