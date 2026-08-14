# Plan de corrección — Reportes de Ingresos y de Rendiciones

> **Estado:** 📋 PLANIFICADO (2026-08-14). Ninguna fase ejecutada todavía.
> **Alcance:** `/reporte/ingresos` y `/reporte/rendiciones`, más la **Fase 0**, que toca
> los cinco reportes Excel porque comparten el mecanismo de descarga.
> **Origen:** auditoría del 2026-08-14. Todos los hallazgos están **verificados
> empíricamente** contra la BD local (escenario mínimo + volcado celda a celda del
> xlsx real, restaurado después con `database/respaldo.ps1`).

---

## 1. Qué hacen hoy estos dos reportes

Ambos comparten mecánica: formulario de dos fechas → POST → redirección a
`/reporte/{ingresos,rendiciones}desc?fechainicio=…&fechafin=…` → el servidor genera un
`.xlsx`, **lo guarda en disco** en `views/reporte/storage/reports/` y devuelve una página
cuyo único contenido es un enlace a `/descargar?rprt=<archivo>`.

| | `/reporte/ingresos` | `/reporte/rendiciones` |
|---|---|---|
| Título interno del xlsx | `REPORTE INGRESOS` | `REPORTE EGRESOS` *(la pantalla se llama "rendiciones")* |
| Bloque 1 | OIE tipo Ingreso (`reporte_ingresos`) | OIE tipo Egreso (`reporte_egresos`) |
| Bloque 2 | Todas las fuentes (`reporte_fuentes`) | Rendiciones (`reporte_rendiciones`) |
| Filtro bloque 1 | `oie_comprobante_fecha_original` | `otros_ingresos_egresos_fecha` |
| Filtro bloque 2 | `fuente_fecha` | `rendicion_fecha` |
| Archivo | `resultados_reporte_ingresos_<usuario>.xlsx` | `resultados_reporte_poa_rendiciones_general_<usuario>.xlsx` |

Los dos bloques se apilan en **una sola hoja plana** bajo un único juego de 14
encabezados. La intención —"todo lo que entra" / "todo lo que sale"— es correcta; la
ejecución no.

---

## 2. Diagnóstico

### 2.1 🔴 Crítico — movimientos que desaparecen del reporte

`reporte_ingresos` y `reporte_egresos` incluyen:

```sql
JOIN detalle_financiamiento d ON d.fuente_financiamiento_id = o.ff_id
JOIN programa g              ON g.id = d.programa_id
```

Es un **INNER JOIN emparejado solo por fuente**, sin relación con el `programa_id` del
propio movimiento. Si la fuente no tiene ningún sobre asignado, no hay filas que emparejar
y **el movimiento se cae del reporte, en silencio**.

Medido con dos ingresos reales:

```
suma real en la tabla:  S/ 35,000.00
suma en el reporte:     S/ 10,000.00     ← falta el 71 %
```

Es exactamente el caso del **ingreso híbrido** que las reglas de negocio contemplan de
forma explícita (`programa_id` NULL, dinero al remanente de la fuente) y el estado más
probable al inicio del ejercicio, antes de repartir sobres.

Como agravante, ese mismo JOIN **multiplica** filas: una fuente con 2 sobres produce 2
copias de cada movimiento. Hoy no se nota porque el `SELECT DISTINCT` las colapsa — es
decir, **el DISTINCT está tapando el bug, no optimizando**. El día que alguien añada al
SELECT cualquier columna de `d` o `g` (por ejemplo el programa, que es justo lo que pide
este plan), los montos empiezan a duplicarse.

Ni `detalle_financiamiento`, ni `programa`, ni `oie_tipo` aportan una sola columna al
SELECT: los tres joins sobran.

### 2.2 🔴 Crítico — `/descargar?rprt=` sirve cualquier archivo del servidor

`ReportePoaRubrosController::indexdescarga()` (línea 226) concatena `$_GET['rprt']` a la
ruta sin sanear nada. Verificado en disco:

```
rprt=../../../../.env                         exists=SI   1863 bytes
rprt=../../../../secrets/.env.enc             exists=SI   2600 bytes
rprt=../../../../includes/config/database.php exists=SI   5947 bytes
rprt=../../../../.git/config                  exists=SI    420 bytes
```

El `.env` contiene la **App Password de Gmail** y las credenciales de BD. La ruta está
registrada en los **tres roles** (`iadmin.php:118`, `iconta.php:114`, `icoordi.php:97`),
así que **cualquier coordinador** puede hacerlo. Y esquiva el bloqueo del `.htaccess`
sobre `.env`, porque es PHP quien lee el archivo, no Apache. La rama `else` además imprime
la ruta absoluta del servidor.

### 2.3 🔴 Crítico — los xlsx generados se descargan sin sesión

El `.htaccess` bloquea `views/**.php` pero no los `.xlsx`. Probado contra el Apache del
8080:

```
GET /views/reporte/storage/reports/resultados_reporte_ingresos_contador.xlsx
→ HTTP 200, 6896 bytes, sin autenticar
```

Los nombres son predecibles (`<tipo>_<parte-local-del-email>.xlsx`) y los archivos **se
acumulan sin limpieza**: hay 9 ahora mismo, de sesiones anteriores, con los datos del
escenario demo.

### 2.4 🟠 Alto — el reporte de ingresos suma el presupuesto de las fuentes

`insertarDatosDesdeArray()` escribe `fuente_monto` en la columna **L = MONTO**. Volcado
real de la hoja:

```
fila 3  A:2026-08-14 16:21  B:OIE001  C:INGRESO…           D:FF001  …  L:10000
fila 4  A:2026-08-14        B:FF001   C:FUENTE CON SOBRES  …           L:500000
fila 5  A:2026-08-14        B:FF002   C:FUENTE SIN SOBRES  …           L:300000
```

Sumar la columna L da **810 000** cuando el ingreso del periodo fue **10 000**. Nada marca
esas filas como distintas. Y `fuente.presupuesto` es el monto inicial, que por regla de
negocio explícita **no es un ingreso**. La fila de FF002 muestra su presupuesto de 300 000
mientras su ingreso real de 25 000 fue descartado por §2.1.

### 2.5 🟠 Alto — rendiciones pendientes mezcladas con aprobadas

`reporte_rendiciones` no filtra por `estado` ni lo expone:

```
fila 4  REN001  APROBADA   L:4000     ← estado=1
fila 5  REN002  PENDIENTE  L:7000     ← estado=0, indistinguible
```

Una pendiente no descuenta el `presupuesto_contable` y aún puede ser observada o
modificada. Además genera una **contradicción interna**: `reporte_poa_rendicion`
(migr. 034) cuenta solo aprobadas del ejercicio vigente; este cuenta todo. Dos reportes
Excel de la misma aplicación dan cifras distintas para el mismo periodo.

### 2.6 🟠 Alto — las columnas USD/EUR nunca se llenan

Las dos vistas declaran 14 encabezados, incluidos `MONTO USD (TC congelado)` y
`MONTO EUR (TC congelado)`. Los dos helpers construyen un array de **12** elementos y
escriben 12 celdas: **M y N salen siempre vacías** (confirmado en el volcado). Toda la
cadena de la migración 030 —TC congelado por fila, `ROUND(monto/NULLIF(tc,0),2)` en las
vistas, propiedades del modelo con sus comentarios— se calcula y se tira en el último
paso. Los helpers fijan además `$endColumn = 'L'`, así que el autoajuste tampoco las
alcanza.

### 2.7 🟠 Alto — cada bloque se filtra por una fecha con distinto significado

