# Estado de Implementacion Ecommerce

## Objetivo activo

Implementar el plan `PLAN_ECOMMERCE_PEDIDOS_VENTAS_INVENTARIO.md` usando la arquitectura de agentes `.agents/`, incorporando como referencia funcional el flujo publico/admin del sistema `rifas_siu_kao_sistema` sin copiar codigo ni modificar ese proyecto.

## Estado global

- Completado: Fase 1 base de datos, permisos, servicios base, rutas y pantallas iniciales.
- Completado: Fase 2 inicial modulo Pedidos administrativo.
- Completado: Fase 3 carrito publico.
- Completado: Fase 4 checkout publico inicial con voucher.
- Completado: Fase 5 WhatsApp integrado como comunicacion posterior/no sustitutiva.
- Completado: Fase 6 aprobacion de pedido, venta y descuento de stock.
- Completado: Fase 7 inicial modulo Ventas.
- Completado: Fase 8 inicial modulo Inventario.
- Completado: Fase 9 gestion administrativa de metodos de pago con QR.
- Completado: ventas manuales desde dashboard con voucher e inventario sincronizado.
- Completado: integracion DNI/RUC preparada con API Peru mediante token de entorno.
- Completado: ubigeo local con cobertura nacional (25 departamentos, 196 provincias, 1892 distritos) con codigos INEI y RENIEC, importado desde `ubigeo-peru-aumentado`.
- Completado: direccion del comprador reubicada al paso de datos del comprador, con autocompletado desde DNI/RUC y visible en confirmacion, exito y panel admin.
- Bloqueado: no.

## Decisiones tecnicas iniciales

- WhatsApp sera solo canal de comunicacion.
- El pedido web se crea con voucher obligatorio.
- El stock solo se descuenta al aprobar el pedido.
- El numero de operacion de pago debe ser unico entre pedidos no rechazados/cancelados.
- Los vouchers usaran almacenamiento controlado y registro en `files`.
- Las ventas confirmadas tendran `sales` y `sale_items` reales, no solo union de consultas.
- Inventario se auditara con `stock_movements`; `products.stock` queda como stock actual visible.
- El token de API Peru no se versiona ni se expone al navegador; se debe configurar como `API_PERU_TOKEN` en el entorno.

## Referencia externa analizada

Proyecto: `C:\laragon\www\rifas_siu_kao_sistema`

- Publico: `app/Views/public/formulario.php`, `PublicController::guardarSolicitud`.
- Admin pedidos/vouchers: `VoucherController`, `app/Views/admin/vouchers/index.php`.
- Admin ventas: `VentasController`, `ReporteVentasService`, `app/Views/admin/ventas/index.php`.

Uso permitido:

- Reutilizacion conceptual de flujo, validaciones, modales, filtros, voucher y estados.
- Adaptacion a productos, carrito, pedidos, ventas e inventario de COOPAECA.

Uso descartado:

- Tickets, sorteos, premios, QR de tickets, SUNAT, OCR, email masivo y logica de promociones.

## Handoffs

### ORCHESTRATOR -> DATABASE_ENGINEER

Fecha: 2026-07-08

Tarea:

- Crear migracion ecommerce inicial.
- Crear permisos y acceso por modulo.
- Preparar tablas `orders`, `order_items`, `sales`, `sale_items`, `stock_movements`, `payment_methods` y `ubigeo`.
- Mantener compatibilidad con MVC actual y `products.stock`.

Estado: completado.

HANDOFF aceptado por ORCHESTRATOR:

- Migracion `0026_create_ecommerce_orders_sales_inventory.sql` creada y aplicada en base local.
- Permisos ecommerce agregados al seeder y cargados en base local.
- Modulos `orders`, `sales`, `inventory` y `payment_methods` agregados al catalogo de asignacion.

### ORCHESTRATOR -> BACKEND_ENGINEER

Fecha: 2026-07-08

Tarea:

- Crear servicios de voucher, pedidos, ventas e inventario.
- Implementar aprobacion de pedido con creacion de venta y movimientos de stock.
- Implementar rechazo, revision y anulacion de venta con reversion de inventario.

Estado: completado.

HANDOFF aceptado por ORCHESTRATOR:

