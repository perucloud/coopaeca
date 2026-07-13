# Flujo de emision y entrega de comprobantes

## Alcance

Este documento describe el flujo implementado para los pedidos recibidos desde el landing page. Al aprobar un voucher, el sistema confirma la venta, emite un ticket PDF e intenta enviarlo automaticamente por correo. WhatsApp queda como una accion manual y opcional del administrador.

El documento generado es un ticket o recibo interno de venta. **No es una boleta o factura electronica integrada con SUNAT** y no reemplaza un comprobante fiscal cuando este sea legalmente exigible.

## Flujo de aprobacion

1. Un usuario con permiso `orders.approve` confirma la aprobacion del pedido.
2. `OrderService::approve()` abre una transaccion de base de datos y bloquea el pedido para procesarlo.
3. Dentro de la transaccion se valida el estado, se verifica el stock, se crea la venta y sus items, se descuenta inventario y se marca el pedido como aprobado.
4. La transaccion se confirma antes de iniciar cualquier entrega externa.
5. Ya fuera de la transaccion, `ReceiptDeliveryService::automaticEmail()`:
   - crea el intento auditable de correo;
   - emite el PDF mediante `ReceiptService::ensureIssued()` o reutiliza el ya emitido;
   - vincula el archivo al intento;
   - valida el correo del cliente;
   - intenta enviar el PDF adjunto;
   - registra el resultado como `sent` o `failed`.
6. La pantalla del pedido informa el resultado y conserva acciones para consultar, descargar o reenviar el documento.

La entrega se ejecuta despues del commit deliberadamente. Una falla de correo o de generacion posterior no revierte la venta, no repone stock y no vuelve a procesar el pedido. El administrador puede reintentar desde el detalle.

## Emision y reutilizacion

`ReceiptService::ensureIssued()` es idempotente: si `sales.receipt_file_id` ya identifica un comprobante existente, lo reutiliza. Si no existe, genera el PDF, registra el archivo en `files` y actualiza:

- `sales.receipt_file_id`
- `sales.receipt_issued_at`
- `sales.receipt_issued_by`

El voucher de pago y el comprobante son documentos distintos. Ambos permanecen asociados a la operacion y se sirven mediante controladores autenticados; no se debe publicar su ruta fisica.

## Canales de entrega

### Correo electronico

- El primer intento ocurre automaticamente al aprobar el pedido.
- Si el correo falta o no tiene formato valido, la emision del PDF se conserva y el intento queda como `failed`.
- Un administrador puede indicar un correo valido y reenviar manualmente el mismo PDF.
- Una falla SMTP no invalida la venta; queda registrada para diagnostico y reintento.

### WhatsApp

- WhatsApp es exclusivamente manual y opcional.
- El sistema valida y normaliza el numero, asegura que el PDF exista y abre `wa.me` con un mensaje preparado.
- El administrador debe descargar o abrir el comprobante, adjuntarlo en la conversacion y pulsar Enviar.
- El historial usa el estado `prepared`, no `sent`: abrir WhatsApp Web no proporciona confirmacion de entrega al sistema.
- No existe envio automatico ni integracion con una API de WhatsApp en este flujo.

## Historial de entregas (migracion 0032)

La migracion `database/migrations/0032_create_receipt_deliveries.sql` crea `receipt_deliveries` con:

- Referencias a venta, archivo y usuario iniciador.
- Canal: `email` o `whatsapp`.
- Modalidad: `automatic` o `manual`.
- Proposito: `initial` o `resend`.
- Estado: `pending`, `prepared`, `sent` o `failed`.
- Destinatario, mensaje de error y marcas de tiempo.

La restriccion `chk_receipt_deliveries_channel_mode` impide registrar WhatsApp como automatico. Los registros de una venta se eliminan en cascada si se elimina la venta; la referencia al archivo o usuario admite `NULL` para conservar el historial restante.

Antes de desplegar este flujo se debe aplicar la migracion 0032 despues de las migraciones de ecommerce y comprobantes (`0026` y `0029`).

## Rutas y permisos

Todas las rutas siguientes pertenecen al grupo privado con `AuthMiddleware` y `CsrfMiddleware`.