| Bloque | Columna del filtro | Qué significa |
|---|---|---|
| Ingresos | `oie_comprobante_fecha_original` | fecha de operación ✔ |
| Egresos | `otros_ingresos_egresos_fecha` | fecha de **registro** ✘ |
| Rendiciones | `rendicion_fecha` | fecha de **registro** ✘ |
| Fuentes | `fuente_fecha` | fecha de alta de la fuente ✘ |

El mismo movimiento cae en periodos distintos según el reporte, y un comprobante de marzo
registrado en agosto aparece en el reporte de agosto. Contradice el diseño del TC
congelado, que ancla todo a la fecha de operación.

### 2.8 🟡 Medios y bajos

- **`TIPO_COMPROBANTE` del reporte de ingresos siempre vacío:** el modelo declara
  `oie_tipo_comprobante_codigo` y el helper lo lee, pero la vista SQL nunca lo selecciona
  (`reporte_egresos` sí). `crearObjeto()` solo asigna claves presentes → queda NULL.
- **La columna FECHA muestra la fecha de registro**, no la de operación: `2026-08-14
  16:21:36` para un comprobante del `2026-03-10`.
- **`reporte_ingresos` no hace `CAST(fecha AS date)` y `reporte_egresos` sí** — de ahí el
  datetime con segundos en uno y la fecha limpia en el otro.
- **Propiedad mal escrita en `ReporteEgresosVista`:** `$columnasDB` dice
  `otros_ingresos_egresos_oie_tipo_id`, la propiedad es `$otros_ingresos_oie_tipo_id`
  (falta `egresos_`). El valor se descarta en silencio.
- **No hay fila de TOTAL, ni subtotales, ni columna de programa.**
- **`reporte_rendiciones` arrastra residuos:** expone `rendicion.monto` dos veces, tiene un
  `GROUP BY r.id` que no agrupa nada, y hace INNER JOIN a `producto`/`resultado`/`programa`
  sin seleccionar una sola columna de ellos — joins que no aportan pero sí pueden
  **descartar filas**.
- **Todo el aparato de `$claves_a_excluir` y el `array_slice` de reordenamiento es código
  muerto:** los helpers leen las propiedades **por nombre**. El `array_slice` es además un
  no-op (`fuente_financiamiento_codigo` ya está en la posición 4).
- **`$_POST['fechainicio']` se usa sin comprobar** y no se valida inicio ≤ fin.
- **`$valores_ordenados` no se inicializa** en el bucle de
  `insertarDatosDesdeArrayEgresosRendiciones()`: un objeto que no caiga en ninguna rama
  reutiliza los valores de la fila anterior y duplica una fila en silencio.

### 2.9 Lectura de conjunto

Estas dos pantallas son **anteriores al trabajo de calidad del item 9** y no lo recibieron.
`ReporteRendicionXlsxBuilder` resolvió para los reportes de POA exactamente estos problemas
—columnas calculadas, totales etiquetados, grano correcto, QA celda a celda— mientras
`ingresosdesc.php` y `rendicionesdesc.php` siguen con el patrón viejo: lógica de
presentación dentro de la vista, helpers genéricos en `ActiveRecord` y cero pruebas.

**Hoy no es prudente fiarse de ninguna de las dos cifras totales.** Para uso interno de
exploración sirven; para entregar a un donante, no.

---

## 3. Decisiones tomadas (2026-08-14) — no volver a preguntar