- `OrderService` crea pedidos desde checkout, valida cliente, carrito, stock y operacion duplicada.
- `InventoryService` valida disponibilidad, descuenta por venta, revierte anulaciones y permite ajuste manual.
- `SaleService` anula ventas y revierte stock con movimiento auditable.
- `VoucherStorageService` almacena voucher en carpeta controlada y registra `files`.

### ORCHESTRATOR -> DASHBOARD_UI_UX_ENGINEER

Fecha: 2026-07-08

Tarea:

- Crear modulos administrativos de Pedidos, Ventas e Inventario.
- Mantener layout, sidebar, navbar, colores y convenciones actuales.

Estado: completado inicial.

HANDOFF aceptado por ORCHESTRATOR:

- Pedidos: listado, filtros, detalle, voucher, aprobar, revisar y rechazar.
- Ventas: listado, filtros, detalle, voucher y anulacion.
- Inventario: stock por producto, historial de movimientos y ajuste manual.

### ORCHESTRATOR -> LANDING_UI_UX_ENGINEER

Fecha: 2026-07-08

Tarea:

- Implementar carrito publico.
- Implementar checkout por pasos con voucher obligatorio.
- Adaptar boton Comprar por WhatsApp para coordinacion sin saltarse el pedido.

Estado: completado inicial.

HANDOFF aceptado por ORCHESTRATOR:

- Detalle de producto ahora tiene `Anadir al carrito` y `Comprar por WhatsApp`.
- WhatsApp agrega/abre carrito con aviso de checkout obligatorio.
- Checkout publico registra comprador, direccion, metodo de pago, operacion y voucher.
- Pantalla de exito muestra codigo de pedido y boton para enviar resumen por WhatsApp.

### ORCHESTRATOR -> QA_ENGINEER

Fecha: 2026-07-08

Tarea:

- Ejecutar pruebas tecnicas iniciales.
- Ejecutar QA funcional end-to-end del flujo ecommerce.

Estado: completado.

Resultado:

- Sintaxis PHP verificada sin errores en controladores, servicios y vistas modificadas.
- Sintaxis JavaScript verificada con `node --check`.
- Migracion ecommerce aplicada en base local.
- Seeder de permisos aplicado en base local.
- Render publico verificado con servidor PHP temporal: `/checkout?lang=es` HTTP 200 y `/?lang=es` HTTP 200.
- Checkout publico real verificado por HTTP con CSRF, cookie de sesion y voucher multipart.
- Pedido QA generado correctamente: `PED-20260708-4F032B0D`.
- Aprobacion administrativa verificada con `OrderService::approve`.
- Venta confirmada creada: `VEN-20260708-D2A5AC98`.
- Stock verificado: producto 6 bajo de 10 a 9 al aprobar pedido.
- Movimiento de inventario verificado: 1 movimiento `salida_venta`.
- Datos QA limpiados despues de la prueba y stock restaurado.

### ORCHESTRATOR -> SECURITY_ENGINEER

Fecha: 2026-07-08

Tarea:

- Revisar superficie de checkout publico, voucher, metodo de pago y carrito local.

Estado: completado inicial.

Resultado:

- CSRF activo en `POST /checkout`.
- El precio y stock no dependen del carrito local; se recalculan en backend desde `products`.
- El metodo de pago del checkout se valida contra `payment_methods` activos.
- El contenido renderizado desde `localStorage` en carrito/checkout se escapa en JavaScript.
- Voucher limitado a JPG, PNG, WebP y PDF con tamanos maximos controlados.

### ORCHESTRATOR -> PAYMENT_METHODS_ENGINEER

Fecha: 2026-07-08

Tarea:

- Implementar gestion administrativa inicial de metodos de pago.

Estado: completado inicial.

HANDOFF aceptado por ORCHESTRATOR:

- Modulo `/payment-methods` creado en dashboard.
- Administrador puede crear y actualizar nombre, tipo, cuenta/contacto, titular, instrucciones, estado y orden.
- Checkout publico solo acepta metodos activos.
- Administrador puede subir/reemplazar QR de pago JPG, PNG o WebP hasta 3 MB.
- Checkout publico muestra QR, cuenta/contacto, titular e instrucciones cuando estan configurados.

### ORCHESTRATOR -> BACKEND_ENGINEER + DASHBOARD_UI_UX_ENGINEER

Fecha: 2026-07-08

Tarea:

