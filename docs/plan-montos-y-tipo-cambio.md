# Plan — Montos, tipo de cambio y conversión contable

> **Estado: ✅ IMPLEMENTADO (2026-07-15, Fases 0-6 completas, suite QA 101/101).** Cubre las migraciones
> **026-031** (la 031 se sumó durante la implementación, ver notas). Se conserva como registro de decisiones.
> Reemplaza el follow-up genérico "B3 — overflow de montos" de `docs/follow-ups-tecnicos.md`.
> Todas las decisiones de negocio de §2 están **confirmadas por el usuario**. No volver a preguntar.
>
> **Notas de implementación (2026-07-15):**
> - **Migr. 031 (no prevista):** `reporte_fuentes` exponía `fecha/codigo/descripcion/monto` pero su modelo
>   (`ReporteFuentesVista`) y `findporRango('fuente_fecha', …)` esperaban alias `fuente_*` →
>   `/reporte/ingresosdesc` reventaba con "Unknown column". Lo destapó el arnés nuevo `qa_reportes.ps1`
>   (la ruta no tenía cobertura, como anticipaba §1.4). Se alineó la vista al modelo.
> - Las rutas `/reporte/rendicionesdesc` y `/reporte/ingresosdesc` solo estaban registradas para el **admin**:
>   el formulario del contador redirigía a un 404. Se registraron también en `iconta.php`.
> - Los convertidos agregados pueden ser **parciales** cuando hay filas pendientes de TC: `SUM()` ignora los
>   `NULL` y la vista expone `rendiciones_sin_tc` para el aviso ("Faltan N…"), como especifica la Fase 4.
> - `$usrcod` (nombre de archivo de los reportes) usaba `usuario.descripcion`, columna eliminada en la migr.
>   024 → ahora usa la parte local del email.
> - Higiene arrastrada (§5.4) ejecutada: `RendicionFuentesCantidadVista` retirada (modelo + llamadas) y los
>   follow-ups de `Login.php` y `fecha_original` cerrados en `CLAUDE.md`.
> - Verificaciones clave en vivo: los 4 casos de corrupción de §1.2 resueltos (3.5M exacto; "3,500,000.50" →
>   3500000.50; 999999999 y "abc" rechazados) y el caso del doble conteo de §2.4 exacto (vigente 1.2M, saldo A
>   800k, **capacidad asignable 0**).

## 1. Por qué

### 1.1 El bloqueo
`fuente_financiamiento.presupuesto` es `decimal(8,2)` → máximo **S/ 999,999.99**. Los presupuestos reales de
Arco Iris van de **S/ 1 millón a S/ 10 millones**. **El sistema hoy no puede almacenar ni una sola fuente
real.** No es riesgo futuro: el techo está por debajo del piso.

El `sql_mode` de MariaDB/XAMPP **no incluye `STRICT_TRANS_TABLES`**, así que el desborde es un *warning*, no un
error. Sumado a `mysqli_report(MYSQLI_REPORT_OFF)` en `conectarDB()`, la cadena es: el usuario ingresa
S/ 1'500,000 → `validar()` pasa (solo comprueba que el string no esté vacío) → MariaDB clampea a 999,999.99 →
`crear()` redirige con "creado correctamente". **Pérdida silenciosa e irrecuperable.**

### 1.2 Las tres vías de corrupción (verificadas en BD)

| El usuario escribe | Se guarda |
|---|---|
| `1,500,000.00` | **1.00** |
| `S/ 450000` | **0.00** |
| `1500000.00` | **999999.99** |
| `450000.00` | 450000.00 ✓ |

Las dos primeras **no dependen del monto**: golpean con cualquier cifra. El `placeholder` del campo es
literalmente `"S./"` — el formulario sugiere el formato que se guarda como cero.

No son tres bugs de tres campos: es **un solo bug** (`type="text"` + validación por truthiness) repetido en
cada formulario de dinero.

### 1.3 Estado real de los inputs de dinero

| Formulario | `type` declarado | Realidad |
|---|---|---|
| `views/fuente_financiamiento/formulario.php:23` | `text` | sin validación |
| `views/rubro/formulario.php:46` | **`float`** | no existe en HTML → cae a `text` |
| `views/ingreso_egreso/formulario.php:132` | **`money`** | no existe en HTML → cae a `text` |
| `views/rendicion/formulario.php:72` | `text` | sin validación |
| `views/rendicionff/formulario.php:22` | `text` | sin validación |
| `views/dfinanciamiento/crear.php:117` | `number step=0.01 min=0 max=$disponible` | ✅ **correcto — patrón a replicar** |
| `views/tcambio/dolar\|euro/formulario.php:4` | `number step="0.01"` | el navegador **rechaza 3.751** |

`type="float"` y `type="money"` no existen en HTML: alguien quiso validación numérica y el navegador la ignoró
en silencio.

### 1.4 El módulo de tipo de cambio

