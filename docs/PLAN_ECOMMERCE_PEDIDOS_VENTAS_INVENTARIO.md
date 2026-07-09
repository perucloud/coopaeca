# Plan de Implementacion: Pedidos, Ventas, Carrito e Inventario

## Objetivo

Implementar un flujo profesional de compra web para COOPAECA, manteniendo WhatsApp como canal de comunicacion comercial, pero registrando formalmente la operacion dentro del sistema mediante pedido, voucher de pago, validacion administrativa, venta confirmada e inventario sincronizado.

El sistema debe permitir:

- Comprar uno o varios productos desde el landing page.
- Generar un pedido con codigo unico.
- Registrar datos del comprador, direccion, metodo de pago, voucher y numero de operacion.
- Enviar por WhatsApp el resumen del pedido y voucher como parte de la coordinacion.
- Validar la compra desde el dashboard.
- Crear una venta solo cuando el pago sea aprobado.
- Descontar stock solo cuando la venta sea confirmada.
- Mantener historial de movimientos de inventario.

## Principio Principal

WhatsApp no debe ser el sistema de venta. WhatsApp sera un canal de comunicacion.

La venta real debe quedar registrada en el sistema.

El stock no debe descontarse por:

- Hacer clic en Comprar por WhatsApp.
- Agregar productos al carrito.
- Crear un pedido.
- Subir un voucher.

El stock debe descontarse unicamente cuando el administrador aprueba el pedido y confirma el pago.

## Alcance General

### Dashboard Administrativo

Se implementaran tres modulos principales:

1. Pedidos
2. Ventas
3. Stock / Inventario

### Landing Page

Se implementara un flujo de compra publica:

1. Boton Anadir al carrito.
2. Boton Comprar por WhatsApp como canal de comunicacion.
3. Carrito de compras.
4. Checkout por pasos.
5. Registro de comprador.
6. Seleccion de metodo de pago.
7. Subida obligatoria de voucher.
8. Confirmacion final.
9. Envio del resumen por WhatsApp.

## Modulo Pedidos

El modulo Pedidos recibe todo lo que el cliente genera desde la web.

Un pedido representa una intencion formal de compra, pero todavia no es una venta definitiva.

### Datos Del Pedido

- Codigo unico de pedido.
- Fecha de creacion.
- Origen: web, WhatsApp, telefono, manual.
- Estado.
- Nombre o razon social del comprador.
- Tipo de documento: DNI o RUC.
- Numero de documento.
- Celular.
- WhatsApp.
- Correo electronico.
- Region.
- Provincia.
- Distrito.
- Direccion exacta.
- Productos.
- Presentacion del producto.
- Cantidades.
- Precios unitarios.
- Subtotal.
- Total.
- Metodo de pago.
- Numero de operacion.
- Voucher de pago.
- Observaciones del cliente.
- Observaciones internas del administrador.

### Estados Del Pedido

- pendiente: pedido creado, todavia sin voucher completo.
- voucher_enviado: cliente adjunto voucher y datos de pago.
- en_revision: administrador esta validando.
- aprobado: pago validado y pedido aceptado.
- rechazado: pago no valido o informacion incorrecta.
- cancelado: pedido anulado antes de aprobar.

### Acciones Administrativas

- Ver detalle del pedido.
- Ver voucher.
- Abrir conversacion de WhatsApp.
- Marcar en revision.
- Aprobar pedido.
- Rechazar pedido.
- Cancelar pedido.
- Agregar observacion interna.

### Reglas Del Modulo Pedidos

- Un pedido puede tener varios productos.
- No se debe descontar stock al crear el pedido.
- No se debe descontar stock al subir voucher.
- Antes de aprobar, se debe validar stock disponible.
- Al aprobar, se debe crear una venta y generar movimientos de inventario.
- No se debe permitir aprobar un pedido si algun producto no tiene stock suficiente.

## Modulo Ventas

El modulo Ventas registra operaciones confirmadas.

Una venta puede nacer desde:

- Pedido aprobado desde la web.
- Venta manual registrada por el administrador.
- Venta coordinada por WhatsApp.
- Venta por telefono.

### Datos De Venta