- Implementar ventas manuales desde dashboard para operaciones confirmadas por WhatsApp, telefono o atencion interna.
- Mantener voucher obligatorio, validacion de metodo de pago, numero de operacion unico e inventario auditable.
- Crear una interfaz administrativa moderna, clara y consistente con el sistema actual.

Estado: completado.

HANDOFF aceptado por ORCHESTRATOR:

- Ruta `/sales/create` agregada con permiso `sales.create`.
- Ruta `/sales/store` registra venta manual confirmada.
- Formulario administrativo permite comprador, origen, documento, productos multiples, cantidades, precio unitario, metodo de pago, numero de operacion y voucher.
- `SaleService::createManual` valida cliente, productos publicados, stock disponible, metodo activo y operacion duplicada.
- Al guardar venta manual se crean `sales`, `sale_items` y movimientos `salida_venta`.
- El stock se descuenta automaticamente solo al confirmar la venta.
- Listado de ventas incorpora acceso rapido a `Nueva venta`.
- UI con resumen lateral, totales en vivo, filas dinamicas y advertencia visual si una cantidad supera stock.

### ORCHESTRATOR -> BACKEND_ENGINEER + LANDING_UI_UX_ENGINEER + DASHBOARD_UI_UX_ENGINEER + SECURITY_ENGINEER

Fecha: 2026-07-08

Tarea:

- Integrar consulta DNI/RUC usando API Peru sin exponer el token en frontend ni versionarlo.
- Permitir autocompletar datos del comprador en checkout publico y venta manual administrativa.
- Mantener la carga manual como fallback cuando la API no este configurada o no responda.

Estado: completado tecnico.

HANDOFF aceptado por ORCHESTRATOR:

- Servicio `ApiPeruIdentityService` creado para consultar `/dni` y `/ruc`.
- Token leido desde `API_PERU_TOKEN`; base URL y verificacion SSL configurables desde entorno.
- Controlador `IdentityController` expone `POST /identity/lookup` con CSRF y rate-limit.
- Checkout publico incorpora boton `Buscar` para DNI/RUC.
- DNI autocompleta nombre del comprador.
- RUC autocompleta razon social, region, provincia, distrito y direccion cuando la API los devuelve.
- Venta manual administrativa incorpora consulta DNI/RUC y autocompleta comprador.
- Frontend muestra estados visuales de carga, exito y error.
- `.env.example` documenta variables necesarias sin incluir credenciales reales.

### ORCHESTRATOR -> DATABASE_ENGINEER + BACKEND_ENGINEER + LANDING_UI_UX_ENGINEER

Fecha: 2026-07-08

Tarea:

- Implementar ubigeo local (departamentos, provincias, distritos) para reemplazar los campos de texto libre de region/provincia/distrito en el checkout publico.
- Mantener carga manual como fallback si el catalogo de ubigeo no esta disponible.

Estado: completado.

HANDOFF aceptado por ORCHESTRATOR:

- Migracion `0027_seed_core_ubigeo.sql` agrega datos base de ubigeo (Junin/Satipo, Lima, Callao) sobre la tabla `ubigeo` creada en `0026_create_ecommerce_orders_sales_inventory.sql`.
- `UbigeoService` expone `departments()`, `provinces()`, `districts()` y `coverageCount()`.
- `UbigeoController` expone `GET /ubigeo/departments`, `GET /ubigeo/provinces` y `GET /ubigeo/districts`.
- `CheckoutController::cart` inyecta `departments` y `ubigeoCount` a la vista.
- `checkout.php` usa selects en cascada region -> provincia -> distrito cuando hay cobertura de ubigeo, y cae a inputs de texto libre cuando no la hay.
- `landing.js` implementa la cascada de selects y la integra con el autocompletado DNI/RUC existente (RUC ahora selecciona el ubigeo por nombre en vez de solo rellenar texto).

Validado por QA_ENGINEER (verificacion tecnica):

- `php -l` sin errores en `UbigeoController.php`, `UbigeoService.php`, `CheckoutController.php`, `public/index.php`, `app/bootstrap.php`.
- `node --check public/assets/js/landing.js` sin errores.
- Migracion `0027_seed_core_ubigeo.sql` aplicada en base local: tabla `ubigeo` con 68 filas.
- Render HTTP local: `/checkout?lang=es` HTTP 200, `/?lang=es` HTTP 200.
- `GET /ubigeo/departments` devuelve 3 departamentos.
- `GET /ubigeo/provinces?department_code=15` devuelve provincia de Lima.
- `GET /ubigeo/districts?province_code=1501` devuelve distritos de Lima.

