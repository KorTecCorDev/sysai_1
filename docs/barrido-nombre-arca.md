# Barrido del nombre visible → "Arca"

> Preparado el 2026-07-17 en la rama `feat/rediseno-login`, para **ejecutar en el
> equipo de escritorio**. Cambia la **marca visible** de "SysAI" a **"Arca"**.
> El identificador **técnico** `sysai` (repo, BD, namespace PHP, rutas) **NO cambia**.

## Cómo ejecutarlo

```bash
# desde la raíz del repo, en la rama feat/rediseno-login
php scripts/rebrand-arca.php
```

Es **idempotente** (re-ejecutarlo no hace daño) y **no requiere recompilar assets**
(no toca SCSS/JS). Al terminar imprime un resumen y verifica que no quede marca
visible sin barrer en los archivos objetivo.

## Qué cambia (superficie visible al usuario)

| Archivo | Qué |
|---|---|
| `views/layout_admin.php` | `<title>` + `<meta description>` |
| `views/layout_contador.php` | `<title>` + `<meta description>` |
| `views/layout_coordinador.php` | `<title>` + `<meta description>` |
| `views/error404.php` | `<title>` + `<meta description>` |
| `.env.example` | `MAIL_FROM_NAME` (plantilla versionada) |
| `includes/config/mail.php` | fallback del remitente de correos |

- Los `<title>` quedan en **`Arca · Arco Iris`** (igual que el login ya rediseñado).
- La `<meta description>` se alinea con la del login.
- `views/login.php` y `views/layout_login.php` **ya están** en "Arca" (commit `8175416`) — no los toca.

## Paso manual (no automatizable)

- Tu **`.env` local** (no versionado) tiene `MAIL_FROM_NAME=Area de TI - SysAI`.
  Cámbialo a **`Arca`** a mano si quieres que los correos ya salgan con la marca
  nueva desde este equipo. El script solo actualiza la plantilla `.env.example`.

## Qué NO toca (a propósito)

Estas apariciones son **nombre-clave interno de desarrollo**, no las ve el usuario.
Se dejan como están para no meter ruido y porque el id técnico sigue siendo `sysai`:

- `CLAUDE.md` (memoria del proyecto)
- `database/migrate.php`, `database/README.md`, `database/seed.sql`, `database/seed_demo.sql` (comentarios)
- `src/scss/base/_tema.scss`, `_sidebar-tema.scss`, `_variables.scss` (comentarios de cabecera)

> **Opcional** — si algún día quieres que hasta los comentarios internos digan "Arca",
> es un barrido aparte y de bajo valor; se puede hacer con un buscar/reemplazar de
> `SysAI`→`Arca` limitado a esos archivos, recompilando el SCSS después
> (`npx gulp build`) porque se tocarían fuentes de estilo.

## Verificar y commitear

```bash
# 1) Confirmar que no quedó marca visible fuera de los comentarios internos:
grep -rIn --exclude-dir=vendor --exclude-dir=node_modules "SysAI\|SysAi" views/ includes/ .env.example

# 2) Ver en el navegador (local3000 → revisar la pestaña/título en cada rol y el 404).

# 3) Commit:
git add -u
git commit -m "chore(marca): barrido del nombre visible SysAI -> Arca (layouts, 404, correo)"
```
