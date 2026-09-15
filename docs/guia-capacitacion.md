# Guía de conducción — Capacitación Arca / Organización Arco Iris

> Para el que dirige la sesión. Presencial, misma red, sobre el equipo de desarrollo con Apache.
> Todo lo que aparece aquí está verificado sobre la máquina real, no supuesto.

---

## 1. Antes del día

| # | Qué | Cómo se comprueba |
|---|---|---|
| 1 | **Apache arriba** | Panel de XAMPP → Apache en verde. `netstat -ano \| findstr :8080` debe listar `LISTENING`. |
| 2 | **Firewall** | Regla "Apache HTTP Server" (TCP entrante, perfiles Privada y Pública) habilitada. |
| 3 | **La Wi-Fi en perfil Privada** | Si Windows la marca como Pública tras cambiar de red, la regla puede no aplicar. |
| 4 | **Anotar la IP** | `npm run dev` la imprime, o `ipconfig`. **Cambia al reconectar el Wi-Fi** — verificarla el mismo día. |
| 5 | **Correo operativo** | `php database/smtp_test.php tu-correo@gmail.com` debe terminar en `SMTP OPERATIVO`. |
| 6 | **Prueba de calentamiento** | Ver §5. Hacerla **el día antes**, no en la sesión. |
| 7 | **Participantes dados de alta** | `php database/preparar_capacitacion.php --simular` no debe proponer altas. |
| 8 | **Respaldo limpio** | `pwsh -File database\respaldo.ps1 guardar -Etiqueta estado-inicial` |

**La URL que reparten a los participantes es `http://<IP>:8080`** — no `localhost`, no el 3000 ni el 3001,
que solo existen en el equipo anfitrión.

---

## 2. Preparar los datos

```bash
php database/preparar_capacitacion.php          # crea database/participantes.csv si no existe
# … rellenar el CSV: una línea por persona, debajo de la cabecera …
php database/preparar_capacitacion.php --simular
php database/preparar_capacitacion.php
```

Cada **coordinador** recibe usuario + programa propio + un sobre de S/ 100 000 sobre el
`FONDO DE CAPACITACION`. Ese sobre es lo que le permite presupuestar: sin él, el sistema le bloquea
rubros, POA Presupuestal y rendiciones (regla "sin sobres no hay presupuesto").

Con **uno o dos contadores** basta: es el rol que revisa y aprueba lo de todos.

---

## 3. Cómo entra cada participante

**Nadie tiene contraseña, y es correcto.** El alta genera una provisional aleatoria que **nadie
conoce, ni el administrador**. Cada uno activa la suya:

1. `http://<IP>:8080/login` → **"Cambiar contraseña"**
2. Escribe su correo → le llega un código
3. Teclea el código y define su contraseña (mínimo 8 caracteres)

> Conviene explicarlo como parte de la capacitación, no como un trámite: es exactamente el
> mecanismo que usarán en producción, y la razón por la que nadie —ni quien administra— puede
> conocer la contraseña de otro.

---

## 4. Los cuatro tropiezos previsibles

Están medidos sobre `models/Login.php`; los números son los reales.

**① "Pedí el código dos veces y no me llegó el segundo"**
Hay un **cooldown de 2 minutos** por correo (`RECUP_COOLDOWN`). El segundo intento **no reenvía nada**,
y **la pantalla no lo dice**. Avisar en voz alta antes de empezar: *pidan el código UNA vez y esperen.*

**② "El código ya no me sirve"**
Vive **30 minutos** (`RECUP_TOKEN_TTL`). Si alguien lo pide al inicio y lo usa al final de la sesión,
caducó. Indicar que lo pidan **en el momento de usarlo**.

**③ "Me dice que espere / no me deja pedir más"**
Máximo **30 solicitudes por IP cada 15 minutos** (`RECUP_MAX_IP`), y otras 30 verificaciones de código
(`RECUP_MAX_VERIFY`). En la LAN de capacitación cada laptop tiene su propia IP, pero **en producción
toda la oficina sale a Internet por una sola IP pública**: los límites son por eso holgados (hasta el
2026-09-14 eran 5, y el sexto usuario de la oficina quedaba bloqueado).

