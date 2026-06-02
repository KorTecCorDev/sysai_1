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
- Al cambiar coordinador: desactivar vínculo anterior, crear nuevo vínculo.
- Jerarquía: Programa → Resultado → Producto → Actividad → Rubro (POA Presupuestal)
- Jerarquía: Programa → Resultado → Producto → Actividad → Indicadores (POA Indicadores)

### POA Indicadores
- Se crea **antes** del POA Presupuestal.
- Misma jerarquía que el Presupuestal pero con indicadores en lugar de rubros.
- También requiere aprobación del Contador (mismo flujo de estados).
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
- Datos obligatorios: razón social, RUC, DNI (según tipo de comprobante).
- El monto de rendiciones comprometidas se muestra como "presupuesto comprometido" en la fuente.
- El descuento real del saldo contable ocurre al aprobar el POA_Rendición.

### POA Rendición
- Mismo flujo de aprobación que el POA Presupuestal: Borrador → Enviado → Observado → Aprobado.
- Lo elabora el Coordinador (y el Contador como coordinador en jefe).
- Al aprobarse por el Contador: las rendiciones se bloquean y se descuenta el `presupuesto_contable` de cada fuente.
- Luego de la aprobación: solo el Contador puede hacer modificaciones (complementarios).

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

---

## [SECCION: ESTADO ACTUAL DE LA BD — BRECHAS IDENTIFICADAS]

| Tabla / Campo | Situación actual | Cambio requerido |
|---|---|---|
| `poa.estado` | Solo 0/1 (insuficiente) | Ampliar a 0=Borrador, 1=Enviado, 2=Observado, 3=Aprobado |
| `poa.presupuesto` | decimal(7,2) = max 99,999 | Ampliar a decimal(14,2) |
| `poa` | Solo existe un tipo de POA | Crear entidad `poa_rendicion` separada |
| `detalle_financiamiento` | Sin monto | Pendiente definición (ver Grupo 10) |
| `fuente_financiamiento.presupuesto` | decimal(8,2), un solo campo | Agregar `presupuesto_comprometido` y `presupuesto_contable` |
| `rendicion` | Sin campo `estado`, sin `dni` | Agregar `estado`, `dni`, `poa_rendicion_id` |
| `rendicion` | Sin aprobación | Vincular a POA_Rendición |
| `usuario` / `programa` | Sin vínculo directo coordinador-programa | Pendiente definición (ver Grupo 8) |
| `cantidad_fuentes_rendicion` | Concepto eliminado (una sola fuente por rendición) | Eliminar tabla |
| POA Indicadores | Sin entidad documento (solo tablas de detalle) | Crear `poa_indicadores` con estados |
| `otros_ingresos_egresos` | Vinculado a `poa_id`, sin monto directo | Revisar vínculo (ver Grupo 13) |

---

## [SECCION: PREGUNTAS PENDIENTES — GRUPO 8 EN ADELANTE]

> Estado: **SIN RESOLVER** — responder antes de implementar los módulos afectados.

---

### Grupo 8 — Vínculo Coordinador-Programa

**8.1.** Actualmente el sistema deduce el `programa_id` del coordinador consultando sus POAs (`login_session_vista`). Esto es frágil si el coordinador no tiene POA aún. ¿El programa debe asignarse al coordinador **al crear el usuario** (campo directo en `usuario`)? ¿O prefieres una tabla intermedia `coordinador_programa` con campo `activo` para gestionar reemplazos?

---

### Grupo 9 — POA Rendición como documento

**9.1.** ¿El POA_Rendición es **uno solo por año por programa** (el coordinador acumula rendiciones y lo somete al finalizar), o puede haber **múltiples entregas parciales** en el año (ej. trimestrales)?

**9.2.** Desde que el coordinador **envía** el POA_Rendición al Contador, ¿puede seguir agregando rendiciones nuevas, o queda bloqueado hasta que el Contador lo observe o apruebe?

