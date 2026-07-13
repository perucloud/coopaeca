<?php
$activeLandingNav = 'productos';
require __DIR__ . '/partials/header.php';
$lang = landing_lang();
$isEn = $lang === 'en';
$errors = errors();
$oldItems = old('items', '');
$departments = $departments ?? [];
$hasUbigeo = !empty($departments);
?>

<section class="checkout-hero">
    <div class="lp-container">
        <span class="lp-tag"><?= e($isEn ? 'Secure order' : 'Pedido seguro') ?></span>
        <h1><?= e($isEn ? 'Complete your order' : 'Finalizar compra') ?></h1>
        <p><?= e($isEn ? 'Register your purchase with voucher and order code. WhatsApp remains available for coordination.' : 'Registra tu compra con voucher y codigo de pedido. WhatsApp queda disponible para coordinacion.') ?></p>
    </div>
</section>

<section class="checkout-page">
    <div class="lp-container checkout-grid">
        <form class="checkout-form" id="checkoutForm" action="<?= e(lurl('/checkout')) ?>" method="post" enctype="multipart/form-data" data-identity-url="<?= e(lurl('/identity/lookup')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="items" id="checkoutItems" value="<?= e((string)$oldItems) ?>">

            <?php if ($errors): ?>
                <div class="lp-alert lp-alert-error checkout-alert">
                    <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="checkout-steps">
                <div class="checkout-step is-active" data-step-label="1"><?= e($isEn ? 'Cart' : 'Carrito') ?></div>
                <div class="checkout-step" data-step-label="2"><?= e($isEn ? 'Customer' : 'Comprador') ?></div>
                <div class="checkout-step" data-step-label="3"><?= e($isEn ? 'Payment' : 'Pago') ?></div>
                <div class="checkout-step" data-step-label="4"><?= e($isEn ? 'Confirm' : 'Confirmar') ?></div>
            </div>

            <div class="checkout-card" data-checkout-panel="1">
                <div class="checkout-card-head">
                    <div>
                        <span><?= e($isEn ? 'Step 1' : 'Paso 1') ?></span>
                        <h2><?= e($isEn ? 'Selected products' : 'Productos seleccionados') ?></h2>
                    </div>
                    <a href="<?= e(lurl('/#productos')) ?>" class="checkout-link"><?= e($isEn ? 'Add more' : 'Agregar mas') ?></a>
                </div>
                <div id="checkoutCartList" class="checkout-cart-list"></div>
                <div class="checkout-empty" id="checkoutEmpty">
                    <strong><?= e($isEn ? 'Your cart is empty' : 'Tu carrito esta vacio') ?></strong>
                    <p><?= e($isEn ? 'Choose products before creating an order.' : 'Selecciona productos antes de generar un pedido.') ?></p>
                    <a href="<?= e(lurl('/#productos')) ?>" class="lp-btn lp-btn-primary"><?= e($isEn ? 'View products' : 'Ver productos') ?></a>
                </div>
            </div>

            <div class="checkout-card" data-checkout-panel="2">
                <div class="checkout-card-head">
                    <div>
                        <span><?= e($isEn ? 'Step 2' : 'Paso 2') ?></span>
                        <h2><?= e($isEn ? 'Customer information' : 'Datos del comprador') ?></h2>
                    </div>
                </div>
                <div class="checkout-fields two">
                    <label>Tipo de documento
                        <select name="document_type" id="documentType" required>
                            <option value="DNI" <?= old('document_type', 'DNI') === 'DNI' ? 'selected' : '' ?>>DNI</option>
                            <option value="RUC" <?= old('document_type') === 'RUC' ? 'selected' : '' ?>>RUC</option>
                        </select>
                    </label>
                    <label><?= e($isEn ? 'Document number' : 'Numero de documento') ?>
                        <span class="identity-lookup-field">
                            <input type="text" name="document_number" id="documentNumber" value="<?= e(old('document_number')) ?>" required inputmode="numeric" autocomplete="off">
                            <button type="button" class="identity-lookup-btn" id="identityLookupBtn"><?= e($isEn ? 'Search' : 'Buscar') ?></button>
                        </span>
                        <small class="identity-lookup-status" id="identityLookupStatus" aria-live="polite"></small>
                    </label>
                    <label class="span-2"><?= e($isEn ? 'Full name or business name' : 'Nombres completos o razon social') ?>
                        <input type="text" name="customer_name" id="customerName" value="<?= e(old('customer_name')) ?>" required autocomplete="name">
                    </label>
                    <label><?= e($isEn ? 'Phone' : 'Celular') ?>
                        <input type="tel" name="phone" value="<?= e(old('phone')) ?>" autocomplete="tel">
                    </label>
                    <label>WhatsApp
                        <input type="tel" name="whatsapp" value="<?= e(old('whatsapp')) ?>" required autocomplete="tel">
                    </label>
                    <label class="span-2"><?= e($isEn ? 'Email' : 'Correo electronico') ?>
                        <input type="email" name="email" value="<?= e(old('email')) ?>" autocomplete="email">
                    </label>
                    <label class="span-2"><?= e($isEn ? 'Address (fiscal / home)' : 'Direccion (fiscal o domicilio)') ?>
                        <input type="text" name="address" id="checkoutAddress" value="<?= e(old('address')) ?>" required>
                        <small><?= e($isEn ? 'Autofilled from RUC when available. You can edit it.' : 'Se autocompleta con el RUC cuando esta disponible. Puedes editarla.') ?></small>
                    </label>
                </div>
            </div>

            <div class="checkout-card" data-checkout-panel="3">
                <div class="checkout-card-head">
                    <div>
                        <span><?= e($isEn ? 'Step 3' : 'Paso 3') ?></span>
                        <h2><?= e($isEn ? 'Delivery location' : 'Ubicacion de entrega') ?></h2>
                    </div>
                </div>
                <?php if (!$hasUbigeo): ?>
                    <div class="lp-alert checkout-alert">
                        <?= e($isEn ? 'Location catalog is not loaded yet. You can type the location manually.' : 'El catalogo de ubicaciones aun no esta cargado. Puedes escribir la ubicacion manualmente.') ?>
                    </div>
                <?php endif; ?>
                <div class="checkout-fields three" data-ubigeo-root data-provinces-url="<?= e(url('/ubigeo/provinces')) ?>" data-districts-url="<?= e(url('/ubigeo/districts')) ?>">
                    <label><?= e($isEn ? 'Region' : 'Region') ?>
                        <?php if ($hasUbigeo): ?>
                            <select name="region" id="checkoutRegion" data-old-value="<?= e(old('region')) ?>" required>
                                <option value=""><?= e($isEn ? 'Select region' : 'Selecciona region') ?></option>
                                <?php foreach ($departments as $department): ?>
                                    <option value="<?= e($department['name']) ?>" data-code="<?= e($department['code']) ?>" <?= old('region') === $department['name'] ? 'selected' : '' ?>><?= e($department['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        <?php else: ?>
                            <input type="text" name="region" id="checkoutRegion" value="<?= e(old('region')) ?>" required>
                        <?php endif; ?>
                    </label>
                    <label><?= e($isEn ? 'Province' : 'Provincia') ?>
                        <?php if ($hasUbigeo): ?>
                            <select name="province" id="checkoutProvince" data-old-value="<?= e(old('province')) ?>" required disabled>
                                <option value=""><?= e($isEn ? 'Select province' : 'Selecciona provincia') ?></option>
                            </select>
                        <?php else: ?>
                            <input type="text" name="province" id="checkoutProvince" value="<?= e(old('province')) ?>" required>
                        <?php endif; ?>
                    </label>
                    <label><?= e($isEn ? 'District' : 'Distrito') ?>
                        <?php if ($hasUbigeo): ?>
                            <select name="district" id="checkoutDistrict" data-old-value="<?= e(old('district')) ?>" required disabled>
                                <option value=""><?= e($isEn ? 'Select district' : 'Selecciona distrito') ?></option>
                            </select>
                        <?php else: ?>
                            <input type="text" name="district" id="checkoutDistrict" value="<?= e(old('district')) ?>" required>
                        <?php endif; ?>
                    </label>
                    <label class="span-3"><?= e($isEn ? 'Reference' : 'Referencia') ?>
                        <input type="text" name="address_reference" value="<?= e(old('address_reference')) ?>">
                    </label>
                </div>
            </div>

            <div class="checkout-card" data-checkout-panel="4">
                <div class="checkout-card-head">
                    <div>
                        <span><?= e($isEn ? 'Step 4' : 'Paso 4') ?></span>
                        <h2><?= e($isEn ? 'Payment voucher' : 'Voucher de pago') ?></h2>
                    </div>
                </div>
                <div class="payment-options">
                    <?php foreach ($paymentMethods as $index => $method): ?>
                        <?php $name = (string)($method['name'] ?? ''); ?>
                        <label class="payment-option">
                            <input type="radio" name="payment_method" value="<?= e($name) ?>" <?= old('payment_method', $index === 0 ? $name : '') === $name ? 'checked' : '' ?> required>
                            <span>
                                <strong><?= e($name) ?></strong>
                                <?php if (($method['type'] ?? '') === 'bank_transfer' && (!empty($method['bank_name']) || !empty($method['currency']))): ?>
                                    <?php $currencyLabel = ($method['currency'] ?? '') === 'PEN' ? ($isEn ? 'Soles (S/)' : 'Soles (S/)') : ((($method['currency'] ?? '') === 'USD') ? ($isEn ? 'Dollars (US$)' : 'Dólares (US$)') : ''); ?>
                                    <small><?= e(trim(($method['bank_name'] ?? '') . ($currencyLabel !== '' ? ' · ' . $currencyLabel : ''))) ?></small>
                                <?php endif; ?>
                                <?php if (!empty($method['account_label']) || !empty($method['account_number'])): ?>
                                    <small><?= e(trim(($method['account_label'] ?? '') . ' ' . ($method['account_number'] ?? ''))) ?></small>
                                <?php endif; ?>
                                <?php if (!empty($method['cci'])): ?>
                                    <small>CCI: <?= e($method['cci']) ?></small>
                                <?php endif; ?>
                                <?php if (!empty($method['holder_name'])): ?><small><?= e($method['holder_name']) ?></small><?php endif; ?>
                                <?php if (!empty($method['instructions'])): ?><small><?= e($method['instructions']) ?></small><?php endif; ?>
                                <?php if (!empty($method['qr_path'])): ?>
                                    <button type="button" class="payment-option-qr-btn" data-qr-zoom="<?= e(url('/' . $method['qr_path'])) ?>" data-qr-name="<?= e($name) ?>">
                                        <img class="payment-option-qr" src="<?= e(url('/' . $method['qr_path'])) ?>" alt="QR <?= e($name) ?>" loading="lazy">
                                        <small class="payment-qr-hint"><?= e($isEn ? 'Tap to enlarge QR' : 'Toca para ampliar el QR') ?></small>
                                    </button>
                                <?php endif; ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
                <div class="checkout-fields two">
                    <label><?= e($isEn ? 'Operation number' : 'Numero de operacion') ?>
                        <input type="text" name="payment_operation_number" value="<?= e(old('payment_operation_number')) ?>" required>
                    </label>
                    <label><?= e($isEn ? 'Voucher image or PDF' : 'Imagen o PDF del voucher') ?>
                        <input type="file" name="voucher" id="voucherInput" accept="image/jpeg,image/png,image/webp,application/pdf" required>
                    </label>
                    <label class="span-2"><?= e($isEn ? 'Notes' : 'Observaciones') ?>
                        <textarea name="customer_notes" rows="3"><?= e(old('customer_notes')) ?></textarea>
                    </label>
                </div>
            </div>

            <div class="checkout-actions">
                <button type="button" class="checkout-nav-btn" id="checkoutPrev"><?= e($isEn ? 'Back' : 'Atras') ?></button>
                <button type="button" class="lp-btn lp-btn-primary" id="checkoutNext"><?= e($isEn ? 'Continue' : 'Continuar') ?></button>
                <button type="button" class="lp-btn lp-btn-primary is-hidden" id="checkoutConfirm"><?= e($isEn ? 'Review and confirm' : 'Revisar y confirmar') ?></button>
            </div>
        </form>

        <aside class="checkout-summary">
            <div class="checkout-summary-card">
                <span><?= e($isEn ? 'Order summary' : 'Resumen del pedido') ?></span>
                <div id="checkoutSummaryItems" class="checkout-summary-items"></div>
                <div class="checkout-total">
                    <span>Total</span>
                    <strong id="checkoutTotal">S/ 0.00</strong>
                </div>
                <p><?= e($isEn ? 'Stock will be updated only after administrative approval.' : 'El stock se actualizara solo cuando administracion apruebe el pedido.') ?></p>
            </div>
        </aside>
    </div>
</section>

<div class="checkout-modal" id="checkoutModal" aria-hidden="true">
    <div class="checkout-modal-card">
        <button type="button" class="checkout-modal-close" id="checkoutModalClose">x</button>
        <span><?= e($isEn ? 'Final validation' : 'Validacion final') ?></span>
        <h2><?= e($isEn ? 'Confirm your order details' : 'Confirma los datos de tu pedido') ?></h2>
        <div id="checkoutReview" class="checkout-review"></div>
        <div class="checkout-modal-actions">
            <button type="button" class="checkout-nav-btn" id="checkoutModalBack"><?= e($isEn ? 'Edit' : 'Editar') ?></button>
            <button type="submit" form="checkoutForm" class="lp-btn lp-btn-primary" id="checkoutSubmit"><?= e($isEn ? 'Create order' : 'Generar pedido') ?></button>
        </div>
    </div>
</div>

<!-- Lightbox: QR ampliado para escanear (Yape, Plin, etc.) -->
<div class="qr-zoom-overlay" id="qrZoomOverlay" role="dialog" aria-modal="true" aria-label="QR">
    <div class="qr-zoom-box">
        <button type="button" class="qr-zoom-close" id="qrZoomClose" aria-label="<?= e($isEn ? 'Close' : 'Cerrar') ?>">&times;</button>
        <img id="qrZoomImg" src="" alt="QR">
        <strong id="qrZoomName"></strong>
        <span><?= e($isEn ? 'Scan the QR with your phone camera or wallet app' : 'Escanea el QR con la cámara de tu celular o tu app de billetera') ?></span>
    </div>
</div>

<script>
(function () {
    var overlay = document.getElementById('qrZoomOverlay');
    var img = document.getElementById('qrZoomImg');
    var name = document.getElementById('qrZoomName');
    if (!overlay) return;
    document.querySelectorAll('[data-qr-zoom]').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            img.src = btn.dataset.qrZoom;
            name.textContent = btn.dataset.qrName || '';
            overlay.classList.add('is-open');
            document.body.classList.add('qr-zoom-open');
        });
    });
    function close() { overlay.classList.remove('is-open'); document.body.classList.remove('qr-zoom-open'); }
    document.getElementById('qrZoomClose').addEventListener('click', close);
    overlay.addEventListener('click', function (e) { if (e.target === overlay) close(); });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') close(); });
})();
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