## Archivos modificados

- `docs/ECOMMERCE_IMPLEMENTATION_STATUS.md`
- `docs/PLAN_ECOMMERCE_PEDIDOS_VENTAS_INVENTARIO.md`
- `database/migrations/0026_create_ecommerce_orders_sales_inventory.sql`
- `database/seeders/0001_permissions.sql`
- `app/bootstrap.php`
- `app/Controllers/UserController.php`
- `app/Controllers/OrderController.php`
- `app/Controllers/SalesController.php`
- `app/Controllers/InventoryController.php`
- `app/Controllers/CheckoutController.php`
- `app/Controllers/PaymentMethodController.php`
- `app/Controllers/IdentityController.php`
- `app/Services/ApiPeruIdentityService.php`
- `app/Services/VoucherStorageService.php`
- `app/Services/InventoryService.php`
- `app/Services/OrderService.php`
- `app/Services/SaleService.php`
- `app/Views/layouts/app.php`
- `app/Views/orders/index.php`
- `app/Views/orders/show.php`
- `app/Views/sales/index.php`
- `app/Views/sales/create.php`
- `app/Views/sales/show.php`
- `app/Views/inventory/index.php`
- `app/Views/inventory/movements.php`
- `app/Views/landing/product-detail.php`
- `app/Views/landing/checkout.php`
- `app/Views/landing/checkout-success.php`
- `app/Views/payment_methods/index.php`
- `app/Helpers/icons.php`
- `public/index.php`
- `public/assets/css/app.css`
- `public/assets/css/landing.css`
- `public/assets/js/landing.js`
- `.env.example`
- `app/Controllers/UbigeoController.php`
- `app/Services/UbigeoService.php`
- `database/migrations/0027_seed_core_ubigeo.sql`
- `database/migrations/0028_expand_ubigeo_catalog.sql`
- `database/seeders/import_ubigeo.php`
- `database/seeders/data/ubigeo/departamento.csv`
- `database/seeders/data/ubigeo/provincia.csv`
- `database/seeders/data/ubigeo/distrito.csv`
- `app/Services/ApiPeruIdentityService.php`
- `app/Views/landing/checkout-success.php`

## Pruebas ejecutadas

- `php -l app\Controllers\CheckoutController.php`
- `php -l app\Views\landing\checkout.php`
- `php -l app\Views\landing\checkout-success.php`
- `php -l app\Views\landing\product-detail.php`
- `php -l app\Services\OrderService.php`
- `php -l app\Services\InventoryService.php`
- `php -l public\index.php`
- `php -l app\Controllers\OrderController.php`
- `php -l app\Controllers\SalesController.php`
- `php -l app\Controllers\InventoryController.php`
- `php -l app\Controllers\PaymentMethodController.php`
- `php -l app\Views\payment_methods\index.php`
- `php -l app\Controllers\CheckoutController.php` despues de conectar QR de metodos de pago.
- `php -l app\Views\landing\checkout.php` despues de mostrar QR en checkout.
- Render HTTP local `/checkout?lang=es` despues de conectar QR: HTTP 200.
- `node --check public\assets\js\landing.js`
- `php -l app\Services\OrderService.php` despues de hardening de metodo de pago.
- `node --check public\assets\js\landing.js` despues de hardening de escape frontend.
- `php -l app\Services\SaleService.php` despues de ventas manuales.
- `php -l app\Controllers\SalesController.php` despues de ventas manuales.
- `php -l app\Views\sales\create.php`.
- `node --check public\assets\js\app.js` despues de ventas manuales.
- QA HTTP local de venta manual con login administrativo, CSRF y voucher multipart.
- Venta manual QA generada: `VEN-20260708-5A6ABDF6`.
- Operacion QA: `QAMAN20260709031330`.
- Verificado descuento de stock y movimiento `salida_venta` durante la prueba.
- Datos QA limpiados despues de la prueba; producto 6 verificado nuevamente con stock 10.
- `php -l app\Services\ApiPeruIdentityService.php`.
- `php -l app\Controllers\IdentityController.php`.
- `php -l app\Views\landing\checkout.php` despues de conectar consulta DNI/RUC.
- `php -l app\Views\sales\create.php` despues de conectar consulta DNI/RUC.
- `php -l public\index.php` despues de registrar ruta `/identity/lookup`.
- `node --check public\assets\js\landing.js` despues de conectar consulta DNI/RUC.
- `node --check public\assets\js\app.js` despues de conectar consulta DNI/RUC en venta manual.
- Verificacion local sin token: `ApiPeruIdentityService` responde de forma controlada con `Consulta DNI/RUC no configurada`.
- Aplicacion local de `database/migrations/0026_create_ecommerce_orders_sales_inventory.sql`
- Aplicacion local de `database/seeders/0001_permissions.sql`
- Render HTTP local con servidor temporal: `/checkout?lang=es` y `/?lang=es`.
- QA funcional end-to-end:
  - Crear pedido publico con voucher por HTTP multipart.
  - Aprobar pedido con `OrderService`.
  - Verificar venta, stock y movimiento de inventario.
  - Limpiar datos QA y restaurar stock.