| # | Hallazgo | Impacto |
|---|---|---|
| 1 | `tipo_cambio decimal(7,2)` | SBS publica `3.751` → se guarda `3.75`. Mal en ambas direcciones: 5 dígitos enteros (el TC nunca pasa de ~10) y 2 decimales (necesita 3+) |
| 2 | `validar()` solo truthiness | `"0.001"` pasa → se guarda `0.00` |
| 3 | La conversión es una **división** (`ActiveRecord:699,700,1013,1014`) | TC `0.00` o ausente → **`DivisionByZeroError` fatal** (verificado en PHP 8.2) |
| 4 | `findlast()` hace `ORDER BY id DESC` | El "vigente" es el **último tecleado**, no el de fecha más reciente |
| 5 | `fecha` se fija en el constructor | Es el timestamp de registro, **no la fecha de vigencia**. No se puede registrar el TC de ayer |
| 6 | `crearDolar` redirige `resultado=1` fuera del `else` | Si `setUsuarioActual()` o el guardado fallan, **igual reporta éxito** |
| 7 | Dos tablas casi idénticas | `tipo_cambio_dolar` + `tipo_cambio_euro`, dos vistas, `crear/actualizar/eliminar` duplicados |
| 8 | Una sola columna `tipo_cambio` | No puede representar **compra** vs **venta** |

**Consecuencia hoy:** ambas tablas tienen **0 registros** (`seed_qa.sql` no siembra TC) → **`/reporte/poa`,
`/reporte/poarendicion` y `/reporte/poarubros` revientan con fatal**. Ningún arnés de QA toca las rutas de
reporte, por eso nunca se detectó.

---

## 2. Decisiones de negocio (confirmadas 2026-07-15)

1. **Moneda base = sol.** La conversión a USD/EUR es **contable**, no decorativa.
2. **El TC aplicado es el vigente a la FECHA DE OPERACIÓN** (`rendicion.fecha_original`), **congelado al
   registrar**. Ver §2.2.
3. **"TC vigente a una fecha"** = el registro con **`fecha_vigencia` máxima ≤ esa fecha** (convención contable
   para fines de semana y feriados). Nunca por `id`.
4. **Existen TC compra y TC venta.** Ingreso → compra. Gasto (rendición, rubro) → venta.
5. **La tasa de la SBS es informativa.** La dispara el Contador, queda visible y editable; nunca se guarda
   automáticamente como tasa contable ni se consulta desde la ruta de un reporte.
6. **El rubro es ADVERTENCIA, no bloqueo.** Ver §2.3.
7. **El ingreso suma al presupuesto de la fuente**, pero *presupuesto mostrado* ≠ *capacidad asignable*. Ver §2.4.
8. **Los 4 reportes que hoy no convierten** (`/reporte/rendiciones`, `/rendicionesdesc`, `/ingresos`,
   `/ingresosdesc`) **pasan a convertir** bajo estas mismas reglas.
9. **Rango de presupuestos:** S/ 1M–10M hoy; podrían crecer.

### 2.1 Por qué congelar el TC mantiene los reportes simples
Sin congelamiento, un reporte contable exigiría `SUM(monto / tc_de_su_fecha)` — reescribir las vistas de
agregación para unir con el TC por fecha. Con el TC congelado en la fila, el reporte hace
`SUM(monto_convertido)`: **sin join histórico, sin tocar la lógica de agregación.**

> ⚠️ Lo que el congelamiento **no** evita es la **cobertura**: resolver por `fecha_original` exige que existan
> filas de `tipo_cambio` cubriendo fechas pasadas. Esa cobertura se acumula sola conforme el Contador registra,
> más un backfill único en el setup (§Fase 6). Es un requisito de datos, no de arquitectura.

### 2.2 El TC de la fecha de operación — sustento

**Regla:** al registrar una rendición se resuelve `TipoCambio::vigente(moneda, rendicion.fecha_original)` y se
**copia el valor** a la fila.

| Momento candidato | Veredicto |
|---|---|
| **`fecha_original`** (fecha del comprobante) | ✅ **elegido** |
| registro (tecleo) | descartado |
| aprobación del POA | descartado |

**Sustento, atado a la realidad del sistema:**
1. **El dato ya está.** `rendicion.fecha_original` es `date`, obligatorio, y es la fecha del comprobante.
2. **Cuesta cero estructura.** `vigente(moneda, fecha)` ya hacía falta para feriados y fines de semana;
   pasar `fecha_original` en vez de `hoy` es un parámetro.
3. **Conserva el congelamiento.** Se copia el valor → inmune a que alguien edite o borre ese TC después.
4. **Nunca es peor que "el último TC".** Con registro esporádico de TC, ambos criterios caen en la misma tasa
   rancia; con registro regular, `fecha_original` acierta y "el último" falla. No hay escenario donde "el
   último" gane.
5. **Es NIC 21** → defendible ante un auditor o ante el donante sin explicaciones.

