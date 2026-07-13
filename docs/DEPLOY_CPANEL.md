# Guia de despliegue a cPanel (Namecheap) - coopaeca.org.pe

Verificado el 2026-07-11. El proyecto ya usa `.env` para toda config sensible
(no hay rutas ni dominios hardcodeados en PHP), asi que subir a hosting es
seguro siguiendo estos pasos en orden.

## 0. Orden exacto de pantallas en tu cPanel (hazlo en este orden)

Tu cPanel (tema Jupiter de Namecheap) tiene todas las herramientas que
necesitas. El SSL ya esta activo para `coopaeca.org.pe` (se ve en "General
Information" de tu panel), asi que no hay que tocar SSL.

**Paso 1 - PHP version (antes de subir nada)**
`Software > Select PHP Version` → elige **PHP 8.2 o 8.3**. Ahi mismo, en la
pestana de extensiones, marca: `mbstring`, `sodium`, `iconv`, `pdo_mysql`,
`gd`, `fileinfo`, `curl`. Si no ves "MultiPHP INI Editor" en esa misma
pantalla, entra a `Software > MultiPHP INI Editor` y en modo "Editor" para
tu dominio sube `upload_max_filesize=20M`, `post_max_size=25M`,
`memory_limit=256M`, `max_execution_time=60` (ver seccion 4 mas abajo).

**Paso 2 - Crear la base de datos (antes de importar nada)**
`Databases > MySQL Databases` (o `Database Wizard` para el flujo guiado):
1. Crea la base de datos (ej. `coopqjib_dashboard`, cPanel antepone tu
   usuario automaticamente).
2. Crea un usuario MySQL con password fuerte.
3. En "Add User to Database" dale **All Privileges**.
Anota los 3 nombres reales (db, usuario, password) — los necesitas para el
`.env` en el Paso 5.

**Paso 3 - Subir el codigo (el ZIP)**
`Files > File Manager` → entra a la carpeta del dominio (normalmente
`public_html/`, o `coopaeca.org.pe/` si es un addon domain separado de tu
dominio principal). Sube el `.zip` del proyecto con el boton **Upload**,
luego click derecho sobre el zip subido → **Extract**. Esto te deja
`app/`, `public/`, `storage/`, `vendor/`, etc. directamente en esa carpeta
(no dentro de una subcarpeta extra — si `Extract` crea una carpeta
`ccopaeca/` adicional, mueve todo su contenido un nivel arriba).
Ver seccion 1 y 6 para el detalle de que va y que no va en el zip.

*Alternativa mas rapida (tienes SSH y Git habilitados en tu panel):* si
prefieres evitar subir un zip pesado por el navegador, con
`Security > SSH Access` puedes entrar por terminal y hacer
`git clone`/`git pull` + `composer install --no-dev` directamente en el
servidor. Es opcional, el metodo zip funciona igual de bien.

**Paso 4 - Importar la base de datos**
`Databases > phpMyAdmin` → selecciona la base de datos creada en el Paso 2
→ pestana **Importar** → sube `database/dashboard_base_export.sql`. Ver
seccion 2 para detalles de por que usar ese dump y no `schema.sql`.

**Paso 5 - Configurar el `.env`**
En `File Manager`, dentro de la carpeta donde extrajiste el zip, crea el
archivo `.env` (activa "Show Hidden Files" en Settings del File Manager si
no lo ves) con los datos reales del Paso 2. Ver seccion 3 para la plantilla
completa.

**Paso 6 - Permisos**
En `File Manager`, click derecho > **Permissions** sobre `storage/`,
`storage/logs`, `storage/uploads`, `storage/mail_attachments`,
`storage/purifier` y `public/uploads` → `755` (o `775` si con `755` el sitio
no puede escribir logs/uploads). Ver seccion 5.

**Paso 7 - Subir las imagenes reales**
Copia el contenido de tus carpetas locales `public/uploads/*` (no viajan en
git/zip) a las mismas rutas en el servidor via File Manager o FTP. Ver
seccion 6 para el listado exacto de carpetas y tamanos.

**Paso 8 - Verificar**
Ver el checklist de la seccion 8. Entra a `https://coopaeca.org.pe/` y
`https://coopaeca.org.pe/login`.

## 1. Estructura de subida

El `.htaccess` de la raiz ya redirige todo hacia `public/`, asi que **sube
TODO el proyecto tal cual** a la raiz del dominio (`public_html/` o el
subdominio que apunte a `coopaeca.org.pe`), no solo la carpeta `public/`.

Carpetas/archivos a subir por FTP/File Manager (todo excepto lo listado en
"NO subir"):

```
app/  database/  public/  storage/  vendor/  .htaccess  composer.json  composer.lock
```

### NO subir (o borrar despues si se subio por error)
- `.git/`, `.claude/`, `.codex/`, `.agents/`, `.tmp_sessions/`
- `docs/` (opcional, no es necesario en produccion)
- `database/dashboard_base_export.sql` (subelo aparte, solo para importar en phpMyAdmin, luego bórralo del servidor)
- Cualquier `.env` local: en el servidor se crea uno nuevo (paso 3)

## 2. Base de datos

1. En cPanel > **MySQL Databases**, crea la base de datos y un usuario con
   todos los privilegios sobre ella (Namecheap antepone tu usuario cPanel al
   nombre, ej. `usuario_dashboard_base`).
2. En **phpMyAdmin**, entra a esa base vacia y ve a **Importar**.
3. Sube `database/dashboard_base_export.sql` (dump fresco generado hoy con
   `mysqldump`, 336 KB, incluye TODA la data actual: usuarios, productos,
   pedidos, ventas, sliders, configuracion). Es mas seguro que reconstruir
   con `schema.sql` + migraciones sueltas, porque `schema.sql` esta
   desactualizado (faltan las migraciones 0026 a 0033: ecommerce, sliders,
   comprobantes, etc.) y no hay un runner de migraciones en el proyecto.
4. Verifica que importo sin errores (revisa el contador de tablas al final).

## 3. Archivo `.env` en el servidor

Crea `.env` en la raiz del proyecto en el servidor (usa `.env.example` como
plantilla) con estos valores:

```
APP_NAME="COOPAECA"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://coopaeca.org.pe
APP_TIMEZONE=America/Lima
APP_SESSION_NAME=base_dashboard_session
APP_SECURE_COOKIES=true

API_PERU_TOKEN=<copiar_del_.env_local>
API_PERU_BASE_URL=https://apiperu.dev/api
API_PERU_SSL_VERIFY=true

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=<nombre_real_asignado_por_cpanel>
DB_USERNAME=<usuario_real_asignado_por_cpanel>
DB_PASSWORD=<password_real>
DB_CHARSET=utf8mb4

MAIL_ENCRYPTION_KEY=<copiar_TAL_CUAL_del_.env_local>

MAIL_NOTIFY_HOST=coopaeca.org.pe
MAIL_NOTIFY_PORT=465
MAIL_NOTIFY_EMAIL=<copiar_del_.env_local>
MAIL_NOTIFY_PASSWORD=<copiar_del_.env_local>
```

Los valores marcados como "copiar del `.env` local" estan en tu archivo
`.env` local (que no esta en git). No los pego aqui en texto plano para que
esta guia se pueda subir/commitear sin exponer credenciales.

**CRITICO:** `MAIL_ENCRYPTION_KEY` debe ser EXACTAMENTE la misma que en local
(copiala del `.env` local, no generes una nueva). Las contrasenas SMTP de las
cuentas de correo guardadas en la tabla `mail_accounts` estan cifradas con
esa clave; si cambia, el envio de correos desde el modulo de Mail deja de
funcionar hasta reconfigurar cada cuenta manualmente.

`APP_DEBUG=false` es obligatorio en produccion: con `true`, cualquier error
muestra el stack trace completo (rutas del servidor, queries) a cualquier
visitante.

## 4. Requisitos de PHP en cPanel (MultiPHP Manager)

- **Version PHP: 8.2 o superior** (el proyecto usa `readonly`/`enum` de 8.1+
  y `declare(strict_types=1)`; se verifico que no usa sintaxis exclusiva de
  8.3, asi que 8.2 tambien sirve si 8.3 no esta disponible).
- Extensiones requeridas (activalas en **MultiPHP Extensions** si no lo estan
  por defecto): `mbstring`, `iconv`, `sodium`, `pdo_mysql`, `gd` o `imagick`
  (usadas por Dompdf/optimizacion de imagenes), `fileinfo`.
- En **MultiPHP INI Editor**, sube estos limites (los defaults de shared
  hosting suelen ser bajos para el modulo de vouchers/comprobantes/PDF):
  - `upload_max_filesize = 20M`
  - `post_max_size = 25M`
  - `memory_limit = 256M`
  - `max_execution_time = 60`

## 5. Permisos de carpetas

Tras subir, en File Manager o por SSH:
```
chmod -R 755 storage storage/logs storage/uploads storage/mail_attachments storage/purifier
chmod -R 755 public/uploads
```
Estas carpetas deben ser escribibles por el usuario de PHP (Apache/PHP-FPM
del hosting) porque ahi se guardan logs, PDFs, vouchers, imagenes subidas y
la cache de HTMLPurifier.

## 6. Archivos subidos existentes (imagenes reales)

Estas carpetas tienen contenido real en local que **no esta en git** (estan
en `.gitignore` a proposito) y hay que subirlas manualmente por FTP:

```
public/uploads/media/            (~25 MB)
public/uploads/payment-methods/  (~1 MB)
public/uploads/posts/            (~1.3 MB)
public/uploads/receipts/         (~1.7 MB)
public/uploads/settings/         (~1.8 MB, incluye el logo)
public/uploads/sliders/          (~1.4 MB)
public/uploads/vouchers/         (~1.2 MB)
```
Copia el contenido de estas carpetas locales (no solo el `.htaccess`) al
mismo path en el servidor, respetando los nombres de archivo (la base de
datos ya referencia esos nombres exactos en la tabla `files`).

## 7. Composer / vendor

Si el hosting tiene acceso SSH con Composer, mejor ejecutar
`composer install --no-dev --optimize-autoloader` directamente en el
servidor. Si no hay SSH, sube la carpeta `vendor/` completa tal cual (ya
esta en tu local, sincronizada con `composer.lock`).

## 8. Verificacion post-deploy (checklist rapido)

- [ ] `https://coopaeca.org.pe/` carga el landing (no error 500).
- [ ] `https://coopaeca.org.pe/login` entra con un usuario real de la BD.
- [ ] Dashboard, Pedidos, Ventas, Productos muestran datos (confirma que la
      BD importada tiene la data).
- [ ] Las imagenes del landing (hero sliders, productos, logo) cargan bien
      (confirma que subiste `public/uploads/*`).
- [ ] Un pedido nuevo de prueba en el checkout llega el correo de
      confirmacion (confirma SMTP + `MAIL_ENCRYPTION_KEY` correctos).
- [ ] Ver un comprobante/ticket en PDF (confirma que Dompdf y la fuente
      `ARIALN.TTF` funcionan igual en el hosting).
- [ ] `.env`, `database/dashboard_base_export.sql`, `.git/` NO son
      accesibles por URL directa (ej. `https://coopaeca.org.pe/.env` debe
      dar 403/404).