### ORCHESTRATOR -> BACKEND_ENGINEER + FRONTEND_ENGINEER

Fecha: 2026-07-09

Tarea:

- Reubicar el campo de direccion (fiscal/domicilio) al paso de datos del comprador para que el autocompletado de DNI/RUC lo llene junto con el nombre.
- Permitir que el autocompletado de direccion funcione tanto para DNI como para RUC (antes solo aplicaba a RUC).
- Mostrar la direccion en el modal de confirmacion, en la pagina de exito del pedido y en el resumen enviado por WhatsApp.

Estado: completado.

HANDOFF aceptado por ORCHESTRATOR:

- `ApiPeruIdentityService::normalizeDni` ahora expone `address` (desde `direccion_completa`/`direccion` de RENIEC) igual que `normalizeRuc`.
- `checkout.php`: el input `address` (`checkoutAddress`) se movio al panel 2 (Datos del comprador); el panel 3 quedo como "Ubicacion de entrega" (region/provincia/distrito/referencia).
- `landing.js`: el autofill de `address` ya no depende del tipo de documento; los selects de ubigeo se aplican solo cuando la API devuelve region/provincia/distrito.
- Modal de confirmacion, `checkout-success.php` y el mensaje de WhatsApp ahora incluyen la direccion completa.
- La validacion server-side de `OrderService::validateCustomer` (direccion obligatoria) no cambio; ya exigia el campo antes de este ajuste.

Validado por QA_ENGINEER:

- `php -l` sin errores en `ApiPeruIdentityService.php` y `checkout-success.php`.
- `node --check` sin errores en `landing.js`.
- `/checkout?lang=es` HTTP 200 con el campo `checkoutAddress` presente en el HTML renderizado.
- Verificacion en vivo contra RENIEC/SUNAT real (Apache, no CLI): DNI y RUC devuelven la clave `address` en la respuesta JSON.

### ORCHESTRATOR -> DATABASE_ENGINEER

Fecha: 2026-07-09

Tarea:

- Ampliar el catalogo de ubigeo de 3 regiones de muestra a cobertura nacional completa, usando `https://github.com/jmcastagnetto/ubigeo-peru-aumentado` como fuente.
- Guardar codigos INEI y RENIEC por nivel (departamento/provincia/distrito) sin romper el esquema ni los datos ya usados por `UbigeoService`.
- Evitar duplicados y mantener la jerarquia region -> provincia -> distrito.

Estado: completado.

HANDOFF aceptado por ORCHESTRATOR:

- CSVs fuente (`departamento.csv`, `provincia.csv`, `distrito.csv`) descargados de la rama `main` del repositorio y guardados en `database/seeders/data/ubigeo/` para importaciones reproducibles.
- Migracion `0028_expand_ubigeo_catalog.sql` agrega `department_reniec_code`, `province_reniec_code` y `district_reniec_code` a la tabla `ubigeo` existente (sin tocar `department_code`/`province_code`/`district_code`, que siguen siendo codigos INEI/UBIGEO estandar y mantienen la clave unica `district_code`).
- Importador `database/seeders/import_ubigeo.php` hace upsert (`INSERT ... ON DUPLICATE KEY UPDATE`) por `district_code`, cruzando departamento/provincia por nombre para asignar sus codigos INEI/RENIEC correctos a cada distrito.
- Antes de importar se respaldo la tabla original en `ubigeo_backup_pre0028` (68 filas previas) en la base local.
- Cobertura final: 25 departamentos, 196 provincias, 1892 distritos. Se omitio 1 distrito (Moquegua / Mariscal Nieto / San Antonio) por no tener codigo INEI oficial en la fuente; queda documentado en la salida del importador.
- No se modificaron `UbigeoController`, `UbigeoService` ni las rutas `/ubigeo/*`: siguen funcionando igual, ahora sobre datos nacionales completos.

