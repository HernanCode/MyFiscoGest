# MyFiscoGest

> **Nombre provisional.** Es solo un marcador para publicar; se cambiará por el
> definitivo (estábamos entre *Cuadre.*, *Cuadro.* y *Timón.*). Al renombrar hay que
> tocar `APP_NAME` del `.env`, el `name`/`description` de `composer.json` y este título.

App web de **gestión fiscal para autónomos en España**, pensada para **autoalojarse**
(cada persona lleva sus propios datos) y **multiusuario** (tú y quien tú invites,
con los datos aislados entre sí).

## Qué hace

- **Movimientos**: altas de ingresos y gastos con factura adjunta (PDF o imagen),
  filtros por año, trimestre, tipo y estado de cobro/pago, y autocompletado de
  clientes/proveedores.
- **Panel**: ingresos, gastos y margen del trimestre, próximos pagos (303 + 130),
  caja pendiente (por cobrar / por pagar) y un checklist *"Ponte al día"*.
- **Presentaciones**: los 8 vencimientos del año (IVA 303 e IRPF 130), con su importe
  estimado y botón para marcar como presentado.
- **Recurrentes**: plantillas de movimientos que se repiten cada mes (cuota de
  autónomos, alquiler, suscripciones), con generación manual.
- **Exportación**: CSV para el gestor y copia de seguridad completa en ZIP
  (CSV + facturas clasificadas por año/trimestre).
- **Multiusuario**: registro público cerrado; las cuentas se crean por **invitación**
  desde la pantalla *Usuarios* (solo el dueño).

> Los cálculos (303/130) son **estimaciones orientativas** para organizarte; no
> sustituyen a la presentación oficial. Ver `docs/spec.md`.

## Stack

- PHP 8.4+ y Laravel
- Blade + Tailwind + Alpine (starter kit Breeze)
- SQLite en desarrollo (migraciones compatibles con MySQL)
- Tests con PHPUnit

## Instalación local

Requisitos: PHP 8.4+, Composer y Node.js.

```bash
git clone <url-del-repo> myfiscogest
cd myfiscogest

# Crea el .env, la clave, la base de datos SQLite, siembra y compila assets:
composer setup
```

Antes de `composer setup`, ajusta en tu `.env` al menos:

```dotenv
APP_NAME=MyFiscoGest
APP_URL=http://localhost:8000

# Dueño de la instancia (se crea al sembrar)
ADMIN_NAME="Tu nombre"
ADMIN_EMAIL="tu@email.com"
ADMIN_PASSWORD="una-contraseña-larga-y-segura"
```

Si no defines `ADMIN_*`, el seeder avisa y **no crea ningún usuario** (y no podrás
entrar, porque el registro está cerrado).

Arrancar en desarrollo:

```bash
composer dev        # servidor + cola + logs + vite
# o por separado:
php artisan serve
npm run dev
```

Configura el correo si quieres que funcione *"¿Olvidaste tu contraseña?"*. Por
defecto `MAIL_MAILER=log` (los correos se escriben en `storage/logs/laravel.log`,
no se envían):

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.tu-proveedor.com
MAIL_PORT=587
MAIL_USERNAME=tu@email.com
MAIL_PASSWORD=...
MAIL_FROM_ADDRESS="avisos@tudominio.com"
```

## Usuarios e invitaciones

- El primero (el `ADMIN_*`) es el **dueño** y el único que ve la pantalla **Usuarios**.
- Desde ahí genera **enlaces de invitación** (caducan a los 7 días y sirven una sola
  vez). Se los pasas a quien quieras; esa persona elige su nombre y contraseña.
- Cada cuenta ve **solo sus datos**. El registro público está desactivado.

## Despliegue con Docker

```bash
# 1) Prepara el .env de producción en la raíz del proyecto
cp .env.example .env
php artisan key:generate --show          # copia el valor a APP_KEY

# 2) Edita .env: APP_URL con tu dominio, ADMIN_*, MAIL_* y, si hay proxy,
#    TRUSTED_PROXIES=*  (ver más abajo)

# 3) Levanta
docker compose up -d --build
```

La app escucha en `http://localhost:8080` (**pon delante un proxy con HTTPS**:
nginx, Caddy, Traefik…). En producción la app **fuerza https** y marca la cookie de
sesión como segura automáticamente.

- Los datos (base de datos SQLite, facturas y logs) viven en el **volumen** `storage`,
  así que sobreviven a recompilaciones. Monta ahí tu backup.
- Si va detrás de un proxy, define `TRUSTED_PROXIES` (su IP/CIDR o `*`) para que se
  detecten bien la IP real y el HTTPS.

### Variables de entorno relevantes

| Variable | Para qué |
|---|---|
| `APP_KEY` | Clave de la app (**obligatoria**). |
| `APP_URL` | URL pública (para enlaces de reset, etc.). |
| `ADMIN_NAME` / `ADMIN_EMAIL` / `ADMIN_PASSWORD` | Dueño de la instancia (primer arranque). |
| `MAIL_*` | Envío de correo (recuperar contraseña). |
| `TRUSTED_PROXIES` | IPs/CIDR del proxy si la app va detrás de uno. |
| `SESSION_SECURE_COOKIE` | Fuerza cookie segura (se activa sola en producción). |

## Copias de seguridad

- **Desde la app**: *Perfil → Exportar copia completa (.zip)*.
- **Del servidor**: haz copia del volumen `storage` (contiene la base de datos y las
  facturas) y del `.env`.
- **Borrar una cuenta** elimina también sus datos (años, movimientos, facturas,
  vencimientos y recurrentes). El único administrador no puede darse de baja.

## Tests

```bash
php artisan test
# o
composer test
```

## Seguridad

- Registro cerrado; cuentas solo por invitación del dueño.
- Login con límite de intentos (por email+IP y por IP) y mensaje genérico.
- Contraseñas con bcrypt y **mínimo 12 caracteres**.
- Todo lo sensible detrás de autenticación, con comprobación de propiedad por registro.
- `APP_DEBUG=false` en producción, cookie de sesión segura y HTTPS.
- `secretos` (`.env`), base de datos e invoices **nunca** se versionan.

## Estructura de documentación

- `docs/spec.md`: modelo de datos y cálculos fiscales (fuente de verdad).
- `docs/DESIGN.md`: sistema de diseño y pantallas.

## Licencia

[AGPL-3.0](LICENSE) © 2026 Samuel Hernán.

Si publicas una versión modificada y la ofreces como servicio en red, la AGPL obliga a
ofrecer el código fuente a sus usuarios.