**Dónde bloquea:** la rendición nace **Pendiente(0) y no afecta el saldo hasta aprobarse** (regla existente),
así que:
- El **Coordinador registra sin bloqueo**. Si falta cobertura → `tc_usd`/`tc_eur` quedan `NULL` y la rendición
  se marca *"pendiente de tipo de cambio"*.
- El **Contador no puede aprobar** hasta que la cobertura exista.

La dependencia recae sobre **quien administra los TC**, en **la compuerta que ya administra**. Y como la tasa se
resuelve por `fecha_original`, **da igual cuándo se resuelva: el resultado es el mismo**. El congelamiento no es
un momento — es una caché contra ediciones futuras.

### 2.3 El rubro: advertencia + saldo con signo
El tope duro de una rendición sigue siendo **solo el saldo del sobre**. El rubro **no bloquea**, pero recupera
visibilidad:
```
saldo_rubro = rubro.monto − Σ rendiciones del rubro
   positivo → sobrante
   negativo → sobregasto ("excedente")
```
Al registrar una rendición que cruza el monto del rubro se **avisa sin impedir**. Esto no revierte la enmienda
del 2026-07-09 (el rubro sigue sin limitar el gasto); le devuelve la visibilidad y resuelve el
*"diferenciar sobrantes o excedentes"*: es una sola cifra con signo.

### 2.4 Ingresos: presupuesto mostrado ≠ capacidad asignable

Un ingreso puede ir **al total de la fuente** (`programa_id IS NULL`) o **a un sobre** (`programa_id = X`). En
ambos casos suma al presupuesto de la fuente. **Pero aplicarlo literal a la capacidad asignable cuenta el
dinero dos veces:**

> Fuente con presupuesto **S/ 1M**. Sobres A = 600k, B = 400k (Σ = 1M, sin remanente).
> Ingreso de **200k dirigido al sobre A**.
> Ese ingreso **ya entra al saldo de A** (`vista_saldo_sobre` lo suma: `600k + 200k = 800k`). Si además elevara
> la capacidad asignable a 1.2M, el Contador podría subir `monto_asignado` de A a 800k → saldo de A = **1M**.
> Los mismos 200k, contados dos veces.

**La aritmética correcta:**
```
dinero total de la fuente     = P + Σ TODOS los ingresos
dinero ya dentro de sobres    = Σ monto_asignado + Σ ingresos dirigidos a sobres
                                ─────────────────────────────────────────────────
capacidad asignable           = P + Σ ingresos SIN programa (NULL) − Σ monto_asignado
```
Los ingresos dirigidos a un sobre **se cancelan en ambos lados**: ya están asignados, *son* la asignación.

Por tanto:
- **Presupuesto mostrado de la fuente** = inicial + **todos** los ingresos.
- **Capacidad asignable** = inicial + solo los ingresos **`programa_id IS NULL`** − Σ `monto_asignado`.

Verificación con el ejemplo: presupuesto mostrado 1.2M ✓ · saldo A 800k ✓ · saldo B 400k ✓ · Σ saldos = 1.2M =
presupuesto mostrado ✓ · capacidad asignable = 0 ✓ (los 200k ya están en A).

> **`presupuesto` NO se muta.** Sigue siendo el compromiso inicial; el vigente se **calcula en vivo**
> (`inicial + ingresos`), como ya hacen todas las vistas de saldo. Un `UPDATE fuente SET presupuesto =
> presupuesto + monto` por cada ingreso obligaría a restar a mano al borrar o editar — y basta que falle una vez
> para que el presupuesto quede desviado para siempre, sin forma de detectarlo. Misma lógica que §2.6.

### 2.5 Qué se convierte con qué tasa

| Cifra | ¿Tiene fecha de operación? | Tasa |
|---|---|---|
| **Rendición** (gasto) | sí (`fecha_original`) | **venta** a esa fecha, congelada |
| **OIE ingreso** (donativo) | sí (`fecha`) | **compra** a esa fecha, congelada |
| **OIE egreso** | sí (`fecha`) | **venta** a esa fecha, congelada |
| **Rubro / presupuesto POA** | **no — es planificación** | **venta, al cierre** (vigente al generar el reporte) |
| **Saldo** (partida monetaria) | no aplica | **compra, al cierre** (NIC 21) |

### 2.6 Congelar el VALOR, no un FK
`/tcambio/dolar/actualizar` y `/eliminar` **existen y las tienen Admin y Contador** (`iadmin.php:139-141`,
`iconta.php:130-131,139`). Si la rendición guardara `tipo_cambio_id`, editar o borrar ese TC **reescribiría en
silencio la contabilidad de todas las rendiciones que lo usaron**. Por eso se copia el número. El
`tipo_cambio_id` se guarda aparte, solo como rastro de procedencia.

---

## 3. Fases

### 3.0 Orden de ejecución y puntos de corte