Validado por QA_ENGINEER:

- Verificacion de duplicados: `SELECT district_code FROM ubigeo GROUP BY district_code HAVING COUNT(*) > 1` devuelve 0 filas.
- `GET /ubigeo/departments` devuelve los 25 departamentos del Peru.
- `GET /ubigeo/provinces?department_code=12` devuelve las 9 provincias de Junin.
- `GET /ubigeo/districts?province_code=1206` devuelve los distritos de Satipo.
- `/checkout?lang=es` HTTP 200 con el select de region mostrando las 25 opciones (verificado AMAZONAS...UCAYALI en el HTML renderizado).
- `php -l` sin errores en `import_ubigeo.php`.

### ORCHESTRATOR -> BACKEND_ENGINEER + FRONTEND_ENGINEER + UI_UX_ENGINEER

Fecha: 2026-07-09

Tarea:

- Rediseñar la forma de ajustar stock en Inventario: modal rapido por producto (permitiendo sumar o restar) y modo de ingreso masivo editando directamente la columna Stock de la tabla, en vez de una pagina aparte con selector de producto.
- Agregar acceso directo para editar un producto desde Inventario, reutilizando el formulario real de Productos (sin duplicar logica).
- En Productos: boton para ir a Inventario, boton para imprimir/descargar PDF del listado con previsualizacion en modal, y botones Editar/Eliminar con color distintivo.
- Instalar una libreria PDF real (no solo impresion del navegador) y generar el PDF en servidor.

Estado: completado.

HANDOFF aceptado por ORCHESTRATOR:

- Se instalo `dompdf/dompdf` (v3.1.5) via Composer. `composer.json`/`composer.lock` actualizados; `vendor/` sigue ignorado por git como ya estaba (`/vendor/` en `.gitignore`), asi que **hay que correr `composer install` en cualquier entorno donde se despliegue este cambio**.
- `InventoryController`: se elimino `bulkForm()` (pagina `/inventory/bulk` ya no existe); `bulkStore()` ahora lee `quantity[product_id]` como arreglo asociativo (antes usaba arreglos paralelos `product_id[]`/`quantity[]`), acorde a la edicion inline de la tabla. El endpoint `adjust()` no cambio (ya soportaba delta positivo o negativo).
- `app/Views/inventory/index.php`: cada fila tiene un boton `+` que abre un modal (reutilizando el patron `.modal-overlay`/`.modal-box` ya usado en el picker de imagenes de Productos) para ajustar stock con cantidad con signo y motivo obligatorio, contra el endpoint `/inventory/adjust` existente. El boton "Ingreso masivo" ahora alterna un modo inline: la columna Stock se vuelve editable fila por fila dentro de un unico formulario que envia a `/inventory/bulk/store`, con un campo de motivo compartido. Se agrego un icono "Editar" por fila que enlaza a `/products/edit?id=X`, visible solo si el usuario tiene permiso `products.edit`.
- `app/Views/products/index.php`: boton "Actualizar inventario" (enlaza a `/inventory`, visible solo con permiso `inventory.view`), boton "Imprimir PDF" que abre un modal con un `<iframe>` apuntando a `/products/pdf` mas botones Descargar e Imprimir (usa `iframe.contentWindow.print()`), y los botones Editar/Eliminar ahora usan las clases `.button.info` (azul) y `.button.danger` (rojo).
- `ProductController::pdf()` genera un PDF real en servidor con Dompdf (tabla de productos: nombre, SKU, categoria, precio, stock, estado) y lo devuelve inline (`Content-Disposition: inline`) para que el iframe lo pueda previsualizar.
- Iconos nuevos agregados a `app/Helpers/icons.php`: `printer`, `download`.
- CSS nuevo en `public/assets/css/app.css`: `.button.info`, `.modal-sm`, `.modal-xl`, `.header-actions`, `.bulk-stock-bar`, `.inventory-table.bulk-mode`, `.pdf-frame`, con soporte responsive.

