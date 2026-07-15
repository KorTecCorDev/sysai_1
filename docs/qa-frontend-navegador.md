# QA Frontend — Pruebas en navegador

> Plan de pruebas para validar la **estandarización visual** de esta sesión: tema Bootstrap,
> sidebar (perfil claro), rediseño de `dfinanciamiento/crear` y regresiones.
> Marca cada `[ ]` → `[x]` conforme pruebas.

## Preparación
- [ ] `local3000` (php -S localhost:3000) y MariaDB de XAMPP arriba → http://localhost:3000
- [ ] **Ctrl+F5** al abrir cada vista (para saltar caché de CSS/JS)
- [ ] Consola del navegador (F12) abierta para detectar errores JS
- [ ] Credenciales: admin `robertokar97@gmail.com` · contador `contador@sysai.test` / `admin1234` · coordinador `coordinador@sysai.test` / `Test1234*`

---

## A. Login y arranque
- [ ] La pantalla de login carga con el tema (tipografía nueva, sin fuentes cómicas)
- [ ] Login correcto con los 3 roles (admin, contador, coordinador)

## B. Sidebar — repetir en los 3 roles (admin, contador, coordinador)
- [ ] Fondo **claro** (panel gris), texto oscuro bien legible (nada en blanco camuflado)
- [ ] Se **distingue del contenido** (borde derecho nítido + leve sombra)
- [ ] El ítem de la **página actual** aparece como bloque **azul sólido** con barra lateral
- [ ] **Colapsado** → hover en ícono simple (Fuentes, Rendiciones, Ingresos-Egresos, Usuarios): **tooltip** con el nombre a la derecha
- [ ] **Colapsado** → hover en ícono con submenú (Programas, Contabilidad, Tipo de Cambio, Reportes): **flyout a la derecha** con cabecera de sección y subopciones completas
- [ ] Se ve completa la subopción **"Relación F. Financiamiento"** (flyout y expandido)
- [ ] **Expandido** (botón hamburguesa) → submenús tipo acordeón; etiquetas largas completas
- [ ] Al expandir/colapsar, el **contenido se corre** y el sidebar **no lo tapa**
- [ ] **"Cerrar sesión"** visible (rojo), con tooltip en colapsado y cierre de sesión OK
- [ ] Logo del header visible sobre el panel claro
- [ ] Navegación con **teclado (Tab)**: foco visible en los enlaces
- [ ] No hay comportamientos raros (submenús encimados, panel corto, texto cortado)

## C. Piloto — Programas (`/programa/admin`)
- [ ] Botón **"Agregar"** en azul de marca; botones editar (ámbar) y eliminar (rojo) tematizados
- [ ] Cabecera de tabla en mayúsculas gris; filas legibles con hover
- [ ] Crear / editar / eliminar un programa funciona; se ve la notificación

## D. Fuentes ↔ Programas (`/dfinanciamiento/crear`) — como **admin** y como **contador**
- [ ] **Sin programa** seleccionado: cards **atenuadas** + aviso "Selecciona un programa"
- [ ] El **select mantiene** el programa elegido tras recargar (no vuelve a "--Seleccione--")
- [ ] Textos **visibles**: nombre de la fuente y montos NO aparecen en blanco/camuflados
- [ ] **Grilla responsive**: al achicar la ventana, las cards se reacomodan (no quedan 6 diminutas)
- [ ] Card muestra: chip **"Disponible"/"Vinculada"**, desglose **Presupuesto / Comprometido / Disponible** (con `S/`) y **barra de uso**
- [ ] **Agregar**: el input de monto muestra el disponible; al crear el vínculo → chip **"Vinculada"**, muestra el **sobre asignado** y botón **"Quitar"**
- [ ] El select **sigue** mostrando el programa **después** de Agregar/Quitar
- [ ] **Exceder** el disponible al asignar → **error inline** (bloque de alerta, no modal)
- [ ] **Quitar** un vínculo → la card vuelve a **"Disponible"**
- [ ] Fuente con disponible **0** → input y botón **deshabilitados**
- [ ] Cambiar de programa en el select → recarga con las cards de ese programa

## E. Regresión (que la mina del `span` blanco quedó resuelta)
- [ ] `/saldos_contables/saldos`: etiquetas que antes estaban en blanco ahora se ven (p. ej. "Saldo disponible:"); KPIs y desglose legibles
- [ ] Otras vistas con **cards/tablas** (rubros, rendiciones, POA): textos visibles, nada en blanco
- [ ] **Badges de estado** (p. ej. `/poa/admin`: Borrador/Enviado/Observado/Aprobado) con texto blanco legible sobre su color

## F. General / accesibilidad
- [ ] Las notificaciones flash se **autoocultan** (~3 s); los banners persistentes (observaciones) permanecen
- [ ] **Sin errores** en la consola del navegador (F12) al navegar por las vistas anteriores
- [ ] Recorrido general por el menú de cada rol sin páginas rotas

---

### Notas / hallazgos durante las pruebas
> (anota aquí cualquier detalle a corregir)
-
