# Plan — El "comprometido" y el tope del POA por sobres

> **Estado:** definido y confirmado (2026-07-15). Cubre la **higiene del término "comprometido"** y la
> reapertura del **item 4** (tope del POA + puerta de sobres). **Sin migraciones**: ninguna fase de este plan
> toca el esquema.
> Complementa `docs/plan-montos-y-tipo-cambio.md` (migr. 026-030). El orden entre ambos está en **§4**.
> Las decisiones de §2 están **confirmadas por el usuario**. No volver a preguntar.

## 1. Por qué

### 1.1 "Comprometido" ha significado tres cosas, y sobreviven las tres

| # | Definición | Origen | Dónde sobrevive hoy | Estado |
|---|---|---|---|---|
| 1 | Σ **POAs aprobados** que usan la fuente | migr. 008 | `database/migrations/008_crear_fuente_presupuesto_anual.sql:3-4` | ☠️ muerta — `CLAUDE.md` dice que **nunca se calculó** |
| 2 | Σ **rendiciones + otros egresos**, por fuente | enmienda 2026-06-04 | 🔴 **`controllers/PoaController.php:183-184` (código vivo)** · `docs/historial-implementacion-items-2-6.md:57` | superada |
| 3 | Σ de los **sobres** (`monto_asignado`) | enmienda 2026-07-09 (migr. 020) | `CLAUDE.md:208,232` | ✅ **VIGENTE** |

**El cálculo está bien; el problema es la prosa.** Verificado: `SaldoFuenteFinanciamientoVista::desglosePorFuente()`
(`models/SaldoFuenteFinanciamientoVista.php:53-55`) hace `SUM(df.monto_asignado)` — la definición 3, la correcta.
Los consumidores también: `views/saldos_contables/saldos.php:128-132,192-197` y
`views/dfinanciamiento/crear.php:49-55,77`.

Lo que está mal es que **la definición 2 sigue escrita en un comentario de código vivo**, en
`PoaController::aprobar()`:

```php
// (El presupuesto_comprometido NO se calcula aquí: se acumula desde rendiciones +
//  otros egresos por fuente — ver items 5/6/8.)
```

La primera mitad es correcta. La segunda quedó obsoleta el 2026-07-09.

### 1.2 La colisión de niveles — el origen del enredo

La palabra está **sobrecargada en dos niveles, con significados opuestos**:

| Nivel | Dónde | `comprometido` significa | En una palabra |
|---|---|---|---|
| **Fuente** | `SaldoFuenteFinanciamientoVista:55,71` | Σ `monto_asignado` de sus sobres | **reservado** |
| **Sobre** | `DetalleFinanciamiento:44-45` (docblock) | Σ rendiciones + Σ egresos OIE del sobre | **gastado** |

Ambos conceptos son legítimos y necesarios. El problema es el nombre compartido: arriba significa *"cuánto de
esta fuente ya está repartido"*, abajo *"cuánto de este sobre ya se gastó"*.

> **Y esto explica la definición 2.** No fue un error suelto: es **el significado del sobre aplicado a la fuente**.
> La enmienda del 2026-07-09 movió el nivel de fuente de *gastado* a *reservado*, pero el nivel de sobre se quedó
> como estaba. Mientras el nombre siga duplicado, la confusión **se va a repetir**.

**Atenúa el daño:** en `saldoSobre()` la palabra vive **solo en el docblock**. El array que devuelve usa
`asignado / ingresos / egresos / rendiciones / disponible` — no hay una API que mienta.

### 1.3 La trampa armada para el item 8

`fuente_presupuesto_anual.presupuesto_comprometido` es una **columna real** (migr. 008) que **nada escribe**:
verificado que ningún modelo ni controlador toca esa tabla — solo `seed_demo.sql:258` y `seed_qa.sql:43`. Es el
cierre anual, item 8, pendiente.

El día que alguien lo implemente y busque la definición, **lo más cerca que tiene es el comentario de la
migración que creó la columna** — que enuncia la definición **1**. Implementaría "Σ POAs aprobados": dos
enmiendas atrás, y algo que nunca se calculó.

### 1.4 El POA no tiene tope (item 4)

Verificado en código:

| Punto | Qué hace hoy | Qué falta |
|---|---|---|
| `PoaController::enviar()` (`:129-134`) | `$doc->presupuesto = Poa::presupuestoCalculado()` y guarda | **no compara contra nada** |
| `PoaController::aprobar()` (`:213-218`) | cambia estado y llama a `Rendicion::aprobarPorPrograma()` | **no valida nada** |

**Hoy se puede enviar y aprobar un POA de S/ 5M con sobres que suman S/ 1M.**

