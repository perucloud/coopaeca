# Dashboard Base PHP

Dashboard administrativo reutilizable para PHP 8.1+ y MySQL/PDO, sin framework grande.

## Estructura

```text
ccopaeca/
  app/
    Config/          Configuracion de app y base de datos
    Controllers/     Acciones HTTP
    Core/            PDO, Router, Response
    Helpers/         Auth, CSRF, sesiones, logs, vistas
    Views/           Plantillas
  database/
    schema.sql       SQL inicial
  public/
    index.php        Front controller
    assets/          CSS y JS
  storage/
    logs/            Logs privados
    uploads/         Subidas privadas/protegidas
```

## Instalacion local en Laragon

1. Crea la base de datos importando `database/schema.sql`.
2. Ajusta credenciales en variables de entorno o en `app/Config/database.php`.
3. En Laragon puedes abrir `http://localhost/ccopaeca`.
   Tambien puedes configurar el document root directamente hacia `public/`.
4. En local puedes entrar con:
   - Email: `admin@example.com`
   - Password: `Admin12345`
5. Cambia la contrasena inmediatamente desde `Mi perfil`.

## Variables recomendadas

```text
APP_NAME="Mi Sistema"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost/ccopaeca
APP_SECURE_COOKIES=false
DB_HOST=localhost
DB_DATABASE=dashboard_base
DB_USERNAME=root
DB_PASSWORD=
```

En produccion usa `APP_DEBUG=false`, `APP_SECURE_COOKIES=true` y HTTPS.

## Checklist de seguridad aplicado

- PDO con prepared statements.
- Passwords con `password_hash` y `password_verify`.
- Regeneracion de ID de sesion al iniciar sesion.
- Cookies HttpOnly, SameSite y Secure configurable.
- CSRF en formularios y soporte para AJAX con `X-CSRF-Token`.
- Rate limit basico para login por IP.
- Remember me con selector y validador hasheado.
- Recuperacion de contrasena con token hasheado y expiracion.
- Escape HTML centralizado con `e()`.
- Rutas privadas con `require_auth()`.
- Roles, modulos y permisos.
- Logs de actividad y errores.
- `.htaccess` para front controller, cabeceras y bloqueo de archivos sensibles.
- `storage/` protegido contra acceso web.
- `uploads/` bloquea ejecucion de scripts.

## Siguiente personalizacion sugerida

- Conectar SMTP real en `AuthController::sendReset()`.
- Agregar tus modulos en `modules` y sus permisos en `role_module`.
- Cambiar colores en `public/assets/css/app.css`.
- Crear controladores nuevos siguiendo el patron de `UserController`.
