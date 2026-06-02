# CLAUDE.md — SysAI · Organización Arco Iris

> Documento de contexto técnico y de negocio para el desarrollo del sistema.
> Diseñado para merge con otras instancias: cada sección es independiente y está etiquetada.

---

## [SECCION: ENTORNO]

- **Proyecto:** SysAI — Sistema de gestión presupuestal y rendición de cuentas
- **Organización:** Arco Iris (ONG sin fines de lucro)
- **Stack:** PHP MVC + Active Record propio · MySQL · Bootstrap · SCSS/Gulp · PHPSpreadsheet · PHPMailer
- **Entorno local:** XAMPP (Windows) — `C:/xampp/htdocs/sysai_1`
- **Producción:** Hostinger
- **Separación de entornos:** `.env` por entorno (ignorado en git). `.env.example` versionado como plantilla.
- **Credenciales de BD:** Solo en `.env`, nunca hardcodeadas. `includes/config/database.php` ignorado en git.
- **Moneda base:** Sol peruano (PEN). Conversiones a USD/EUR solo para reportes.

---

## [SECCION: ARQUITECTURA]

```
index.php          → Punto de entrada. Carga rutas según cargo_id de sesión.
iadmin.php         → Rutas del Administrador (cargo_id=1)
iconta.php         → Rutas del Contador (cargo_id=2)
icoordi.php        → Rutas del Coordinador (cargo_id=3)
Router.php         → Enrutador MVC personalizado
includes/app.php   → Bootstrap: funciones, config DB, autoload, conexión
includes/config/database.php → Lee .env, conecta MySQL
controllers/       → 18 controladores (lógica de negocio)
models/            → 56 modelos (Active Record + vistas SQL)
views/             → Vistas por módulo (admin, crear, actualizar, formulario)
build/             → CSS/JS/IMG compilados (output de Gulp)
src/               → SCSS y JS fuente
```

**Roles:**
| cargo_id | Nombre | Capacidades |
|---|---|---|
| 1 | Administrador | Root. Acceso total. |
| 2 | Contador | Coordinador en jefe + aprobador de POAs y rendiciones. Registra OIE (aprobación automática). |
| 3 | Coordinador | Gestiona su programa asignado. Elabora POAs y rendiciones. |

---

## [SECCION: REGLAS DE NEGOCIO — CONFIRMADAS]

### Fuentes de Financiamiento
- Una fuente es una organización de caridad con presupuesto anual.
- El monto es referencial hasta que se elaboren los POAs presupuestales.
- Una fuente puede estar vinculada a múltiples programas (`detalle_financiamiento`).
- Solo las fuentes vinculadas al programa pueden usarse en rendiciones.
- No hay límite de fuentes por programa.
- **Dos tipos de presupuesto por fuente:**
  - `presupuesto_comprometido`: se define al aprobar el POA Presupuestal.
  - `presupuesto_contable`: monto inicial + ingresos − rendiciones aprobadas − otros egresos.
- El saldo contable sobrante al cierre del año se traslada al siguiente periodo.

### Programa
- Cada programa tiene **uno y solo un coordinador activo**.
- Vínculo coordinador-programa mediante tabla intermedia `coordinador_programa` (usuario_id, programa_id, activo).
- Al cambiar coordinador: desactivar vínculo anterior (`activo=0`), crear nuevo vínculo (`activo=1`).
- Un coordinador solo puede tener un programa activo a la vez.
- Jerarquía: Programa → Resultado → Producto → Actividad → Rubro (POA Presupuestal)
- Jerarquía: Programa → Resultado → Producto → Actividad → Indicadores (POA Indicadores)

### POA Indicadores
- Se crea **antes** del POA Presupuestal.
- Tiene su propio registro documento con **estados**: Borrador(0) → Enviado(1) → Observado(2) → Aprobado(3). Mismo flujo que el Presupuestal.
- La jerarquía Resultado → Producto → Actividad es **compartida** entre el POA Indicadores y el POA Presupuestal. El Presupuestal solo agrega Rubros a la misma estructura.
- Indicadores solo a nivel de Actividad (las tablas `indicador_producto` e `indicador_resultado` son para uso futuro).

