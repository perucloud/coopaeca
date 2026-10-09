# Prueba local de la copia de COOPAECA

Restaurado el 7 de octubre de 2026 desde `dashboard_base.sql`.

- URL: http://coopaeca.test
- Apache: C:/laragon/etc/apache2/sites-enabled/coopaeca.test.conf
- DocumentRoot: C:/laragon/www/ccopaeca/public
- Base local: coopaeca_local_20261007 (la base dashboard_base existente se conserva).
- Credenciales: las existentes en .env; no se cambiaron usuarios ni contrasenas.
- Restauracion: 45 tablas, 60 relaciones, 2 usuarios, 6 productos, 18 pedidos, 17 ventas, 1892 registros de ubigeo.
- Archivos: los 75 registros de files tienen su archivo fisico.
- Dependencias de PHP verificadas; PHPUnit: 4 pruebas correctas.

## Registrar el dominio

Windows denego la escritura de hosts por falta de permisos administrativos. Abrir PowerShell como administrador y ejecutar:

```powershell
& 'C:\laragon\www\ccopaeca\docs\registrar-coopaeca-local.ps1'
```

Luego abrir http://coopaeca.test y http://coopaeca.test/login, utilizando las cuentas de la copia original.

Apache y MySQL estan iniciados. Para futuras sesiones iniciarlos desde Laragon.
La configuracion previa de .env se guarda en storage/.env.local-sync-backup.
## Verificacion HTTP

Inicio, dashboard, productos, configuracion y ubigeo respondieron 200 con la sesion local de prueba. Pedidos, ventas e inventario respondieron 403 para el usuario admin seleccionado: el acceso esta restringido por los permisos de la copia. No se modificaron esos permisos. No se comprobaron envios de correo ni integraciones externas.
