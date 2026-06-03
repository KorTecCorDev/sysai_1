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
> `001`-`013` + `seed.sql` aplican sin errores y el login del admin verifica.

## Reglas

- Una migración aplicada **no se edita**: se corrige con una nueva.
- Numeración correlativa de 3 dígitos (`001`, `002`, …).
- El DDL en MySQL hace commit implícito; la seguridad ante reejecución viene del
  registro en `schema_migrations`, no de transacciones.