Lo que **sí** funciona y no se toca: las rendiciones ya se acumulan contra el sobre y se bloquean al llegar al
límite (`Rendicion::validarLimiteSobre()` → `DetalleFinanciamiento::saldoSobre()`, que cuenta las de **todo
estado** para no sobre-comprometer).

### 1.5 Sin sobres, la puerta está abierta

Con **Σ sobres = 0** (programa sin fuentes vinculadas) el Coordinador puede registrar rubros, POA Presupuestal y
rendiciones contra un presupuesto que no existe. Nada lo impide.

---

## 2. Decisiones confirmadas (2026-07-15)

1. **`comprometido` a nivel de fuente = Σ sobres.** Vigente, correcto, **no cambia**. Coincide con lo que ya
   calcula el código y con lo que asume `plan-montos-y-tipo-cambio.md` §Fase 1.
2. **El nivel de sobre deja de llamarse "comprometido"** → pasa a **`ejecutado`**. Un solo significado por
   palabra. Es cambio de **comentarios y nombres internos**: sin BD, sin comportamiento.
3. **Tope del POA = AGREGADO:** `Σ rubro.monto ≤ Σ detalle_financiamiento.monto_asignado` del programa.
   **No por fuente**, porque **`rubro` no tiene `ff_id`** (verificado en esquema): un rubro no sabe de qué sobre
   sale. Un programa con sobres de 600k (fuente A) y 400k (fuente B) tiene tope 1M, repartible como sea.
   *Descartadas:* la variante por fuente (exigiría migración `rubro.ff_id`) y la de no poner tope.
4. **Se valida en `enviar()` y en `aprobar()`.** Revalidar al aprobar **no es redundante**: el sobre pudo bajar
   entre el envío y la aprobación.
5. **Σ sobres = 0 → puerta cerrada** para el Coordinador en lo **presupuestal** (rubros, POA Presupuestal,
   rendiciones). **NO alcanza** al POA Indicadores ni a la jerarquía Resultado→Producto→Actividad: no manejan
   dinero, y el POA Indicadores se elabora **antes** que el Presupuestal.
6. **Contador/Admin no pasan por la puerta** (adenda sobre POA aprobado, regla existente).

---

## 3. Fases

### Fase A — Higiene del "comprometido" *(~1 h · sin cambio de comportamiento)*

> Solo comentarios, docblocks y un nombre interno. **Ningún cálculo cambia.** Es seguro y desbloquea §4.

1. [ ] **Memoria del proyecto** (`~/.claude/.../memory/project_arcoiris.md:21`) — dice
       `comprometido (al aprobar POA)`: la definición **1**, superada dos veces. Corregir a Σ sobres.
       > ⚠️ Esa memoria tiene 43 días y **está obsoleta de punta a punta**, no solo en esta línea: la línea 23
       > dice `rendiciones ≤ monto del rubro` (revertido el 2026-07-09), la 24 y la 19 hablan del *POA Rendición*
       > como documento aparte (reencuadrado el 2026-06-05), y las líneas 26-34 listan como "brechas de BD"
       > cosas ya resueltas por las migraciones 001-025. **Reescribirla entera o borrarla**, no parchear la 21.
2. [ ] **`controllers/PoaController.php:183-184`** — el comentario enuncia la definición **2**. Reescribir:
       el comprometido no se calcula al aprobar porque **es Σ de los sobres** (`monto_asignado`), no una
       acumulación de rendiciones. Remitir a `CLAUDE.md` → *Fuentes*.
3. [ ] **`database/migrations/008_crear_fuente_presupuesto_anual.sql:3-4`** — enuncia la definición **1**.
       **No tocar el SQL** (ya se ejecutó; el runner registra 008 en `schema_migrations`). Añadir **solo un
       comentario** advirtiendo que la definición fue superada por la migr. 020 y remitiendo a `CLAUDE.md`.
       > Es la trampa de §1.3: quien implemente el item 8 leerá este archivo primero.
4. [ ] **`docs/historial-implementacion-items-2-6.md:57`** — es un documento **histórico**, así que registrar la
       decisión del 2026-06-04 es correcto *como historia*. Añadir solo la marca de que fue **superada el
       2026-07-09**, sin reescribir el registro.
5. [ ] **`models/DetalleFinanciamiento.php:44-45`** — renombrar el concepto del sobre:
       `comprometido` → **`ejecutado`** en el docblock. `disponible = capacidad − ejecutado`.
       El array devuelto **no cambia** (`asignado/ingresos/egresos/rendiciones/disponible`): no hay API que tocar.
6. [ ] **Verificación:** `grep -rn "comprometido"` sobre `models/ controllers/ views/ database/migrations/` →
       toda aparición restante debe ser **nivel fuente = Σ sobres**. Cualquier otra es un residuo.

### Fase B — Item 4: tope del POA + puerta de sobres *(~3-4 h · cambia comportamiento)*