| # | Decisión | Elegido |
|---|---|---|
| D1 | Bloque de fuentes en el reporte de ingresos | **Sección aparte y etiquetada**, con encabezado y total propios |
| D2 | Qué fuentes lista esa sección | **Todas, siempre** — es contexto; el rango filtra los ingresos, no el contexto |
| D3 | Rendiciones pendientes | **Solo aprobadas** (coherente con `reporte_poa_rendicion`) |
| D4 | Criterio de fecha | **Fecha de operación (`fecha_original`) en todo**, filtro y columna |
| D5 | Profundidad | **Builder dedicado + arnés QA**, patrón del item 9 |
| D6 | Seguridad (§2.2 y §2.3) | **Ya, en un commit aparte**, antes de la capacitación |
| D7 | Entrega del archivo | **Streaming directo**, sin guardar en disco |
| D8 | Alcance del streaming | **Los cinco reportes**; se retiran `/descargar` y `storage/reports/` |
| D9 | Estructura del reporte de rendiciones | **Secciones separadas y etiquetadas** (rendiciones / otros egresos) |
| D10 | Columnas nuevas | **PROGRAMA**, **fila de TOTAL por sección**, **subtotales por fuente**, **columna ESTADO** |
| D11 | Vistas SQL | **Migración 035** que las redefine (patrón de las migr. 030/031/034) |
| D12 | Ejecución | **Solo el plan**; nada se toca hasta la aprobación |

---

## 4. Fases

### Fase 0 — Seguridad ✅ HECHA (2026-08-14)

> **Ejecutada y verificada end-to-end contra el Apache del 8080.** Decisión adicional
> tomada al arrancar: los tres reportes de POA **descargan directamente desde el sidebar**,
> sin página intermedia (se retiró la pantalla cuyo único contenido era el botón).
>
> | Verificación | Resultado |
> |---|---|
> | Los cinco reportes | HTTP 200, `Content-Type` de xlsx, `filename` correcto, magic `PK` |
> | El xlsx se relee con PhpSpreadsheet | ✔ `qa_ing.xlsx` → `A1 = "REPORTE INGRESOS"`, dim `A1:N3` |
> | `/descargar?rprt=../../../../.env` | → `/error`, 0 bytes, sin fuga |
> | `/views/reporte/storage/reports/*.xlsx` | → `/error` (la carpeta ya no existe) |
>
> Probado con un usuario contador desechable, creado y eliminado con
> `database/respaldo.ps1`; la contraseña del admin no se tocó y la base de la
> capacitación quedó en su estado limpio.



Cierra §2.2 y §2.3. No depende de ninguna otra fase y va en su propio commit para que
pueda revisarse y mergearse por separado.

1. **Helper de descarga** en `includes/funciones.php`:
   `descargarXlsx(Spreadsheet $ss, string $nombreArchivo): never` — limpia el búfer de
   salida, fija `Content-Type`/`Content-Disposition`/`Content-Length`, escribe con
   `Xlsx::save('php://output')` y termina. El nombre lo compone el servidor; **nunca** llega
   del cliente.
2. **Los cinco reportes pasan a streaming.** Cada vista de reporte deja de hacer
   `mkdir` + `$writer->save(...)` + `echo '<a href=…>'` y pasa a llamar al helper. Las
   cinco: `poa.php`, `poarendicion.php`, `poarubros.php`, `ingresosdesc.php`,
   `rendicionesdesc.php`.
3. **Se retira `indexdescarga()`** y las tres rutas `GET /descargar`
   (`iadmin.php:118`, `iconta.php:114`, `icoordi.php:97`).
4. **Se elimina `views/reporte/storage/`** y se añade al `.gitignore` por si alguna rama
   vieja la recrea. Los 9 archivos acumulados con datos del escenario demo se borran.
5. **Verificación:** `GET /views/reporte/storage/reports/<cualquiera>.xlsx` → 404;
   `GET /descargar?rprt=../../../../.env` → 404 (ruta inexistente); los cinco reportes
   siguen descargando su xlsx correcto.

> ⚠️ **Cambio visible para el usuario, flagged para tu revisión:** los tres reportes de POA
> hoy muestran una página cuyo único contenido es el botón "Ver POA / Ver Rendiciones".
> Con streaming, entrar a `/reporte/poa` **descarga el archivo directamente**, sin página
> intermedia. Es un clic menos, pero es un cambio de comportamiento: si prefieres conservar
> la página con su botón, se mantiene y el botón apunta a una ruta nueva `…desc` que
> streamea. Dímelo y ajusto la fase.