**⚠️ Las Fases 2-4 son un bloque ATÓMICO.** La Fase 2 elimina `tipo_cambio_dolar`, `tipo_cambio_euro`,
`vista_dolar` y `vista_euro`, que `ReportePoaRubrosController` todavía usa. Parar entre la 2 y la 4 deja el
sistema **peor que ahora**: hoy los reportes revientan por falta de TC; ahí reventarían por tablas inexistentes,
con la BD ya migrada. **No abrir la Fase 2 sin tiempo para llegar a la 4.**

**Puntos de corte seguros:** después de Fase 0 · después de Fase 1 · después de Fase 4. Ningún otro.

| Bloque | Fases | Estimado | ¿Shippable al final? |
|---|---|---|---|
| **A** | 0 + 1 | ~4 h | ✅ Sí — desbloquea el greenfield |
| **B** | 2 + 3 + 4 | ~8 h | ✅ Sí — atómico, no partir |
| **C** | 5 + 6 | ~3 h | ✅ Sí — aditivo |

> El bloque A no depende del B. El B no depende del C. Se pueden mergear por separado.

#### Checklist del bloque A *(turno tarde)*

**Fase 0 — migr. 026 (~2 h)**
1. [x] `database/migrations/026_montos_presupuesto_fuente.sql` — el `ALTER` + `INSERT IGNORE schema_migrations`.
2. [x] `php database/migrate.php` → verificar `SHOW COLUMNS FROM fuente_financiamiento LIKE 'presupuesto'`.
3. [x] `includes/funciones.php` — `montoNumerico()` + constante `MONTO_MAXIMO`.
4. [x] Modelos → `montoNumerico()` en el constructor y tope en `validar()`: `FuenteFinanciamiento`, `Rubro`,
       `Rendicion`, `OieComprobante`, `DetalleFinanciamiento`.
5. [x] Los 6 inputs de §1.3 → `type="number" step="0.01" min="0"`; quitar `text-transform: uppercase` y el
       `placeholder="S./"`.
6. [x] **Verificar en navegador** (no solo lint): registrar una fuente con `3500000` → se guarda exacto;
       con `3,500,000` → normaliza o rechaza visiblemente, **nunca `1.00`**; con `999999999` → rechaza por tope.

**Fase 1 — migr. 027 (~2 h)**
7. [x] `database/migrations/027_reglas_presupuesto.sql` — `vista_saldo_rubro` + reescritura de
       `vista_saldo_fuente_financiamiento` con `presupuesto_inicial` / `presupuesto_vigente` / `capacidad_asignable`.
8. [x] `DetalleFinanciamiento::validarLimiteAsignacion()` → comparar contra la **capacidad asignable** (§2.4).
9. [x] `Rendicion` → advertencia no bloqueante al cruzar `rubro.monto` (§2.3).
10. [x] UI: saldo del rubro con signo en `rubro/admin` y en el formulario de rendición; pantalla de saldos con
        las 3 cifras.
11. [x] **Verificar el caso del doble conteo** (§2.4): fuente 1M, sobres 600k/400k, ingreso de 200k **al sobre A**
        → presupuesto vigente 1.2M, saldo A 800k, **capacidad asignable 0**. Si sale 200k, la Fase 1 está mal.

> El paso 11 es el que prueba que la aritmética de §2.4 quedó bien. No omitirlo.

---

### Fase 0 — Migración 026: montos *(el bloqueo)*

**`database/migrations/026_montos_presupuesto_fuente.sql`**
- `ALTER TABLE fuente_financiamiento MODIFY presupuesto DECIMAL(14,2) NOT NULL DEFAULT 0.00;`
  Alinea con `detalle_financiamiento.monto_asignado`, `poa.presupuesto` y `fuente_presupuesto_anual.monto_inicial`,
  que **ya son `decimal(14,2)`**. Era la única columna sin ensanchar, siendo la raíz del árbol.
- **No hace falta recrear las vistas** que arrastran el tipo (`fuente_por_actividad_vista`,
  `vista_fuentes_financiamiento_por_actividad`, `reporte_fuentes`): **verificado que MariaDB re-resuelve el
  tipo tras el `ALTER`** (probado leyendo 5,000,000 a través de una vista no tocada).

**Helper de sanitización — `includes/funciones.php`**
```php
montoNumerico(?string $valor): ?float   // normaliza separadores de miles, símbolo S/, espacios;
                                        // devuelve null si no es un número válido
```
Un solo punto. No parches por campo.

**Validación en modelos** — `FuenteFinanciamiento`, `Rubro`, `Rendicion`, `OieComprobante`,
`DetalleFinanciamiento`, `TipoCambio`: numérico real, `> 0`, y tope superior.

**Tope de cordura:** constante única `MONTO_MAXIMO = 50_000_000.00` (S/ 50 millones).
- 5× el máximo declarado (S/ 10M) → margen de crecimiento.
- Atrapa el cero de más sobre 10M y los absurdos tipo `999999999`.
- **No atrapa dedazos plausibles** (1M tecleado como 10M pasa, porque 10M es legítimo). Contra eso la defensa
  es la invariante Σ sobres ≤ capacidad y el criterio del Contador, no la validación.
