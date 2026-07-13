# QA — Autogeneración de códigos (F0–F3)

> Pruebas en navegador para validar que los códigos se **autogeneran** (nadie los teclea) y que
> el **código de usuario fue eliminado**. Marca `[ ]` → `[x]` conforme pruebas.

## Preparación
- [ ] `local3000` + MariaDB arriba; migraciones **001–024** aplicadas (`php database/migrate.php --status`)
- [ ] **Ctrl+F5** en cada vista; consola (F12) sin errores
- [ ] Nota: el demo local tiene códigos viejos (`RES001`, `FF00x`). Para ver la cadena jerárquica **limpia** (`1 / 1.1 / …`), **crea un programa NUEVO** y arma su árbol desde cero.

## A. Maestros correlativos (código read-only, autogenerado)
- [ ] **Programa** `/programa/crear`: el campo Código está **deshabilitado** ("Se asignará automáticamente"); al guardar aparece **PRG###**
- [ ] **Fuente** `/fuente_financiamiento/crear`: código **FF###** automático
- [ ] **Categoría de rubro** `/categoria_rubro/crear`: código **CAT###** automático
- [ ] **Rendición** (crear una en un rubro): código **REN###** automático
- [ ] En todos: el usuario **no puede** escribir el código; se ve tras guardar

## B. Árbol POA jerárquico (sobre un programa NUEVO)
- [ ] **Resultado** → primer código **1**; crear otro → **2**
- [ ] **Producto** bajo el resultado 1 → **1.1**; otro → **1.2**
- [ ] **Actividad** bajo 1.1 → **1.1.1**; otra → **1.1.2**
- [ ] **Rubro** bajo 1.1.1 → **1.1.1.01**; otro → **1.1.1.02**

## C. Estable con huecos
- [ ] Borra un elemento intermedio (p. ej. Producto **1.1**) y crea otro → **no reutiliza** el hueco (sigue con MAX+1, p. ej. 1.3)

## D. No regenera al editar
- [ ] Edita el **nombre** de un programa/resultado/rubro → su **código NO cambia**

## E. Usuario sin código
- [ ] `/usuario/crear`: **no existe** el campo "Usuario"/código; solo Datos personales + Email + Cargo
- [ ] `/usuario/admin`: la tabla **no tiene** columna "Usuario"; identifica por **Datos (nombre) + Email**
- [ ] Crear un usuario nuevo y **loguear con su email** → funciona (el login siempre fue por email)

## F. No regresión / auditoría
- [ ] Crear / editar / eliminar sigue funcionando en todos los módulos anteriores
- [ ] **Tipo de cambio** dólar/euro (admin): la columna de "usuario" que registró muestra ahora el **email** (antes el código) — sin errores
- [ ] **Reportes Excel** (POA, rendiciones): se generan; los códigos jerárquicos aparecen. Sobre datos NUEVOS salen limpios (`1.1.1`); sobre el demo viejo pueden verse híbridos (`RES001.1`) — **esperado** hasta regenerar el seed (F5)

## Pendientes conocidos (no son bugs)
- **OIE** (`/ingreso_egreso`) todavía pide código a mano → se hará junto con **item 7** (rework del módulo).
- ✅ **Seed greenfield — RESUELTO (F5):** `seed.sql`/`seed_demo.sql` ya NO insertan `usuario.descripcion`;
  el árbol POA del demo usa códigos jerárquicos (`1 / 1.1 / 1.1.1 / 1.1.1.01`) y `categoria_rubro` usa `CAT###`.
  Verificado con import limpio en BD scratch (baseline + migr. 001-024 + seed + seed_demo, sin errores).
  Arnés `qa_rendicion.ps1` actualizado (ya no envía `codigo`; identifica por serie+número; su sección 5
  pasó de "límite por rubro" a "límite por sobre"). Fixture de QA portátil (`database/seed_qa.sql`, aplicado
  por `qa_all.ps1`). **Suite completa VERDE: 49/49** (18+21+10) confirmada contra `localhost:3000` (2026-07-13).

---
### Hallazgos durante las pruebas
> (anota aquí lo que haya que corregir)
-
