<?php
$errors = errors();
$sources = ['whatsapp' => 'WhatsApp', 'phone' => 'Telefono', 'manual' => 'Manual'];
$productsJson = json_encode(array_map(fn ($p) => [
    'id' => (int)$p['id'],
    'name' => $p['name'],
    'sku' => $p['sku'],
    'presentation' => $p['presentation'],
    'stock' => $p['stock'] === null ? null : (int)$p['stock'],
    'price' => (float)($p['sale_price'] ?? $p['price']),
], $products), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

<section class="page-card">
    <div class="page-header">
        <div>
            <h2>Nueva venta manual</h2>
            <span>Registra ventas confirmadas por WhatsApp, telefono o atencion interna. El stock se descuenta al guardar.</span>
        </div>
        <a class="button ghost" href="<?= e(url('/sales')) ?>"><?= icon('arrow-left') ?> Volver</a>
    </div>

    <?php if ($errors): ?>
        <div class="alert alert-error"><?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?></div>
    <?php endif; ?>

    <form action="<?= e(url('/sales/store')) ?>" method="post" enctype="multipart/form-data" class="manual-sale-form" id="manualSaleForm" data-identity-url="<?= e(url('/identity/lookup')) ?>">
        <?= csrf_field() ?>
        <div class="manual-sale-grid">
            <div class="manual-sale-main">
                <article class="detail-panel">
                    <h3>Comprador</h3>
                    <div class="payment-method-form">
                        <label>Origen
                            <select name="source" required>
                                <?php foreach ($sources as $value => $label): ?>
                                    <option value="<?= e($value) ?>" <?= old('source', 'whatsapp') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Documento
                            <select name="document_type" id="manualDocumentType" required>
                                <option value="DNI" <?= old('document_type', 'DNI') === 'DNI' ? 'selected' : '' ?>>DNI</option>
                                <option value="RUC" <?= old('document_type') === 'RUC' ? 'selected' : '' ?>>RUC</option>
                            </select>
                        </label>
                        <label>Numero documento
                            <span class="identity-lookup-field">
                                <input type="text" name="document_number" id="manualDocumentNumber" value="<?= e(old('document_number')) ?>" required inputmode="numeric">
                                <button type="button" class="button small identity-lookup-btn" id="manualIdentityLookupBtn">Buscar</button>
                            </span>
                            <small class="identity-lookup-status" id="manualIdentityLookupStatus" aria-live="polite"></small>
                        </label>
                        <label>Nombre / razon social
                            <input type="text" name="customer_name" id="manualCustomerName" value="<?= e(old('customer_name')) ?>" required>
                        </label>
                        <label>Celular
                            <input type="tel" name="phone" value="<?= e(old('phone')) ?>">
                        </label>
                        <label>WhatsApp
                            <input type="tel" name="whatsapp" value="<?= e(old('whatsapp')) ?>" required>
                        </label>
                        <label class="span-2">Correo
                            <input type="email" name="email" value="<?= e(old('email')) ?>">
                        </label>
                    </div>
                </article>

                <article class="detail-panel">
                    <div class="card-title-row">
                        <div>
                            <h3>Productos vendidos</h3>
                            <p>Agrega uno o varios productos. El precio se carga desde catalogo y puede ajustarse si corresponde.</p>
                        </div>
                        <button type="button" class="button small" id="addSaleItem"><?= icon('plus') ?> Agregar</button>
                    </div>
                    <div class="manual-sale-items" id="saleItems"></div>
                </article>

                <article class="detail-panel">
                    <h3>Pago y voucher</h3>
                    <div class="payment-method-form">
                        <label>Metodo de pago
                            <select name="payment_method" required>
                                <option value="">Selecciona metodo</option>
                                <?php foreach ($paymentMethods as $method): ?>
                                    <option value="<?= e($method['name']) ?>" <?= old('payment_method') === $method['name'] ? 'selected' : '' ?>><?= e($method['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label>Numero de operacion
                            <input type="text" name="payment_operation_number" value="<?= e(old('payment_operation_number')) ?>" required>
                        </label>
                        <label class="span-2">Voucher de pago
                            <input type="file" name="voucher" accept="image/jpeg,image/png,image/webp,application/pdf" required>
                            <small>Obligatorio para dejar constancia de pago.</small>
                        </label>
                    </div>
                </article>
            </div>

            <aside class="manual-sale-summary">
                <div class="checkout-summary-card">
                    <span>Resumen</span>
                    <div class="manual-sale-summary-lines" id="saleSummaryLines">
                        <p class="text-muted">Agrega productos para calcular el total.</p>
                    </div>
                    <div class="checkout-total"><span>Total</span><strong id="saleGrandTotal">S/ 0.00</strong></div>
                    <p>Al guardar, la venta quedara confirmada y el inventario se descontara automaticamente.</p>
                    <button class="button primary" type="submit"><?= icon('save') ?> Registrar venta</button>
                </div>
            </aside>
        </div>
    </form>
</section>

<template id="saleItemTemplate">
    <div class="manual-sale-item">
        <label>Producto
            <select name="product_id[]" class="sale-product" required>
                <option value="">Selecciona producto</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?= (int)$product['id'] ?>">
                        <?= e($product['name']) ?><?= $product['stock'] !== null ? ' - Stock: ' . (int)$product['stock'] : '' ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>Cantidad
            <input type="number" name="quantity[]" class="sale-quantity" min="1" value="1" required>
        </label>
        <label>Precio unitario
            <input type="number" name="unit_price[]" class="sale-price" step="0.01" min="0.01" required>
        </label>
        <div class="sale-line-total">
            <span>Subtotal</span>
            <strong>S/ 0.00</strong>
        </div>
        <button type="button" class="button small danger sale-remove">Quitar</button>
    </div>
</template>

<script>
window.MANUAL_SALE_PRODUCTS = <?= $productsJson ?: '[]' ?>;
</script>