- Si queda corto, el fallo es **ruidoso y recuperable** (error en pantalla, una constante que se sube).

**Inputs** — todos los de §1.3 al patrón de `dfinanciamiento/crear.php:117`:
`type="number" step="0.01" min="0"` + `max` donde exista cota conocida. Quitar `text-transform: uppercase` de
campos numéricos y el `placeholder="S./"`.

**Topes absolutos donde la cadena relativa no llega:** `rubro.monto` (sin padre desde la enmienda 2026-07-09)
y **OIE ingreso** (un donativo no tiene techo de negocio). Solo tope de cordura en ambos, no regla de negocio.

Lo demás **ya está acotado por su padre y sale gratis**: `monto_asignado` ≤ capacidad asignable
(`DetalleFinanciamiento::validarLimiteAsignacion`, ver Fase 1), `rendicion.monto` ≤ saldo del sobre
(`Rendicion::validarLimiteSobre`), OIE egreso ≤ saldo del sobre (`OtrosIngresosEgresos::validarTopeSobre`).

---

### Fase 1 — Migración 027: reglas de presupuesto

**Capacidad asignable (§2.4).** `DetalleFinanciamiento::validarLimiteAsignacion()`
(`models/DetalleFinanciamiento.php:151-182`) hoy compara Σ sobres **solo** contra
`fuente_financiamiento.presupuesto`. Pasa a comparar contra:
```
presupuesto + Σ oie_comprobante.monto  WHERE oie_tipo_id = 1 (ingreso) AND programa_id IS NULL
```

**Vistas** — `vista_saldo_fuente_financiamiento` y `SaldoFuenteFinanciamientoVista::desglosePorFuente()`
exponen las tres cifras diferenciadas:
- `presupuesto_inicial` — el compromiso original (inmutable).
- `presupuesto_vigente` = inicial + **todos** los ingresos.
- `capacidad_asignable` = inicial + ingresos `NULL` − Σ `monto_asignado`  ← reemplaza al "remanente sin asignar".

**`vista_saldo_rubro`** (nueva, §2.3): `rubro_id, monto, ejecutado, saldo` con
`saldo = monto − Σ rendiciones`. Puede ser negativo.

**Advertencia no bloqueante** en `Rendicion` cuando `Σ rendiciones del rubro > rubro.monto`. Se muestra el
sobregasto; no se impide guardar.

**UI** — saldo del rubro con signo en el listado de rubros y en el formulario de rendición.
Pantalla de saldos: presupuesto vigente + comprometido (Σ sobres) + capacidad asignable.

---

### Fase 2 — Migración 028: cimiento del tipo de cambio

```sql
CREATE TABLE tipo_cambio (
  id             INT AUTO_INCREMENT PRIMARY KEY,
  moneda         CHAR(3)       NOT NULL,          -- 'USD' | 'EUR'
  fecha_vigencia DATE          NOT NULL,          -- la fecha a la que aplica (≠ fecha de registro)
  compra         DECIMAL(12,6) NOT NULL,
  venta          DECIMAL(12,6) NOT NULL,
  origen         VARCHAR(20)   NOT NULL DEFAULT 'MANUAL',  -- MANUAL | SBS
  usuario_id     INT           NOT NULL,
  fecha          DATETIME      NOT NULL,          -- cuándo se registró
  UNIQUE KEY uq_moneda_fecha (moneda, fecha_vigencia)
);
DROP VIEW IF EXISTS vista_dolar, vista_euro;
DROP TABLE IF EXISTS tipo_cambio_dolar, tipo_cambio_euro;
```
> **Sin migración de datos:** ambas tablas tienen **0 registros** (verificado) y producción está dada de baja.
> El costo de normalizar esto está en su mínimo histórico, justo ahora.

**`decimal(12,6)`** — 6 decimales es la convención de ERP para tasas: cubre los 3 de la SBS con margen y evita
error acumulado (la conversión **divide**, y dividir amplifica la pérdida de precisión). 6 dígitos enteros son
de sobra; la holgura no cuesta nada.

**`models/TipoCambio.php`** (reemplaza `TipoCambioDolar`, `TipoCambioEuro`, `VistaDolar`, `VistaEuro`):
- `vigente(string $moneda, string $fecha): ?TipoCambio`
  → `WHERE moneda = ? AND fecha_vigencia <= ? ORDER BY fecha_vigencia DESC LIMIT 1`
- `coberturaFaltante(string $moneda, array $fechas): array`
- `validar()`: numérico vía `montoNumerico()`, `compra > 0`, `venta > 0`, banda de cordura
  (p. ej. `0.1 ≤ tc ≤ 100`), `venta >= compra`, `fecha_vigencia` no futura.