- Codigo unico de venta.
- Codigo de pedido relacionado, si existe.
- Fecha de venta.
- Origen: web, WhatsApp, telefono, manual.
- Comprador.
- DNI o RUC.
- Celular.
- WhatsApp.
- Correo electronico.
- Producto.
- Presentacion.
- Cantidad.
- Precio unitario.
- Monto total.
- Metodo de pago.
- Numero de operacion.
- Voucher de pago.
- Usuario administrador que aprobo la venta.
- Estado de venta.

### Estados De Venta

- confirmada.
- anulada.
- entregada.

### Reglas Del Modulo Ventas

- Una venta confirmada debe descontar inventario.
- Una venta anulada debe poder revertir inventario si ya fue descontado.
- Toda venta debe tener trazabilidad hacia el pedido o hacia el usuario que la registro manualmente.
- La venta no debe modificar directamente productos sin generar movimiento de inventario.

## Modulo Stock / Inventario

El modulo Stock / Inventario debe sincronizarse con productos y ventas.

El producto puede seguir mostrando un campo de stock actual, pero el control profesional debe estar basado en movimientos.

### Vista De Inventario

Por cada producto:

- Producto.
- SKU.
- Presentacion.
- Precio normal.
- Precio oferta.
- Stock actual.
- Estado del producto.
- Ultimo movimiento.
- Acciones.

### Movimientos De Inventario

Tipos de movimiento:

- entrada_inicial.
- entrada_manual.
- salida_venta.
- ajuste_manual.
- anulacion_venta.
- devolucion.

Cada movimiento debe registrar:

- Producto.
- Tipo de movimiento.
- Cantidad.
- Stock anterior.
- Stock nuevo.
- Referencia: pedido o venta.
- Usuario responsable.
- Fecha.
- Observacion.

### Reglas De Inventario

- No permitir stock negativo.
- Toda salida por venta debe estar asociada a una venta confirmada.
- Todo ajuste manual debe guardar usuario y observacion.
- El historial de movimientos no debe eliminarse.
- Si se anula una venta, el stock debe revertirse mediante un nuevo movimiento, no borrando el anterior.

## Landing Page: Flujo De Compra

### Producto

En la pagina de detalle de producto deben existir dos acciones:

- Anadir al carrito.
- Comprar por WhatsApp.

### Anadir Al Carrito

El cliente puede:

- Agregar varios productos.
- Definir cantidades distintas.
- Ver subtotal por producto.
- Ver total general.
- Eliminar productos.
- Actualizar cantidades.

### Comprar Por WhatsApp

El boton debe comunicar claramente que WhatsApp es para coordinacion.

Flujo recomendado:

1. Si el producto no esta en carrito, se agrega al carrito con la cantidad seleccionada.
2. Se abre un modal indicando que para generar el pedido debe completar el checkout.
3. Al finalizar checkout, el sistema genera mensaje de WhatsApp con resumen del pedido.

WhatsApp no reemplaza el pedido.

## Checkout Publico

El checkout debe ser por pasos para mantener una experiencia profesional.

### Paso 1: Carrito

- Lista de productos.
- Cantidades.
- Precio unitario.
- Subtotal.
- Total.
- Boton continuar.

### Paso 2: Datos Del Comprador

Campos:

- Tipo de documento: DNI o RUC.
- Numero de documento.
- Nombre completo o razon social.
- Celular.
- WhatsApp.
- Correo electronico.

Futuras integraciones:

- API RENIEC para DNI.
- API SUNAT/RUC para RUC.

La primera version debe permitir carga manual aunque no exista API activa.

### Paso 3: Direccion

Campos:

- Region.
- Provincia.
- Distrito.
- Direccion exacta.
- Referencia opcional.

Recomendacion tecnica:

- Usar tabla local de ubigeo.
- No depender de consultas externas para cargar region, provincia y distrito.

### Paso 4: Metodo De Pago

Opciones iniciales:

- Transferencia bancaria.
- Yape.
- Plin.
- Otro.

Campos:

- Metodo de pago.
- Numero de operacion.
- Imagen de voucher.

El voucher debe ser obligatorio para completar el pedido.

### Paso 5: Confirmacion

Antes de crear el pedido, mostrar modal de confirmacion con:

- Datos del comprador.
- Documento.
- Contacto.
- Direccion.
- Productos.
- Cantidades.
- Total.
- Metodo de pago.
- Numero de operacion.

El cliente debe confirmar que los datos son correctos.

### Paso 6: Pedido Generado

Luego de confirmar:

- Crear pedido.
- Generar codigo unico.
- Guardar voucher.
- Mostrar pantalla de exito.
- Mostrar codigo de pedido.
- Mostrar boton para enviar resumen por WhatsApp.

El mensaje de WhatsApp debe incluir:

- Codigo de pedido.
- Nombre del comprador.
- Documento.
- Productos.
- Cantidades.
- Total.
- Metodo de pago.
- Numero de operacion.
- Indicacion de que el voucher fue adjuntado en el sistema.

## Base De Datos Propuesta

### orders

- id
- code
- source
- status
- customer_name
- document_type
- document_number
- phone
- whatsapp
- email
- region
- province
- district
- address
- address_reference
- payment_method
- payment_operation_number
- voucher_file_id
- subtotal
- total
- customer_notes
- admin_notes
- approved_by
- approved_at
- rejected_at
- cancelled_at
- created_at
- updated_at

### order_items

- id
- order_id
- product_id
- product_name
- product_sku
- presentation
- quantity
- unit_price
- subtotal
- created_at

### sales

- id
- code
- order_id
- source
- status
- customer_name
- document_type
- document_number
- phone
- whatsapp
- email
- payment_method
- payment_operation_number
- voucher_file_id
- subtotal
- total
- confirmed_by
- confirmed_at
- cancelled_by
- cancelled_at
- created_at
- updated_at

### sale_items

- id
- sale_id
- product_id
- product_name
- product_sku
- presentation
- quantity
- unit_price
- subtotal
- created_at

### stock_movements

- id
- product_id
- movement_type
- quantity
- stock_before
- stock_after
- reference_type
- reference_id
- notes
- created_by
- created_at

### payment_methods

- id
- name
- type
- account_label
- account_number
- holder_name
- instructions
- qr_image_id
- is_active
- position
- created_at
- updated_at

### ubigeo

- id
- department_code
- department_name
- province_code
- province_name
- district_code
- district_name

## Permisos Propuestos

Agregar permisos:

- orders.view
- orders.edit
- orders.approve
- orders.reject
- sales.view
- sales.create
- sales.cancel
- inventory.view
- inventory.adjust
- payment_methods.view
- payment_methods.edit

## Seguridad

### Voucher

- Permitir JPG, PNG, WebP y PDF.
- Limitar tamano maximo.
- Guardar en carpeta controlada.
- Registrar el archivo en tabla files o tabla equivalente.
- No confiar en el nombre original del archivo.

### Checkout Publico

- Validar CSRF si aplica al formulario.
- Validar cantidad solicitada.
- Validar stock disponible antes de crear pedido.
- Revalidar stock antes de aprobar pedido.
- Sanitizar datos de cliente.
- Registrar IP del pedido.

### Validaciones Criticas

- DNI: 8 digitos.
- RUC: 11 digitos.
- Celular/WhatsApp: formato valido.
- Correo: formato valido si se ingresa.
- Voucher obligatorio.
- Numero de operacion obligatorio.
- Metodo de pago obligatorio.
- Cantidad mayor a cero.

## Fases De Implementacion

### Fase 1: Arquitectura Y Migraciones

- Definir tablas finales.
- Crear migraciones.
- Crear permisos.
- Definir estados y constantes.
- Preparar almacenamiento de vouchers.

Entregable:

- Migraciones listas.
- Modelo de datos estable.

### Fase 2: Modulo Pedidos En Dashboard

- Listado de pedidos.
- Filtros por estado.
- Detalle de pedido.
- Visualizacion de voucher.
- Acciones: revisar, aprobar, rechazar, cancelar.

Entregable:

- Administrador puede gestionar pedidos.

### Fase 3: Carrito Publico

- Boton Anadir al carrito.
- Carrito persistente.
- Actualizar cantidades.
- Eliminar productos.
- Total general.

Entregable:

- Cliente puede armar un carrito con multiples productos.

### Fase 4: Checkout Publico