| Metodo | Ruta | Permiso | Funcion |
|---|---|---|---|
| `POST` | `/orders/approve` | `orders.approve` | Aprueba pedido, confirma venta e inicia emision/correo automaticos |
| `POST` | `/orders/receipt/email` | `orders.approve` | Reenvia el comprobante por correo y registra el resultado |
| `POST` | `/orders/receipt/whatsapp` | `orders.approve` | Prepara manualmente la conversacion de WhatsApp |
| `GET` | `/orders/voucher/view` | `orders.view` | Muestra el voucher mediante streaming controlado |
| `GET` | `/orders/receipt/view` | `orders.view` | Muestra el comprobante en linea |
| `GET` | `/orders/receipt/download` | `orders.view` | Descarga el comprobante |
| `POST` | `/sales/receipt/issue` | `sales.create` | Emite manualmente un comprobante pendiente |
| `GET` | `/sales/receipt/view` | `sales.view` | Muestra el comprobante desde Ventas |
| `GET` | `/sales/voucher/view` | `sales.view` | Muestra el voucher desde Ventas |
| `POST` | `/sales/receipt/email` | `sales.create` | Reenvia el comprobante desde Ventas |

Las operaciones que cambian estado son `POST` y requieren CSRF. Las consultas resuelven la venta a partir del pedido en el servidor; no aceptan una ruta arbitraria de archivo aportada por el cliente.

## Seguridad de documentos

- `SecureDocumentService` valida el registro de `files`, el tipo esperado (`voucher` o `receipt`) y transmite el archivo con cabeceras de visualizacion o descarga.
- `public/uploads/receipts/.htaccess` contiene reglas Apache 2.4 y 2.2 para denegar acceso HTTP directo. El PDF debe consultarse a traves de las rutas autenticadas.
- Los errores tecnicos de correo se registran con correos y posibles credenciales/tokens saneados. La interfaz recibe un mensaje operativo sin secretos.
- El historial contiene datos de contacto; debe tratarse como informacion personal y no exponerse fuera del dashboard autorizado.

### Despliegue con Nginx

Nginx no interpreta `.htaccess`. En ese servidor se debe agregar una regla equivalente y comprobar que el acceso directo responda `403` o `404`, por ejemplo:

```nginx
location ^~ /uploads/receipts/ {
    deny all;
    return 404;
}
```

La misma proteccion debe aplicarse a cualquier directorio publico donde se almacenen vouchers. Como defensa adicional, en una evolucion futura se recomienda mover documentos privados fuera del document root.

## Interfaz administrativa

`/orders` muestra accesos diferenciados para pedido, voucher, venta y comprobante, ademas del estado del ultimo intento. `/orders/show` agrega:

- estado de emision;
- visualizacion y descarga;
- reenvio manual por correo;
- preparacion manual de WhatsApp;
- historial con canal, modalidad, destinatario, estado, administrador y detalle del error.

La pantalla de aprobacion advierte que el correo es automatico y WhatsApp manual, y que una falla de entrega no revierte la venta.

## Pruebas de aceptacion

Verificar al desplegar, usando datos ficticios y sin registrar informacion personal en evidencias:

1. Aplicar `0032_create_receipt_deliveries.sql` y confirmar sus claves foraneas, indices y restriccion de canal/modalidad.
2. Aprobar un pedido con correo valido: se crea una sola venta, el stock baja una sola vez, se emite un PDF y el intento inicial termina en `sent` cuando SMTP esta disponible.
3. Aprobar con correo vacio o invalido: la venta y el PDF permanecen; el intento termina en `failed` y aparece la opcion manual de WhatsApp.
4. Simular falla SMTP: no se revierte la venta y el error publico no revela configuracion sensible.
5. Reenviar por correo: se reutiliza `receipt_file_id`, se agrega un registro `manual/resend` y no se genera otra venta.
6. Preparar WhatsApp: se abre `wa.me`, el historial registra `manual/resend/prepared` y el sistema no afirma que fue enviado.
7. Confirmar permisos: un usuario con solo vista consulta voucher/comprobante, pero no aprueba ni reenvia.
8. Confirmar CSRF en los `POST` y rechazo de solicitudes no autenticadas.
9. Solicitar directamente un archivo bajo `/uploads/receipts/`: debe responder `403` o `404`; las rutas autorizadas deben seguir transmitiendolo.
10. Repetir o recargar acciones para comprobar que no se duplica la venta, el descuento de inventario ni el PDF emitido.

## Limitaciones y pendientes

- No hay confirmacion automatica de que el administrador adjunto o envio el PDF por WhatsApp.
- El correo es sincrono durante la solicitud posterior a la aprobacion; una cola de trabajos seria recomendable si aumenta el volumen.
- El PDF generado por Dompdf usa una altura fija; debe validarse en la impresora termica real para evitar cortes o paginas innecesarias.
- No existe integracion SUNAT, numeracion fiscal, firma digital, XML tributario, CDR ni consulta de estado fiscal.