**`controllers/TipoCambioController.php`** — la moneda pasa a ser un **dato**, no un nombre de método:
`index/crear/actualizar/eliminar` con `?moneda=USD|EUR`. Elimina ~la mitad. **Arreglar el
`header('Location: ...?resultado=1')` que reporta éxito aunque el guardado falle** (hallazgo 1.4 #6).

**Rutas** — `/tcambio/{moneda}/...` en `iadmin.php` e `iconta.php`. Fuera de `icoordi.php`.

**Formularios** — `step="0.001"` (o `"any"`), campos separados compra/venta, sin `text-transform: uppercase`.

---

### Fase 3 — Migración 029: congelar el TC en las transacciones

```sql
ALTER TABLE rendicion
  ADD COLUMN tc_usd DECIMAL(12,6) NULL,   -- venta a fecha_original, copiada
  ADD COLUMN tc_eur DECIMAL(12,6) NULL,
  ADD COLUMN tipo_cambio_usd_id INT NULL, -- solo rastro de procedencia
  ADD COLUMN tipo_cambio_eur_id INT NULL;

ALTER TABLE oie_comprobante
  ADD COLUMN tc_usd DECIMAL(12,6) NULL,   -- compra si ingreso, venta si egreso
  ADD COLUMN tc_eur DECIMAL(12,6) NULL,
  ADD COLUMN tipo_cambio_usd_id INT NULL,
  ADD COLUMN tipo_cambio_eur_id INT NULL;
```
**`NULL` a propósito**, no `0`: `NULL` significa "sin conversión disponible" y permite mostrar "—" y contarlo en
el aviso. Un `0` provocaría división por cero. Las vistas usan `ROUND(monto / NULLIF(tc_usd, 0), 2)` → `NULL`
cuando falta, nunca un fatal ni un cero mentiroso.

**Al registrar** (`Rendicion::crear`, `OtrosIngresosEgresos::crear`):
1. Resolver `TipoCambio::vigente('USD', $fecha_operacion)` y `vigente('EUR', $fecha_operacion)`.
2. Si hay → copiar `compra`/`venta` según §2.5 a `tc_usd`/`tc_eur`. **Valores, no referencias.**
3. Si no hay → dejar `NULL` y marcar *"pendiente de tipo de cambio"*. **No se bloquea el registro.**

**Al aprobar el POA** (`Poa::aprobar` → rendiciones a estado 1): si alguna rendición del programa tiene
`tc_usd IS NULL`, **se reintenta resolver**; si sigue sin cobertura, **se bloquea la aprobación** con el detalle
de qué fechas faltan.

**Al actualizar una rendición:** el TC congelado **no se recalcula** salvo que cambie `fecha_original`.
*(Ver §5.2.)*

---

### Fase 4 — Migración 030: reportes y saldos

**Helper único de conversión** con guarda contra TC ausente o cero. Sustituye las **4 divisiones crudas**:

| Sitio | Cifra | Cambio |
|---|---|---|
| `ActiveRecord:699,700` | Σ rubros por actividad | pasa al helper, **tasa de cierre** |
| `ActiveRecord:1013,1014` | Σ rendiciones por fuente | **deja de dividir**; suma la columna ya convertida |

**Vistas de agregación** (`reporte_poa_rendicion`, `total_monto_rendiciones_por_actividad`) exponen
`SUM(ROUND(monto / NULLIF(tc_usd,0), 2))`. Sumar valores ya redondeados garantiza que **el total cuadre con la
suma de las filas mostradas**.

**Los 4 reportes que hoy no convierten** (§2 decisión 8) pasan a convertir con las mismas reglas:
`/reporte/rendiciones`, `/reporte/rendicionesdesc` (rendiciones → tasa congelada) y `/reporte/ingresos`,
`/reporte/ingresosdesc` (OIE → tasa congelada).

**Aviso de TC faltantes** — reportes y saldos **nunca revientan y nunca inventan**: muestran soles y
*"Faltan N tipos de cambio. Los importes en USD/EUR están incompletos."* con enlace a cargarlos.

**Saldos (`/saldos_contables/saldos`)** — hoy **no convierte nada**; funcionalidad nueva. Convierte al cierre
(compra) y **muestra siempre tasa, fecha de vigencia y origen**:
`USD 2'666,666 (TC compra 3.748 del 14/07/2026)`. Aviso si el vigente supera N días.

> **Rotular en el Excel:** los rubros van a tasa de cierre y las rendiciones a su tasa congelada → en un mismo
> reporte, "presupuesto USD" y "ejecutado USD" usan **tasas distintas**. Un % de ejecución calculado en dólares
> sale distorsionado. **La comparación es exacta en soles; el USD es traducción para el lector.**

---

### Fase 5 — Seeds y QA

- **`seed_demo.sql`** — las fuentes (180k–450k) están **un orden de magnitud por debajo de la realidad**; el
  comentario `-- OJO: decimal(8,2) => máx 999,999.99` (línea 97, y 93 en `seed_qa.sql`) demuestra que una sesión
  anterior **detectó el techo y moldeó los datos para caber en él** en vez de cuestionarlo. Rehacer con
  presupuestos de S/ 1M–10M. TC (`3.75`/`4.05`, líneas 247-250) → tasas reales de 3 decimales con compra/venta y
  varias `fecha_vigencia` para ejercitar la resolución por fecha.
- **`seed_qa.sql`** — sembrar TC (hoy no lo hace; por eso los reportes revientan en la BD local).
- **Arnés de QA de reportes** — no existe ninguno. Las rutas de reporte están **sin cobertura**, que es
  exactamente por qué el fatal pasó inadvertido. Mínimo: las 7 rutas de reporte, con TC y sin TC.
- **`qa_all.ps1`** — incluir `qa_oie.ps1` (hoy queda fuera; los 4 arneses nunca se corrieron juntos).

---

### Fase 6 — Importador SBS *(informativo)*

Dos usos:
1. **Día a día** — botón "traer TC de SBS" que **pre-llena el formulario**; el Contador revisa y guarda.
2. **Backfill de setup** — importación en lote de un rango de fechas, con `origen='SBS'`, para cubrir las
   rendiciones históricas que se carguen al arrancar (~130 días hábiles de 2026). Teclear eso a mano no lo hace
   nadie. Sigue siendo informativa en lo que importa: **la dispara el Contador, queda visible en el listado y él
   puede sobreescribir cualquier fila.**

**Restricciones no negociables:**
- **Nunca** en la ruta crítica de un reporte (un reporte que depende de un HTTP externo es un reporte que se cuelga).
- **Nunca** se guarda sin que el Contador lo dispare.
- Timeout corto y degradación limpia: si la API no responde, se teclea como siempre.

> Como el esquema ya lleva `origen` y `fecha_vigencia` desde la Fase 2, esta fase es **puramente aditiva** — no
> requiere migración. La salida HTTP del hosting greenfield está sin verificar, y el despliegue no debe depender
> de que responda un tercero.

---

## 4. Comportamiento del sistema una vez implementado

**Cargar un presupuesto.** El Contador registra "Compassion International" con S/ 3'500,000. El campo es
`type="number"`; si escribe `3,500,000` o `S/ 3500000`, `montoNumerico()` lo normaliza o **lo rechaza con error
visible** — nunca lo guarda como `1.00` ni `0.00`. Si escribe `999999999`, el tope de S/ 50M lo rechaza. Se
almacena **exacto**.

**Repartir en sobres.** Vincula la fuente a COMUNIDAD con `monto_asignado = S/ 2'000,000`.
`validarLimiteAsignacion()` verifica Σ sobres ≤ **capacidad asignable** contra el valor real. Capacidad
restante: S/ 1'500,000.

**Recibir un donativo.** Llega un ingreso de S/ 200,000. Si va **al total de la fuente**: presupuesto vigente
3.7M, capacidad asignable sube a 1.7M — el Contador puede repartirlo. Si va **al sobre de COMUNIDAD**:
presupuesto vigente 3.7M, el saldo del sobre sube a 2.2M, y la capacidad asignable **no cambia** (ya está
asignado). Nunca se cuenta dos veces.

**Registrar el tipo de cambio.** `/tcambio/USD/crear`: `fecha_vigencia = 15/07/2026`, `compra = 3.748`,
`venta = 3.751`. Se guarda con los **3 decimales intactos**. Si al día siguiente carga la tasa atrasada del
13/07, **el vigente al 15/07 sigue siendo 3.751** — se resuelve por fecha, no por orden de tecleo.

**Registrar una rendición.** El Coordinador registra S/ 12,000 con `fecha_original = 10/07/2026`:
1. Se valida ≤ saldo del sobre (tope duro).
2. Si Σ rendiciones del rubro cruza `rubro.monto`, **se avisa sin impedir**; el saldo del rubro pasa a negativo
   y se muestra así.
3. Se resuelve `vigente('USD', '2026-07-10')` → `venta = 3.749` (la tasa **de esa fecha**, no la de hoy) y se
   **congela** en la fila.
4. Si el Contador **edita o borra** ese registro de TC mañana, **esta rendición no cambia**.
5. Si no hay TC cubriendo el 10/07, la rendición **se guarda igual**, marcada *"pendiente de tipo de cambio"* —
   el Coordinador no queda bloqueado.

**Aprobar el POA.** El Contador aprueba; las rendiciones pasan a Aprobada(1) y descuentan el saldo contable. Si
alguna sigue *"pendiente de tipo de cambio"*, **la aprobación se bloquea** indicando qué fechas faltan. La
dependencia recae sobre quien administra los TC, en la compuerta que ya administra.

**Ver los saldos.** `S/ 1'988,000 · USD 530,258 (TC compra 3.748 del 15/07/2026)`. Tasa y fecha **siempre
visibles** → la desactualización se ve, no se esconde. Sin TC: solo soles y *"sin tipo de cambio registrado"*
con enlace. **Nunca un fatal, nunca una cifra inventada.**

**Generar un reporte.** Ninguna de las 7 rutas revienta sin TC. Las rendiciones suman sus montos convertidos **a
la tasa de su fecha de operación** (contable, NIC 21). Los rubros convierten a **tasa de cierre** (correcto: la
planificación no tiene fecha de operación). Lo que falte se muestra "—" y se cuenta en el aviso.

### Garantías
- **Ningún monto se trunca ni se corrompe en silencio.** El fallo es siempre visible.
- **Ninguna cifra convertida es inventada ni anónima**: siempre lleva tasa, fecha y origen.
- **Ninguna conversión pasada se reescribe** al editar el TC.
- **Ningún ingreso se cuenta dos veces.**
- **Ninguna ruta revienta** por falta de TC.
- **La comparación presupuesto vs. ejecutado es exacta en soles**; el USD/EUR es traducción.

---

## 5. Lo que este plan NO resuelve

### 5.1 🟡 Presupuesto por periodo *(diferido con criterio)*
El usuario definió: *"los presupuestos tienen varios periodos de entrega y duración; cada presupuesto anual es
un presupuesto por periodo"*. **Se difiere porque no se pierde nada al esperar:** todas las transacciones tienen
fecha (`rendicion.fecha_original` es `date`, el OIE tiene `fecha`), así que la historia se puede **rebanar
retroactivamente** en periodos con una migración mecánica y sin pérdida. Contrasta con el truncamiento de
`presupuesto`, que **destruye** el dato y por eso no puede esperar.

**El test que decide si sube de prioridad:** *¿existe hoy, para cada donante real, un solo número que sea "el
presupuesto vigente"?* Si para algún convenio no existe (p. ej. abril 2026–marzo 2028 con tramos que no calzan
con el año), meter un escalar ya es una mentira el día 1 y esto pasa a ser prerequisito del despliegue.