### POA Presupuestal
- Se elabora a partir de la estructura del POA Indicadores.
- **Estados:** Borrador(0) → Enviado(1) → Observado(2) → Aprobado(3)
- El Coordinador puede editar hasta que el Contador lo apruebe.
- Si el Contador observa, retorna al Coordinador para modificación (sin comentario).
- Luego de aprobado: solo visible para el Coordinador. Solo el Contador puede modificarlo (como adenda).
- Al aprobarse: define el `presupuesto_comprometido` del programa y de las fuentes vinculadas.
- Máximo un POA Presupuestal activo por programa por año.

### Rubros
- Un rubro es un Bien o Servicio (campo `tipo_rubro`: TRB001=Bien, TRB002=Servicio).
- Tiene un monto máximo. La suma de todas las rendiciones contra ese rubro no puede exceder ese monto.
- Pueden registrarse múltiples rendiciones por rubro.

### Rendiciones
- Las elaboran: Coordinador y Contador.
- Siempre vinculadas a un rubro (bien o servicio).
- Una rendición usa **una sola fuente** vinculada al programa.
- Documentos de sustento válidos: factura, boleta, recibo de viaje, declaración jurada, recibo de pago de servicios, recibo general.
- Datos obligatorios: **RUC** y **razón social / nombre**. El RUC identifica a cualquier proveedor (empresa o persona natural). No se usa DNI.
- El monto de rendiciones comprometidas se muestra como "presupuesto comprometido" en la fuente.
- El descuento real del saldo contable ocurre al aprobar el POA_Rendición.

### POA Rendición
- **Uno solo por programa por año.** El Contador puede re-abrirlo después de aprobado para agregar más rendiciones (no se crea un documento nuevo).
- Mismo flujo de aprobación que el POA Presupuestal: Borrador → Enviado → Observado → Aprobado.
- Lo elabora el Coordinador (y el Contador como coordinador en jefe).
- Una vez **enviado**, el Coordinador queda bloqueado: no puede agregar rendiciones hasta que el Contador lo observe (devuelva) o apruebe.
- Al aprobarse por el Contador: las rendiciones se bloquean y se descuenta el `presupuesto_contable` de cada fuente.
- Luego de la aprobación: el Contador puede re-abrirlo para agregar rendiciones adicionales.

### Otros Ingresos / Egresos (OIE)
- **SOLO los registra el Contador** — aprobación automática (descuento/suma inmediata).
- Ingresos: nuevas donaciones. Suman al `presupuesto_contable` de la fuente.
- Egresos: gastos fuera del POA. Restan al `presupuesto_contable` de la fuente.
- Requieren selección de programa y fuente vinculada al programa.
- Incluyen recibo con datos del benefactor/proveedor.

### Tipo de Cambio
- Módulo de registro de USD y EUR.
- Se usa el **tipo de cambio vigente** (último registrado) para todos los cálculos de conversión.
- No hay cálculo con tipo de cambio histórico.

### Saldos
- Se muestran stats de fuentes y programas.
- Saldo fuente = monto_inicial + ingresos − rendiciones_aprobadas − otros_egresos
- El saldo de programas solo se muestra cuando el POA Presupuestal está aprobado.
- Al registrar rendiciones aprobadas u OIE, los saldos se actualizan.
- `presupuesto_comprometido` de una fuente = suma automática de los montos de todos los POAs Presupuestales aprobados que usan esa fuente.
- El presupuesto de la fuente **no se reparte formalmente por programa** (`detalle_financiamiento` sin campo monto_asignado).

### Cierre Anual
- El saldo sobrante de cada fuente al cierre del año se registra en la tabla `fuente_presupuesto_anual` (fuente_id, anio, monto_inicial, presupuesto_comprometido, presupuesto_contable).
- Permite ver el histórico de saldos año a año por fuente.