7. [ ] **`Poa::topeSobres(int $programaId): float`** —
       `SELECT COALESCE(SUM(monto_asignado),0) FROM detalle_financiamiento WHERE programa_id = ?`.
       Leer el escalar con **mysqli directo**: `consultarPreparado()` pasa las filas por `crearObjeto()`, que
       descarta columnas fuera de `$columnasDB` (el alias de la agregación se perdería). Mismo patrón que
       `Poa::presupuestoCalculado()` (`models/Poa.php:81-103`), que ya lo documenta.
8. [ ] **`Poa::validarTopeSobres(int $programaId): bool`** — compara `presupuestoCalculado()` contra
       `topeSobres()`; agrega a `self::$errores`. **Devuelve el desglose** (Σ rubros, Σ sobres, margen) para poder
       construir el mensaje: el patrón de `saldoSobre()`, que ya devuelve el desglose "para construir mensajes claros".
9. [ ] **Dos mensajes distintos** — con Σ sobres = 0 el tope es 0 y **todo** lo excede, así que el mensaje
       genérico de tope mentiría por omisión:
       - Σ sobres **= 0** → *"Tu programa aún no tiene sobres asignados. El Contador debe asignar el presupuesto
         antes de que puedas registrar rubros, el POA o rendiciones."*
       - Σ sobres **> 0** y excedido → *"El POA (S/ X) supera la suma de tus sobres (S/ Y). Excede por S/ Z."*
       > Sin distinguirlos, el coordinador no sabe que debe **esperar al Contador** en vez de recortar su POA.
10. [ ] **`exigirSobreAsignado(int $programaId)`** en `includes/funciones.php`, al estilo del
        `exigirProgramaPropio()` existente. Son **8 rutas**; repetir el chequeo suelto en cada controlador
        garantiza que alguna quede fuera.
        **No aplica a Contador/Admin** (decisión 6) — la puerta es solo del Coordinador.
11. [ ] **Cerrar la puerta** en las rutas del Coordinador (verificadas en `icoordi.php`):
        `/rubro/crear|actualizar|eliminar` · `/poa/crear|enviar` · `/rendicion/crear|actualizar|eliminar`.
        **NO tocar:** `/resultado/*`, `/producto/*`, `/actividad/*`, `/poa_indicadores/*`.
12. [ ] **`PoaController::enviar()`** — bloquear si `Σ rubros > Σ sobres`. Hoy congela el presupuesto sin comparar.
13. [ ] **`PoaController::aprobar()`** — revalidar antes de aprobar (decisión 4). Hoy no valida nada.
14. [ ] **UI** — `Σ rubros` vs `Σ sobres` con el margen restante en `/poa/revisar` y en el listado de POA.
        Sidebar del coordinador: ocultar/deshabilitar el acceso presupuestal con Σ sobres = 0.
        Banner de "sin sobres" con clase **`.alert-persistente`** (los flash normales se auto-ocultan a los 3 s
        vía `src/js/app.js`) — y si se toca el JS, recompilar con `npx gulp js`.
15. [ ] **QA** — extender `database/qa_poa_presupuestal.ps1`:
        - enviar **bajo** tope → pasa
        - enviar **sobre** tope → bloquea
        - **bajar el sobre tras enviar** → aprobar debe bloquear *(el caso que justifica la decisión 4)*
        - **programa sin sobres** → rubro/POA/rendición bloqueados, **POA Indicadores permitido**
          *(el caso que prueba que la puerta no se pasó de alcance)*
        - **Contador** sobre programa sin sobres → **pasa** (adenda)

---

## 4. Orden respecto a `plan-montos-y-tipo-cambio.md`

### 4.1 El techo que hace a la Fase B inútil en producción

Verificado en la BD (migraciones al **025**):

| Columna | Tipo | Techo |
|---|---|---|
| `detalle_financiamiento.monto_asignado` | `decimal(14,2)` | ✅ ~1012 |
| `poa.presupuesto` | `decimal(14,2)` | ✅ |
| `rubro.monto` | `decimal(12,2)` | ✅ |
| **`fuente_financiamiento.presupuesto`** | **`decimal(8,2)`** | 🔴 **S/ 999,999.99** |

**La raíz del árbol es la única columna sin ensanchar.** Y `DetalleFinanciamiento::validarLimiteAsignacion()`
compara `Σ sobres ≤ fuente.presupuesto` → **ningún sobre real puede superar ~S/ 1M**, aunque su columna aguante
1012. Como el tope del POA **es** Σ sobres, hereda ese techo.

> **Consecuencia:** la Fase B es **correcta y testeable hoy** con cifras de laboratorio, pero **inútil con datos
> reales** (presupuestos de S/ 1M-10M) hasta que la **Fase 0 del plan de montos (migr. 026)** ensanche
> `presupuesto` a `decimal(14,2)`. La Fase B no *depende* de la 026 para funcionar; depende de ella para **servir**.

