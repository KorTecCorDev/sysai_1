# Base de datos — Migraciones SysAI

Control de versiones del esquema. El framework no tiene runner propio, así que
las migraciones son archivos `.sql` numerados que se aplican **en orden**.

## Estructura

- `schema_baseline.sql` — snapshot del esquema previo a los Grupos 8-13 (solo
  estructura, **sin datos**). Punto de partida para un despliegue desde cero
  (importar primero, luego correr migraciones).
- `migrations/NNN_descripcion.sql` — cada cambio incremental, numerado y ordenado.
- `migrate.php` — runner que aplica las migraciones pendientes y las registra en
  la tabla `schema_migrations`.
- `seed.sql` — datos de catálogo (cargo, tipos, categorías de rubro) + usuario
  administrador inicial. Idempotente (`INSERT IGNORE`). **No** contiene datos
  transaccionales. Se aplica **después** de las migraciones.
- `seed_demo.sql` — escenario de **demostración visual** realista (ONG Arco Iris:
  programas, fuentes con sobres, POAs, rendiciones, OIE). Re-ejecutable (borra-y-
  reinserta, preserva el admin id=1). Para probar a mano en el navegador.
- `seed_qa.sql` — **fixture determinista de la suite de QA** (`qa_*.ps1`). Reproduce
  el contrato exacto que asumen los arneses (usuarios `*.test`, programa 1 con
  jerarquía/rubros=28000/sobres, programa 5 cross-tenant). Re-ejecutable, preserva
  el admin id=1. Es **mutuamente excluyente** con `seed_demo.sql` sobre la misma BD.

> `seed_demo.sql` y `seed_qa.sql` son **modos** de la BD de desarrollo: instalar uno
> reemplaza los datos del otro. Elegir según la tarea (demo visual vs QA automatizada).

## Aplicar migraciones

### Opción A — runner (recomendado)

```bash
php database/migrate.php            # aplica pendientes
php database/migrate.php --status   # lista aplicadas / pendientes
```

### Opción B — manual (phpMyAdmin / Hostinger)

Ejecutar el contenido de cada archivo `migrations/NNN_*.sql` **en orden numérico**.
Cada archivo registra su propia versión en `schema_migrations` (`INSERT IGNORE`),
así que es seguro combinarlo con el runner.

## Despliegue desde cero (Hostinger)

1. Crear la BD vacía y configurar `.env`.
2. Importar `schema_baseline.sql`.
3. Ejecutar `php database/migrate.php` (o aplicar `migrations/*.sql` en orden).
4. Importar `seed.sql` (catálogos + admin inicial).
5. Iniciar sesión como `admin@arcoiris.pe` / `Arcoiris2026*` y **cambiar la
   contraseña de inmediato** (credenciales temporales del seed).

> Probado end-to-end en una BD limpia: `schema_baseline.sql` + migraciones
> `001`-`024` + `seed.sql` (+ opcionalmente `seed_demo.sql` o `seed_qa.sql`)
> aplican sin errores y el login del admin verifica.

## QA automatizada (portátil entre máquinas)

```bash
# con el server dev arriba (php -S localhost:3000 desde la raíz):
pwsh -File database/qa_all.ps1              # aplica seed_qa.sql y corre los 3 arneses (49 checks)
pwsh -File database/qa_all.ps1 -SkipSeed    # no reinstala el fixture (usa el estado actual)
```

`qa_all.ps1` aplica `seed_qa.sql` por defecto, así que la suite corre idéntica en
cualquier máquina (el fixture está versionado). Tras la QA, para volver al demo:
`mysql -u root sysai < database/seed_demo.sql`.

## Reglas

- Una migración aplicada **no se edita**: se corrige con una nueva.
- Numeración correlativa de 3 dígitos (`001`, `002`, …).
- El DDL en MySQL hace commit implícito; la seguridad ante reejecución viene del
  registro en `schema_migrations`, no de transacciones.