---

## [SECCION: ESTADO ACTUAL DE LA BD — BRECHAS IDENTIFICADAS]

> **Estado migraciones BD: APLICADAS** (`database/migrations/001`-`009`, runner `database/migrate.php`).
> Baseline previo versionado en `database/schema_baseline.sql`. Tabla de control `schema_migrations`.
>
> **Hallazgos del esquema real (confirmados al volcar la BD):**
> - `cantidad_fuentes_rendicion` y `login_session_vista` son **VISTAS**, no tablas.
> - `poa.estado` ya es `int(11)` → admite 0-3 sin cambio de tipo (solo lógica de app).
> - El monto del OIE **no falta**: vive en `oie_comprobante.monto` (igual que `rendicion.monto`).
> - `otros_ingresos_egresos_admin_vista` dependía de `oie.poa_id` → recreada apuntando a `programa`.
> - Convención del esquema: `InnoDB`, `CHARSET=utf8 COLLATE=utf8_general_ci`, `datetime` para `fecha`.


| Tabla / Campo | Situación actual | Cambio requerido |
|---|---|---|
| `poa.estado` | Solo 0/1 (insuficiente) | Ampliar a 0=Borrador, 1=Enviado, 2=Observado, 3=Aprobado |
| `poa.presupuesto` | decimal(7,2) = max 99,999 | Ampliar a decimal(14,2) |
| `poa` | Solo existe un tipo de POA | Crear entidad `poa_rendicion` separada |
| `detalle_financiamiento` | Sin monto | Sin cambio — el presupuesto es de la fuente, no se reparte por programa |
| `fuente_financiamiento.presupuesto` | decimal(8,2), un solo campo | Reemplazar por tabla `fuente_presupuesto_anual` |
| `rendicion` | Sin campo `estado`, sin `poa_rendicion_id` | Agregar `estado`, `poa_rendicion_id` (sin DNI — solo RUC) |
| `rendicion` | Sin aprobación | Vincular a POA_Rendición |
| `usuario` / `programa` | Sin vínculo directo coordinador-programa | Crear tabla `coordinador_programa` (usuario_id, programa_id, activo) |
| `cantidad_fuentes_rendicion` | Concepto eliminado (una sola fuente por rendición) | Eliminar tabla |
| POA Indicadores | Sin entidad documento (solo tablas de detalle) | Crear `poa_indicadores` con estados; jerarquía compartida con POA Presupuestal |
| `otros_ingresos_egresos` | Vinculado a `poa_id`, sin monto directo | Desvincular de `poa` — vincular solo a programa y fuente |
| `fuente_presupuesto_anual` | No existe | Crear tabla (fuente_id, anio, monto_inicial, presupuesto_comprometido, presupuesto_contable) |

---

## [SECCION: DECISIONES CONFIRMADAS — GRUPOS 8-13]

> Estado: **RESUELTO** — implementar según estas decisiones.

---

### Grupo 8 — Vínculo Coordinador-Programa ✓
- **Tabla intermedia `coordinador_programa`** (usuario_id, programa_id, activo).
- Un coordinador tiene **un solo programa activo** a la vez.
- Al reemplazar: `activo=0` al vínculo anterior, nuevo registro con `activo=1`.
- `login_session_vista` se rediseña usando `coordinador_programa`.

### Grupo 9 — POA Rendición como documento ✓
- **Un solo POA Rendición por programa por año.**
- Una vez **enviado**, el Coordinador queda bloqueado (no puede agregar rendiciones).
- Después de **aprobado**, el Contador puede **re-abrir** el mismo POA para agregar rendiciones adicionales (no se crea documento nuevo).

### Grupo 10 — Presupuesto comprometido y contable ✓
- `detalle_financiamiento` **no** tiene campo `monto_asignado`. El presupuesto es de la fuente, no se reparte por programa.
- `presupuesto_comprometido` de una fuente = **suma automática** de los montos de todos los POAs Presupuestales aprobados que usan esa fuente.