Validado por QA_ENGINEER:

- `php -l` sin errores en `InventoryController.php`, `ProductController.php`, `icons.php`, `inventory/index.php`, `products/index.php`, `public/index.php`.
- `GET /products/pdf` devuelve HTTP 200, `Content-Type: application/pdf`, PDF valido (verificado con `pdftotext`: 6 productos con nombre, SKU, categoria, precio y stock correctos).
- Capturas con Playwright (sesion real, no simulada) confirmando: modo ingreso masivo con inputs `+0` por fila y campo Motivo oculto por defecto (se detecto y corrigio un bug donde la barra de motivo aparecia visible desde el inicio por una regla CSS que pisaba el atributo `hidden`); modal de ajuste individual con producto, stock actual, cantidad y motivo; pantalla Productos con botones de color correctos.
- Prueba funcional end-to-end contra Apache real (no CLI): ajuste individual `-2` sobre un producto (19 -> 17, movimiento `ajuste_manual` auditado) e ingreso masivo sobre dos productos a la vez (`quantity[1]=5`, `quantity[2]=3`, ambos productos actualizados y auditados en una sola escritura). Datos de prueba limpiados y stock restaurado despues.
- El iframe de previsualizacion de PDF aparece en blanco en capturas de Chromium headless (Playwright); se confirmo que la causa es una limitacion del visor de PDF en modo headless y no un fallo real: el iframe solicita `/products/pdf`, recibe `200` con `content-type: application/pdf` y el `src` queda correctamente asignado. Pendiente de una revision visual del usuario en un navegador de escritorio normal.

### ORCHESTRATOR -> DATABASE_ENGINEER + BACKEND_ENGINEER + UI_UX_ENGINEER + FRONTEND_ENGINEER + SECURITY_ENGINEER + QA_ENGINEER

Fecha: 2026-07-09

Tarea (autorizada explicitamente por el usuario tras resolver 3 decisiones funcionales pendientes via preguntas dirigidas):

- Modal para el motivo de rechazo de un pedido (antes era un input siempre visible en la fila de acciones).
- Rediseño de la tabla `/orders`: codigo corto visual (PED-000001) sin tocar el codigo real, columnas separadas de documento/telefono-whatsapp/cantidad/producto (resumen "Producto A +N mas")/tipo de pago, sin romper Aprobar/Rechazar por pedido.
- Modulo nuevo de ticket/comprobante de venta (80mm termico), inexistente hasta ahora: se emite una sola vez por venta confirmada, queda guardado para reimprimir o reenviar por correo, y la accion aparece tanto en Pedidos (una vez aprobado) como en Ventas (fuente de verdad real).

Decisiones confirmadas por el usuario antes de implementar:

1. El codigo real del pedido (`PED-20260709-6DDBA26A`) no cambia; solo se agrega una etiqueta corta derivada del `id` en la tabla.
2. Un pedido con varios productos se sigue mostrando en una sola fila (resumen "Producto A +N mas" y cantidad total), sin romper el flujo de aprobacion por pedido.
3. El ticket pertenece a la venta (`sales`, codigo VEN-xxx) generada al aprobar, pero la accion de emitir/ver/reimprimir/enviar debe estar visible tanto en el pedido aprobado como en la venta.

Estado: completado.

HANDOFF aceptado por ORCHESTRATOR:

- Migracion `0029_add_sales_receipt.sql` agrega `receipt_file_id`, `receipt_issued_at`, `receipt_issued_by` a `sales` (aditivo, sin tocar `orders` ni datos existentes). Se respaldo `sales` en `sales_backup_pre0029` antes de aplicar.
- `ReceiptService` nuevo: genera el PDF del ticket (80mm, Dompdf, mismo patron que el PDF de Productos), lo guarda una sola vez vinculado a `files`/`sales.receipt_file_id` (idempotente: si ya existe, no se regenera), y envia por correo con adjunto reutilizando `SmtpService` + la cuenta remitente de `MAIL_NOTIFY_*` (mismo patron que `ContactNotifier`).
- `SalesController`: nuevos endpoints `POST /sales/receipt/issue`, `GET /sales/receipt/view`, `POST /sales/receipt/email`. Permisos: ver/reimprimir con `sales.view`, emitir/enviar con `sales.create` (no se creo un permiso nuevo en el catalogo).
- `OrderController::index()`/`show()` ahora exponen la venta vinculada (`sale_id`, `sale_code`, `sale_receipt_file_id`) via `LEFT JOIN sales`, para poder mostrar la accion de ticket en Pedidos sin duplicar logica de negocio.
- `orders/show.php`: boton "Rechazar" abre modal (mismo patron `.modal-overlay` ya usado 3 veces antes) en vez de input siempre visible; seccion de ticket condicional al estado `aprobado`.
- `orders/index.php`: columnas Pedido (codigo corto + real), Apellidos y nombres, DNI/RUC, Telefono/WhatsApp, Cantidad, Producto (resumen), Tipo de pago, Total, Estado, Fecha compra, Acciones (Ver + Emitir/Ticket condicional).
- `sales/index.php` y `sales/show.php`: misma accion de ticket (Emitir/Ver/Enviar por correo) que en Pedidos, para que ambos modulos muestren el mismo estado real.