**Fecha límite natural:** el primer cierre de periodo.

Preguntas abiertas: ¿periodo = año calendario o rango arbitrario? ¿"periodos de entrega" (desembolsos) y
"duración" (ejecución) son el mismo eje? ¿Se gasta contra lo **comprometido** o lo **desembolsado**? ¿Se solapan
periodos de una misma fuente? ¿El rollover suma al periodo siguiente o se rastrea aparte, y a nivel de fuente o
de sobre? → **Item 8.**

### 5.2 🟡 TC congelado y edición de transacciones
Si se **edita** una rendición, el TC congelado no se recalcula salvo que cambie `fecha_original` (ahí sí debe
re-resolverse). Falta confirmar el comportamiento para rendiciones registradas antes de que existiera el
congelamiento (`tc_usd IS NULL`) y qué pasa si se corrige el monto (mismo TC, monto distinto → la conversión
cambia, que es lo correcto).

### 5.3 🟡 Compra/venta — mapeo por confirmar
El mapeo de §2.5 (ingreso→compra, gasto→venta, saldo→compra) está **derivado por lógica, no por norma**.
**Confirmar con el contador de la organización.**

### 5.4 🟢 Higiene arrastrada
- **Follow-up obsoleto:** el de `Login.php` con `intentos`/`estado` **ya está resuelto** (migr. 011 creó
  `login_intentos`; `models/Login.php:295-342` la usa). Cerrar en `docs/follow-ups-tecnicos.md`.