### Grupo 11 — POA Indicadores como documento ✓
- Tiene **su propio registro documento** con estados Borrador → Enviado → Observado → Aprobado.
- La jerarquía (Resultado → Producto → Actividad) es **compartida** entre el POA Indicadores y el POA Presupuestal. El Presupuestal solo agrega Rubros a la misma estructura sin duplicar registros.

### Grupo 12 — Rendición: campos del emisor ✓
- Se usa **solo RUC** como documento de identificación (tanto empresa como persona natural).
- Campos en `rendicion`: `ruc` + `razon_social`. Sin campo `dni`.
- Se elimina la referencia a DNI de cualquier formulario o validación.

### Grupo 13 — Saldos contables y cierre anual ✓
- Crear tabla **`fuente_presupuesto_anual`** (fuente_id, anio, monto_inicial, presupuesto_comprometido, presupuesto_contable).
- Permite ver el histórico de saldos año a año por fuente.
- `fuente_financiamiento.presupuesto` pasa a ser solo el monto de referencia inicial de la fuente.

---

## [SECCION: ORDEN DE IMPLEMENTACION]

1. **Migración de BD** — ✅ **COMPLETADO** (`database/migrations/001`-`009`)
2. Vínculo Coordinador-Programa
3. POA Indicadores
4. POA Presupuestal
5. Rendiciones
6. POA Rendición
7. Otros Ingresos/Egresos
8. Saldos
9. Reportes Excel *(diferido v1.1)*
10. Usuarios

---

## [SECCION: BACKLOG DE IMPLEMENTACION — PENDIENTE]

> Cada ítem es código (modelo/controlador/vista/rutas). La BD ya está migrada.
> Rutas por rol: registrar cada acción nueva en `iadmin.php`, `iconta.php`, `icoordi.php` según corresponda.

### 2 — Vínculo Coordinador-Programa  *(linchpin: va primero)*
- [ ] Modelo `CoordinadorPrograma` (tabla `coordinador_programa`).
- [ ] CRUD de asignación: al asignar, desactivar (`activo=0`) el vínculo previo del coordinador y crear el nuevo (`activo=1`). Validar regla "un coordinador = un programa activo".
- [ ] Ajustar alta de usuario coordinador para crear su vínculo de programa.
- [ ] Verificar que el login ya consuma `programa_id` desde la vista reescrita (`login_session_vista`) — la vista ya fue migrada (002).
- [ ] Revisar vistas que aún deducen programa desde POA: `programas_sin_coordinador_vista`, `usuario_id_disponible_programa_vista` (basadas en `poa.usuario_id` → migrar a `coordinador_programa`).

### 3 — POA Indicadores (documento + flujo)
- [ ] Modelo `PoaIndicadores` (tabla `poa_indicadores`).
- [ ] Flujo de estados 0→1→2→3 (Borrador/Enviado/Observado/Aprobado).
- [ ] Coordinador elabora; Contador aprueba/observa.
- [ ] Reutiliza jerarquía compartida Resultado→Producto→Actividad (no duplicar).

### 4 — POA Presupuestal (estados + flujo)
- [ ] Estados completos 0-3 en `poa` (campo ya soporta el rango).
- [ ] Flujo aprobación Coordinador↔Contador (observación sin comentario retorna a Borrador editable).
- [ ] Al aprobar: calcular y persistir `presupuesto_comprometido` por fuente = Σ POAs Presupuestales aprobados que usan la fuente.
- [ ] Tras aprobado: solo visible al Coordinador; solo Contador modifica (adenda).
- [ ] Máximo un POA Presupuestal activo por programa por año.