**④ "No puedo entrar, me bloqueó"**
**5 intentos fallidos en 5 minutos** (`RL_MAX_INTENTOS`). Desbloqueo inmediato:

```sql
DELETE FROM login_intentos WHERE email = 'correo@delparticipante';
```

**Red de seguridad, si a alguien el correo simplemente no le llega:**

```bash
php database/preparar_capacitacion.php --clave correo@delparticipante
```

Imprime una contraseña provisional. Entregarla **en mano**, y pedirle cambiarla al terminar. Es una
salida de emergencia: si se usa con todos, se pierde justamente lo que la sesión pretende enseñar.

---

## 5. El riesgo de que los correos caigan en spam

El emisor es la cuenta **Gmail** dedicada de Arca (`cronosarca2024@gmail.com`, desde el 2026-09-15). SPF
y DKIM los pone Google, así que la entrega técnica está bien; el riesgo es distinto: **una cuenta recién
creada enviando 9 correos casi idénticos en pocos minutos** es un patrón que los filtros miran con recelo.

- **El día antes**, mandarse a sí mismo y a dos o tres cuentas de dominios distintos (Gmail, Outlook,
  el correo institucional que usen) una prueba con `smtp_test.php`. Ver dónde cae.
- **En la sesión**, decir de entrada: *"si no lo ven en un minuto, miren la carpeta de spam"*. Es más
  barato avisarlo que diagnosticarlo con nueve personas esperando.
- Que no pidan el código todos a la vez: escalonarlo por filas ayuda.

---

## 6. Entre tanda y tanda

Los participantes dejan POAs, rendiciones y aprobaciones que la siguiente tanda no debe encontrar.
Borrar a mano **no sirve**: quedan correlativos avanzados, vínculos coordinador-programa desactivados
y tokens vivos.

```bash
pwsh -File database\respaldo.ps1 restaurar
```

Restaura el respaldo más reciente y pide escribir el nombre de la base para confirmar. Si se quiere
volver a un punto concreto: `respaldo.ps1 listar` y luego `restaurar -Archivo <ruta>`.

> Tras restaurar, **los participantes de la tanda anterior tendrán que volver a activar su contraseña**
> si el respaldo es anterior a que la fijaran. Para evitarlo: hacer el respaldo *después* de que todos
> hayan activado la suya y *antes* de que empiecen a trabajar. Ese es el "estado inicial" útil.

---

## 7. Si algo se cae en plena sesión

| Síntoma | Causa más probable | Qué hacer |
|---|---|---|
| Nadie abre la página | Apache caído, o la IP cambió | Panel de XAMPP; `ipconfig` y volver a repartir la URL |
| Solo unos pocos no abren | Están en otra red (datos móviles, Wi-Fi de invitados) | Que se conecten a la misma red |
| "Configuración desactualizada" | Se hizo `git pull` sin descifrar el `.env` | `npm run env:pull` |
| Error de conexión a la base | MySQL de XAMPP caído | Panel de XAMPP → MySQL Start |
| Un coordinador no ve "POA Presupuestal" | Se quedó sin sobre asignado | Volver a correr `preparar_capacitacion.php`, o asignarlo desde `/dfinanciamiento/crear` |
| No llega ningún correo a nadie | SMTP caído o límite de Gmail | `php database/smtp_test.php <correo>`; si falla, tirar de `--clave` para todos |

---

## 8. Después

1. `pwsh -File database\respaldo.ps1 guardar -Etiqueta fin-capacitacion` — por si hace falta revisar
   lo que hicieron.
2. Decidir qué pasa con las cuentas: si eran de práctica, se dan de baja; si continúan, avisar de que
   el entorno es local y **no** es el sistema definitivo.
3. **Apagar Apache** al terminar, si el equipo se lleva a otras redes. Un servidor en el 8080 con la
   aplicación entera no tiene por qué seguir escuchando en la cafetería.