---

### Grupo 10 — Presupuesto comprometido y contable

**10.1.** En `detalle_financiamiento` (vínculo programa ↔ fuente), actualmente no hay monto. ¿Debe registrarse aquí el monto que esa fuente **asigna a ese programa para el año** (`monto_asignado`)? ¿O el presupuesto de la fuente no se reparte formalmente por programa?

**10.2.** El `presupuesto_comprometido` de una fuente, ¿es la **suma automática de los montos de todos los POAs Presupuestales aprobados** que usan esa fuente, o es un monto asignado manualmente por el Contador al crear el vínculo fuente-programa?

---

### Grupo 11 — POA Indicadores como documento

**11.1.** ¿El POA Indicadores debe ser un **registro propio** con estado (Borrador → Enviado → Aprobado) vinculado a programa y año, igual que el POA Presupuestal?

**11.2.** Al "copiar la estructura" para el POA Presupuestal: ¿Resultado, Producto y Actividad son los **mismos registros** compartidos entre ambos POAs, o el POA Presupuestal crea **copias independientes** de esa jerarquía con sus propios rubros?

---

### Grupo 12 — Rendición: campos faltantes

**12.1.** La tabla `rendicion` tiene `ruc` y `razon_social` pero no `dni`. Para persona natural el dato clave es el DNI, para empresa es el RUC. ¿Deben ser campos separados según tipo de comprobante, o un solo campo `numero_documento` con tipo?

**12.2.** ¿El campo `descripcion` (nullable) es suficiente para todos los datos del emisor, o hace falta un campo `nombre_emisor` separado del `detalle` del gasto?

---

### Grupo 13 — Saldos contables y cierre anual

**13.1.** Para el cierre anual: ¿el saldo sobrante de una fuente se maneja **actualizando `fuente_financiamiento.presupuesto`** al inicio del año nuevo, o prefieres una tabla `fuente_presupuesto_anual` (fuente_id, anio, monto_inicial, presupuesto_comprometido, presupuesto_contable) que permita ver el histórico año a año?

---

## [SECCION: ORDEN DE IMPLEMENTACION PROPUESTO]

> Pendiente confirmación — definir luego de resolver preguntas Grupos 8-13.

1. Migración de BD (nuevas columnas, nuevas tablas, eliminar `cantidad_fuentes_rendicion`)
2. Vínculo Coordinador-Programa
3. POA Indicadores (documento + flujo de aprobación)
4. POA Presupuestal (estados completos + flujo de aprobación)
5. Rendiciones (campo `dni`, vínculo `poa_rendicion_id`, límite por rubro)
6. POA Rendición (documento + flujo de aprobación + descuento de saldos)
7. Otros Ingresos/Egresos (solo Contador, descuento inmediato)
8. Saldos (presupuesto comprometido vs contable)
9. Reportes Excel (POA, POA_Rendición, Ingresos/Egresos)
10. Usuarios (vínculo activo coordinador-programa, desactivación)

---

## [SECCION: NOTAS TECNICAS]

- `cantidad_fuentes_rendicion`: tabla a eliminar. Una rendición solo tiene una fuente.
- `login_session_vista`: rediseñar cuando se resuelva el vínculo coordinador-programa (Grupo 8).
- `poa.presupuesto` decimal(7,2): insuficiente — ampliar antes de cualquier migración.
- `fuente_financiamiento.presupuesto` decimal(8,2): revisar si es suficiente para los montos reales.
- El correo de recuperación de contraseña usa credenciales SMTP hardcodeadas en `LoginController` — mover al `.env`.
- `detalle_actividad` es la tabla base para los indicadores del POA Indicadores (tiene: indicador_medido, medio_verificacion, supuesto, responsable).
- `avance_actividad`, `avance_producto`, `avance_resultado`: tablas de seguimiento de avances para uso futuro.