Hallazgo de SECURITY_ENGINEER (corregido antes de QA):

- El campo oculto `redirect` usado para volver a la pagina de origen tras emitir/enviar el ticket permitia URLs absolutas sin validar, lo que habilitaba un *open redirect* (`Response::redirect()` no distingue rutas internas de URLs externas). Se agrego `SalesController::safeRedirect()`, que solo acepta rutas que empiecen con `/` y no con `//`, cayendo a un destino seguro por defecto en cualquier otro caso.

Validado por QA_ENGINEER (prueba real end-to-end, no simulada):

- `php -l` sin errores en todos los archivos PHP tocados.
- Se emitio el ticket de una venta real y aprobada existente (`VEN-20260709-9CE00264`, pedido `PED-20260709-1DEFD5BD`): `sales.receipt_file_id` quedo asignado, el archivo PDF se genero en disco (2203 bytes) y quedo registrado en `files`.
- `GET /sales/receipt/view?id=3` devuelve `200`, `Content-Type: application/pdf`; contenido verificado con `pdftotext`: nombre de la cooperativa, direccion, telefono, codigo de venta, cliente, documento, los 3 productos con cantidad/precio/subtotal correctos, total S/ 216.00, metodo de pago y numero de operacion.
- Envio de correo real probado contra `pjevsatipo1812@gmail.com` con el PDF adjunto; sin errores en logs; registrado en `activity_logs`.
- Capturas Playwright confirmando: modal de rechazo con el motivo requerido; tabla de Pedidos con las 11 columnas solicitadas y el boton "Ticket"/"Emitir" apareciendo solo cuando corresponde; seccion de ticket en el detalle del pedido aprobado; misma seccion de ticket en el detalle de la venta.
- Limitacion tecnica conocida (ya anticipada antes de implementar): Dompdf no soporta alto de pagina automatico, por lo que el PDF del ticket sale en 3 "paginas" con espacio en blanco en vez de una sola tira continua. El contenido es correcto; si al imprimir en una impresora termica real se nota el salto, hay que ajustar la altura fija de `ReceiptService::generate()` (actualmente 1600pt).

## Siguiente paso

- Ajustar la altura fija del PDF del ticket si al probar en una impresora termica de 80mm real se nota el salto de "pagina" en blanco.
- SECURITY_ENGINEER debe revisar carga de voucher, CSRF publico, validaciones y superficie de carrito.
- QA_ENGINEER debe ejecutar prueba funcional manual/end-to-end en navegador real: agregar producto, checkout con voucher (incluyendo el nuevo selector de ubigeo), pedido en dashboard, aprobacion, venta y stock.
- Aplicar `database/migrations/0028_expand_ubigeo_catalog.sql`, `database/migrations/0029_add_sales_receipt.sql` y `database/seeders/import_ubigeo.php` en el servidor de produccion cuando se despliegue (en local ya estan aplicados y verificados).
- Eliminar `ubigeo_backup_pre0028` y `sales_backup_pre0029` de la base local una vez confirmado que los cambios no rompieron nada (se dejaron como respaldo temporal).
- Correr `composer install` en cualquier entorno (staging/produccion) antes de desplegar el modulo de Inventario/Productos, para que se instale `dompdf/dompdf`.
- El usuario debe confirmar visualmente en un navegador de escritorio (no headless) que el PDF se previsualiza correctamente dentro del modal de Productos.