### 4.2 Orden recomendado

```
Fase A (este plan)  ──►  Fase 0+1 (plan de montos, migr. 026-027)  ──►  Fase B (este plan)
   higiene                  desbloquea los montos reales                tope + puerta
```

1. **Fase A primero, ya.** No toca comportamiento, cuesta ~1 h y **la Fase 1 del plan de montos reescribe
   `desglosePorFuente()` y `vista_saldo_fuente_financiamiento`** — justo la zona del "comprometido". Hacer la
   higiene antes significa que esa reescritura **hereda el vocabulario limpio** en vez de propagar el enredo.
2. **Fase 0+1 del plan de montos después.** Es el bloqueo real: hoy el sistema no puede almacenar ni una sola
   fuente real. La Fase 1 además introduce `capacidad_asignable`, contra la que `validarLimiteAsignacion()`
   pasará a comparar (§2.4 de ese plan) — **eso cambia el numerador del tope del POA**.
3. **Fase B al final.** Se construye sobre un `Σ sobres` que ya puede valer lo que vale de verdad.

> **Si se hace la Fase B antes que la 026:** funciona, el QA pasa, y queda una validación que en producción
> tope­aría todo POA a ~S/ 1M. No es incorrecto — es prematuro.

### 4.3 Vocabulario: este plan y el de montos no chocan

`plan-montos-y-tipo-cambio.md` §Fase 1 mantiene `comprometido (Σ sobres)` a nivel de fuente y añade
`presupuesto_inicial` / `presupuesto_vigente` / `capacidad_asignable`. **Coincide con la decisión 1 de este plan.**
La decisión 2 (renombrar el nivel de sobre a `ejecutado`) es **aditiva** respecto a ese plan: libera la palabra
para que signifique una sola cosa en todo el sistema.

---

## 5. Comportamiento del sistema una vez implementado

**Programa recién creado, sin fuentes vinculadas.** El Coordinador entra y **puede** elaborar su POA Indicadores:
resultados, productos, actividades, indicadores. Al intentar registrar un rubro o abrir el POA Presupuestal ve
*"Tu programa aún no tiene sobres asignados"* — sabe que debe esperar al Contador, no que se equivocó.

**El Contador asigna el primer sobre.** `/dfinanciamiento/crear`, fuente Compassion, `monto_asignado = S/ 600,000`.
La puerta se abre: el Coordinador ya registra rubros, POA y rendiciones.

**El Coordinador presupuesta de más.** Carga rubros por S/ 750,000 y envía. **Bloqueado:** *"El POA (S/ 750,000)
supera la suma de tus sobres (S/ 600,000). Excede por S/ 150,000."* Recorta a S/ 580,000 → envía.

**El Contador reduce el sobre entre el envío y la aprobación**, de 600k a 500k. Al aprobar, **se bloquea**: el POA
(580k) ya no cabe. *Este es el caso que justifica revalidar en `aprobar()`.*

**Las rendiciones siguen igual.** Ninguna puede pasar del saldo de su sobre — regla existente, no la toca este plan.

### Garantías
- **Ningún POA se aprueba por encima del dinero asignado al programa.**
- **Ningún coordinador presupuesta contra un programa sin financiamiento.**
- **"Comprometido" significa una sola cosa** en todo el sistema: Σ sobres, a nivel de fuente.
- **Ninguna fase de este plan toca la BD** ni el comportamiento de las rendiciones.

---

## 6. Lo que este plan NO resuelve

### 6.1 🔴 El techo de `fuente_financiamiento.presupuesto`
Es de `plan-montos-y-tipo-cambio.md` **Fase 0 (migr. 026)**. Sin eso, la Fase B es correcta pero prematura (§4.1).

### 6.2 🟡 El tope por fuente
`rubro` no tiene `ff_id`, así que el tope es **agregado**. Si más adelante se necesita que "los rubros de la
fuente A quepan en el sobre A", hace falta migración + cambio del formulario de rubro + decidir qué pasa con los
rubros existentes. **Descartado hoy por decisión explícita** (§2.3).

### 6.3 🟡 El item 8 (cierre anual) sigue pendiente
La Fase A **desarma la trampa** (§1.3) dejando la definición correcta al alcance, pero **no implementa** el
cálculo de `fuente_presupuesto_anual.presupuesto_comprometido`. Cuando se haga: es **Σ sobres**, no Σ POAs
aprobados ni Σ rendiciones.

### 6.4 🟢 `RendicionFuentesCantidadVista`
Consulta `cantidad_fuentes_rendicion`, vista eliminada en la migr. 009. Follow-up abierto, ajeno a este plan.