- Formulario por pasos.
- Datos del comprador.
- Direccion.
- Metodo de pago.
- Voucher.
- Confirmacion final.
- Creacion de pedido.

Entregable:

- Cliente puede generar pedido completo desde la web.

### Fase 5: WhatsApp Integrado Al Pedido

- Mensaje automatico con codigo de pedido.
- Resumen de compra.
- Boton de envio posterior a creacion de pedido.

Entregable:

- WhatsApp queda como comunicacion, no como sistema de venta.

### Fase 6: Aprobacion, Venta Y Stock

- Aprobar pedido.
- Validar stock.
- Crear venta.
- Crear sale_items.
- Descontar stock.
- Crear movimientos de inventario.

Entregable:

- Pedido aprobado genera venta e inventario actualizado.

### Fase 7: Modulo Ventas

- Listado de ventas.
- Detalle de venta.
- Filtros.
- Voucher.
- Origen.
- Anulacion controlada.

Entregable:

- Registro comercial definitivo.

### Fase 8: Modulo Inventario

- Vista de stock por producto.
- Historial de movimientos.
- Ajustes manuales.
- Entradas.
- Salidas.

Entregable:

- Inventario auditable.

### Fase 9: Metodos De Pago

- Gestion de metodos de pago.
- QR de Yape/Plin.
- Datos de cuenta bancaria.
- Instrucciones visibles en checkout.

Entregable:

- Administrador puede configurar medios de pago.

### Fase 10: Integraciones Externas

- DNI RENIEC.
- RUC SUNAT.
- Ubigeo local.

Entregable:

- Autocompletado de datos cuando exista API configurada.

### Fase 11: QA Y Pruebas

Casos minimos:

- Compra de un producto.
- Compra de multiples productos.
- Pedido sin voucher no debe completarse.
- Pedido con voucher se crea correctamente.
- Aprobacion descuenta stock.
- Rechazo no descuenta stock.
- No aprobar si no hay stock.
- Anulacion revierte stock mediante movimiento.
- Responsive en movil.
- Flujo en espanol e ingles.

## Agentes / Frentes De Trabajo

### Agente Arquitectura DB

Responsable de:

- Migraciones.
- Relaciones.
- Indices.
- Estados.
- Reglas de integridad.

### Agente Backend

Responsable de:

- Controladores.
- Validaciones.
- Servicios de negocio.
- Aprobacion de pedidos.
- Creacion de ventas.
- Movimientos de stock.

### Agente Dashboard UI/UX

Responsable de:

- Modulo Pedidos.
- Modulo Ventas.
- Modulo Inventario.
- Estados visuales.
- Acciones administrativas.

### Agente Landing UI/UX

Responsable de:

- Carrito.
- Checkout por pasos.
- Modales.
- Resumen de compra.
- Experiencia responsive.

### Agente Pagos Y Voucher

Responsable de:

- Metodos de pago.
- Upload de voucher.
- Validaciones de archivo.
- Visualizacion administrativa.

### Agente QA

Responsable de:

- Pruebas de flujo completo.
- Pruebas de stock.
- Pruebas de aprobacion/rechazo.
- Pruebas responsive.
- Verificacion de seguridad.

## Decisiones Pendientes

- Confirmar si las ventas manuales se implementan desde la primera version o en una fase posterior.
- Confirmar si el checkout permitira compras sin stock ilimitado cuando el producto tenga stock NULL.
- Confirmar si el voucher aceptara PDF desde la primera fase o solo imagenes.
- Confirmar proveedor de API RENIEC.
- Confirmar proveedor de API RUC.
- Confirmar si se usara tabla local de ubigeo desde la primera version.
- Confirmar si habra envio de correo automatico al cliente.
- Confirmar si el pedido tendra expiracion automatica.

## Recomendacion De Implementacion

Implementar primero el flujo minimo robusto:

1. Migraciones.
2. Pedidos.
3. Carrito.
4. Checkout con voucher.
5. Aprobacion.
6. Venta.
7. Descuento de stock.

Luego implementar:

- Inventario avanzado.
- Metodos de pago administrables.
- DNI/RUC automatico.
- Ubigeo local.
- Reportes.

Esta secuencia reduce riesgo y permite probar el nucleo de negocio antes de agregar integraciones externas.