### Fase 1 — Migración 035: las tres vistas SQL ✅ HECHA (2026-08-14)

> `database/migrations/035_reportes_movimientos_saneados.sql`.
>
> | Verificación | Resultado |
> |---|---|
> | El ingreso que desaparecía | suma real **35 000** = suma del reporte **35 000** (antes 10 000) |
> | Fuente con 2 sobres | **una sola fila** por movimiento, sin `DISTINCT` |
> | Ingreso híbrido (`programa_id` NULL) | aparece, con PROGRAMA vacío — el dato, no la fila perdida |
> | `TIPO_COMPROBANTE` en ingresos | ahora con valor (`OTC00001`); antes siempre vacío |
> | Rendiciones | exponen `estado`, `rubro` y `programa`; `tipo_comprobante_descripcion` = "Factura" |
> | Replay greenfield | baseline + **35 migraciones** en una BD nueva, sin errores |
> | Vistas en `sysai` vs. replay | definiciones **idénticas** |
> | Código actual sobre las vistas nuevas | sigue generando el xlsx; **no rompe nada** |
>
> **Se pudo commitear sola**, contra lo que preveía §7: al añadir las columnas al final
> y dejar en su sitio las que se consumen, `crearObjeto()` ignora las nuevas y el único
> campo retirado (`rendicion_monto`, duplicado) no lo usaba nadie. La Fase 1 entra como
> mejora estricta: los movimientos perdidos vuelven al reporte y el tipo de comprobante
> se llena, aunque el resto de defectos siga hasta las Fases 2-3.



`database/migrations/035_*.sql` redefine `reporte_ingresos`, `reporte_egresos` y
`reporte_rendiciones` con `CREATE OR REPLACE VIEW`.

**Cambios comunes a `reporte_ingresos` / `reporte_egresos`:**

- Se **eliminan** los joins a `detalle_financiamiento`, `programa g` (el del sobre) y
  `oie_tipo`. Cierra §2.1.
- `JOIN fuente_financiamiento f ON f.id = o.ff_id` **directo**.
- `JOIN oie_comprobante c ON c.id = o.oie_comprobante_id` — INNER, porque la FK es NOT NULL
  (hoy es un `LEFT JOIN` seguido de un INNER a `oie_tipo_comprobante`, que lo anula).
- `LEFT JOIN programa g ON g.id = o.programa_id` — el programa **del movimiento**. NULL =
  ingreso al remanente de la fuente. Cierra la columna PROGRAMA de D10.
- `LEFT JOIN oie_tipo_comprobante p ON p.id = c.oie_tipo_comprobante_id` en **ambas**
  vistas. Cierra §2.8 (el `TIPO_COMPROBANTE` vacío de ingresos).
- Se **retira el `SELECT DISTINCT`**: sin el fan-out ya no hace falta, y dejarlo enmascararía
  futuros errores de join.
- Ambas exponen **dos fechas con nombre inequívoco**:
  `otros_ingresos_egresos_fecha_registro` (`CAST(o.fecha AS date)`) y
  `oie_comprobante_fecha_original`. El filtro y la columna FECHA usan la segunda (D4).

**`reporte_rendiciones`:**

- Se **retira el `GROUP BY r.id`** (no hay agregación) y el duplicado `rendicion_monto`
  (se conserva `rendicion_comprobante_monto`).
- Se **conserva** la cadena `rubro → actividad → producto → resultado → programa`, ahora
  con propósito: alimenta la columna PROGRAMA.
- Se añade `r.estado AS rendicion_estado` (D10) y `r.rubro_id`/`ru.codigo` para trazabilidad.
- Se renombra la fecha igual que arriba: `rendicion_fecha_registro` y
  `rendicion_fecha_original`.
- **La vista NO filtra por estado.** El filtro "solo aprobadas" (D3) vive en el builder, para
  que la misma vista pueda alimentar un futuro reporte de pendientes sin duplicar SQL. Queda
  anotado aquí porque es una decisión de implementación, no una omisión.