- **Follow-up obsoleto:** B3 menciona `rendicion.fecha_original varchar` — **ya es `date`**. Cerrar.
- **`RendicionFuentesCantidadVista`** consulta `cantidad_fuentes_rendicion`, vista **eliminada en migr. 009**.
  Se la llama desde `/reporte/poarendicion` y `/reporte/poarubros`, pero `consultarSql()` degrada a `[]` y
  `$ffnro` **no se usa en ninguna vista** → solo ensucia el `error_log`. Retirar modelo + llamadas.
- **Rama sin mergear:** `worktree-higiene-artefactos-y-doc` (`8dfd790`) — destrackea 3 `.xlsx` generados y
  alinea la tabla de migraciones del `CLAUDE.md`. Trivial.
- **`sql_mode` sin `STRICT_TRANS_TABLES`** — evaluar activarlo en el greenfield: convertiría todo truncamiento
  silencioso futuro en error ruidoso. **Requiere probar la app entera antes** (podría romper inserts que hoy
  pasan por tolerancia).

### 5.5 🟢 Fuera de alcance
- **TC histórico consultado en el reporte** (`SUM(monto / tc_de_su_fecha)` con join a la tabla de TC) —
  **innecesario** gracias al congelamiento (§2.1). La agregación no se toca.
- **Item 9 (Reportes Excel)** — sigue diferido a v1.1. Este plan evita que revienten y corrige la conversión de
  las 7 rutas; no agrega ni rehace reportes.
- **B2 — fan-out de fuentes en reportes POA** — independiente de este plan.
- **Item 10 (Usuarios)** — el CRUD está completo (`index/crear/actualizar/eliminar` + rutas); solo falta QA.