### 5 — Rendiciones
- [ ] Vincular rendición a `poa_rendicion_id` y manejar `estado` (columnas ya creadas).
- [ ] Límite por rubro: Σ rendiciones contra el rubro ≤ monto del rubro. **OJO:** `rendicion` se vincula a `actividad_id`, no a `rubro_id` — definir cómo se imputa una rendición al rubro (¿agregar `rubro_id` a `rendicion`?). *(decisión técnica pendiente)*
- [ ] Identificación de emisor solo con RUC + razón social (ya en esquema).

### 6 — POA Rendición (documento + flujo + saldos)
- [ ] Modelo `PoaRendicion` (tabla `poa_rendicion`).
- [ ] Uno por programa por año. Flujo 0-3.
- [ ] Al **enviar**: bloquear al Coordinador (no agrega rendiciones).
- [ ] Al **aprobar**: bloquear rendiciones y descontar `presupuesto_contable` de cada fuente.
- [ ] Re-apertura por Contador → *diferido v1.1*.

### 7 — Otros Ingresos/Egresos
- [ ] Actualizar modelo `OtrosIngresosEgresos`: `poa_id` → `programa_id` (columna ya migrada). Quitar validación de `poa_id`.
- [ ] Solo Contador, aprobación automática (descuento/suma inmediata sobre `presupuesto_contable`).
- [ ] Monto desde `oie_comprobante.monto`.

### 8 — Saldos
- [ ] Calcular sobre `fuente_presupuesto_anual` (año vigente): contable = monto_inicial + ingresos − rendiciones_aprobadas − otros_egresos.
- [ ] Mostrar `presupuesto_comprometido` vs `presupuesto_contable`.
- [ ] Saldo de programa visible solo con POA Presupuestal aprobado.

---

## [SECCION: DIFERIDO A v1.1]

- **Re-apertura del POA Rendición** por el Contador (MVP = ciclo enviar→aprobar una vez).
- **Rollover de cierre anual** — lógica de traspaso de saldo entre años (tabla `fuente_presupuesto_anual` ya existe).
- **Reportes Excel nuevos/ampliados** — se conserva lo existente; no se agregan nuevos en MVP.
- **Avances** (`avance_actividad`, `avance_producto`, `avance_resultado`) — seguimiento, uso futuro.

---

## [SECCION: FOLLOW-UPS TECNICOS]

- [ ] Retirar/limpiar modelo `RendicionFuentesCantidadVista` (su vista `cantidad_fuentes_rendicion` fue eliminada en migración 009).
- [ ] Mover credenciales SMTP hardcodeadas de `LoginController` al `.env`.
- [ ] `usuario` no tiene columnas `intentos`/`estado` pero `Login.php` las referencia (bloqueo por intentos) → código muerto o falta migración. Revisar antes de confiar en el bloqueo.
- [ ] **Seed data** para despliegue desde cero: catálogos (`cargo`, `tipo_rubro`, `tipo_comprobante`, `oie_tipo`, `oie_tipo_comprobante`, `tipo_programa`) + usuario admin inicial. (No incluido en migraciones.)
- [ ] Validar relación rendición↔rubro (ver ítem 5 del backlog).

---

## [SECCION: NOTAS TECNICAS]

- `cantidad_fuentes_rendicion`: tabla a eliminar. Una rendición solo tiene una fuente.
- `login_session_vista`: rediseñar usando `coordinador_programa` (tabla intermedia con `activo`).
- `poa.presupuesto` decimal(7,2): insuficiente — ampliar a decimal(14,2) antes de cualquier migración.
- `fuente_financiamiento.presupuesto`: pasa a ser solo monto de referencia. Los saldos anuales van en `fuente_presupuesto_anual`.
- El correo de recuperación de contraseña usa credenciales SMTP hardcodeadas en `LoginController` — mover al `.env`.
- `detalle_actividad` es la tabla base para los indicadores del POA Indicadores (tiene: indicador_medido, medio_verificacion, supuesto, responsable).
- `avance_actividad`, `avance_producto`, `avance_resultado`: tablas de seguimiento de avances para uso futuro.
- `rendicion.dni`: no agregar — se usa solo RUC para cualquier proveedor (empresa o persona natural).