`reporte_fuentes` **no se toca**: es una proyección limpia de `fuente_financiamiento` y con
D2 se consume entera, sin filtro.

### Fase 2 — Modelos y builder

1. **Modelos actualizados** a las columnas nuevas: `ReporteIngresosVista`,
   `ReporteEgresosVista` (corrigiendo de paso la propiedad mal escrita de §2.8) y
   `ReporteRendicionesVista`.
2. **`models/ReporteMovimientosXlsxBuilder.php`** — nuevo, mismo patrón que
   `ReporteRendicionXlsxBuilder` (constructor con los TC de cierre + título;
   `construir(Spreadsheet, array $secciones)`). Responsabilidades:
   - Dibujar **secciones etiquetadas** (D1, D9), cada una con su encabezado, sus filas, sus
     **subtotales por fuente** y su **fila de TOTAL** (D10).
   - Columnas **calculadas** con `Coordinate::stringFromColumnIndex`, sin letras
     hardcodeadas — la lección de la migr. 034.
   - Rellenar **USD y EUR** con el TC congelado de cada fila (cierra §2.6), mostrando `—`
     cuando el valor es NULL, que es la convención ya documentada para "pendiente de TC".
   - La sección de fuentes usa **TC de cierre** (`tcCierre()`), no congelado: un presupuesto
     no es una transacción y no tiene fecha de operación. Se indica la tasa y su fecha en el
     encabezado de la sección, como ya hace la pantalla de saldos.
3. **Se retiran de `ActiveRecord`** los helpers `insertarDatosDesdeArray()` e
   `insertarDatosDesdeArrayEgresosRendiciones()` (~160 líneas) una vez sin usuarios. Con
   ellos se va el bug de `$valores_ordenados` sin inicializar de §2.8.

### Fase 3 — Controlador, vistas y rutas

1. `indexreporteingresosdescargar()` / `indexreporterendicionesdescargar()`: **validar**
   que las fechas llegan, tienen formato `Y-m-d` y que inicio ≤ fin; si no, volver al
   formulario con un aviso en vez de generar una hoja vacía sin explicación (§2.8).
2. Filtrar por la fecha de operación en los tres bloques (D4).
3. Aplicar el filtro **solo aprobadas** a las rendiciones (D3).
4. `ingresosdesc.php` y `rendicionesdesc.php` quedan como **orquestadores delgados**: arman
   las secciones y llaman al builder — igual que `poarubros.php` tras el item 9. Desaparece
   todo el aparato de `$claves_a_excluir` y `array_slice` (§2.8).
5. Corregir el título interno del reporte de rendiciones, que hoy dice `REPORTE EGRESOS`.

### Fase 4 — QA

Se **extiende `database/qa_reportes.ps1`** (no se crea un arnés nuevo: ya existe y ya sabe
leer xlsx con `qa_leer_xlsx.php`). Asserts celda a celda sobre un fixture que incluya
deliberadamente los casos que hoy fallan:

| # | Caso | Qué asserta |
|---|---|---|
| 1 | Ingreso sobre fuente **sin sobres** | Aparece en el reporte (hoy desaparece) |
| 2 | Ingreso sobre fuente con **2 sobres** | Aparece **una sola vez** |
| 3 | Ingreso con `programa_id` NULL | Columna PROGRAMA vacía, no la fila entera perdida |
| 4 | Suma de la sección de ingresos | Igual a la suma real de la tabla |
| 5 | Sección de fuentes | En bloque propio, con su total, sin contaminar el total de ingresos |
| 6 | Rendición **pendiente** | **No** aparece; la aprobada sí |
| 7 | Fila con TC congelado | Columnas USD y EUR con el valor correcto |
| 8 | Fila **sin** cobertura de TC | USD/EUR muestran `—`, no vacío ni 0 |
| 9 | Comprobante de marzo registrado en agosto | Cae en el reporte de **marzo** |
| 10 | Subtotales por fuente | Suman las filas de su fuente y solo esas |
| 11 | `TIPO_COMPROBANTE` en ingresos | Con valor (hoy siempre vacío) |
| 12 | Descarga | Streaming con el `Content-Type` correcto; `/descargar` ya no existe |

Al cerrar: `database/qa_all.ps1` completo (hoy 154/154) más los asserts nuevos.

---

## 5. Diseño de las hojas resultantes

**`/reporte/ingresos` — "REPORTE DE INGRESOS \<rango\>"**

```
SECCIÓN 1 · INGRESOS DEL PERIODO          (filtrado por fecha de operación)
  FECHA · CÓDIGO · DESCRIPCIÓN · PROGRAMA · FUENTE · TIPO COMPROBANTE ·
  FECHA COMPROBANTE · RUC · RAZÓN SOCIAL · SERIE · NÚMERO · DETALLE ·
  MONTO S/ · MONTO USD · MONTO EUR
    → subtotal por fuente
    → TOTAL INGRESOS DEL PERIODO

SECCIÓN 2 · PRESUPUESTO DE LAS FUENTES    (no depende del rango — contexto)
  FUENTE · NOMBRE · PRESUPUESTO S/ · USD · EUR   (TC de cierre, con su fecha)
    → TOTAL PRESUPUESTADO
```

**`/reporte/rendiciones` — "REPORTE DE EGRESOS \<rango\>"**

```
SECCIÓN 1 · RENDICIONES APROBADAS         (filtrado por fecha de operación)
  … mismas columnas + ESTADO
    → subtotal por fuente
    → TOTAL RENDICIONES APROBADAS

SECCIÓN 2 · OTROS EGRESOS (OIE)
  … mismas columnas
    → subtotal por fuente
    → TOTAL OTROS EGRESOS

TOTAL GENERAL DE EGRESOS
```

---

## 6. Lo que este plan **no** hace

- **No toca los tres reportes de POA** más allá de la Fase 0 (streaming). Su contenido lo
  reescribió el item 9 y está cubierto por QA.
- **No añade un reporte de rendiciones pendientes.** D3 lo dejó fuera; la Fase 1 deja la
  vista preparada para que sea barato después.
- **No introduce el concepto de periodo contable** distinto del año calendario — sigue
  abierto en `docs/plan-montos-y-tipo-cambio.md` §5.1, pendiente de consulta a la contadora.
- **No cambia quién ve qué:** ambos reportes siguen siendo de Admin y Contador; el
  coordinador no los tiene registrados.

---

## 7. Riesgos

| Riesgo | Mitigación |
|---|---|
| La migr. 035 cambia nombres de columna → los modelos dejan de mapear | Fases 1 y 2 van en el **mismo commit**; `crearObjeto()` descarta en silencio, así que un desajuste no revienta: **deja columnas vacías**. Por eso los asserts de la Fase 4 son celda a celda y no solo "hay N filas". |
| Retirar los helpers de `ActiveRecord` rompe a otro consumidor | Barrido de referencias antes de borrar. Verificado hoy: solo los usan estas dos vistas. |
| Streaming rompe la descarga si algo emite salida antes | El helper limpia el búfer y los orquestadores no imprimen nada; se verifica con los cinco reportes. |
| Cifras nuevas ≠ cifras viejas y alguien lo lee como regresión | Es el objetivo: las viejas estaban mal. Conviene anunciarlo — sobre todo la desaparición de las pendientes y del presupuesto de las fuentes de la columna MONTO. |

---

## 8. Orden sugerido

1. **Fase 0** — commit propio, mergeable ya. *(Aprobada en D6; pendiente el matiz de la
   página intermedia de los reportes de POA, §4 Fase 0.)*
2. Fases 1 + 2 + 3 — un commit por fase, en la misma rama.
3. Fase 4 — QA, y `qa_all.ps1` en verde antes de proponer el merge.
