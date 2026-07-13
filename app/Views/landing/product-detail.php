<?php
$activeLandingNav = 'productos';
$headerLogo = !empty($settings['header_logo_path']) ? url('/' . $settings['header_logo_path']) : asset('img/logo-ccopaeca.png');
$footerLogo = !empty($settings['footer_logo_path']) ? url('/' . $settings['footer_logo_path']) : asset('img/logowhite.png');
$p = $product;
$cover = $p['cover_path'] ?? null;
$price = (float)($p['price'] ?? 0);
$salePrice = isset($p['sale_price']) && $p['sale_price'] !== null ? (float)$p['sale_price'] : null;
$cats = $p['categories'] ?? [];
$featured = !empty($p['is_featured']);
$productName = localized_value($p, 'name');
$productShort = localized_value($p, 'short_description');
$description = localized_value($p, 'description');
$origin = localized_value($p, 'origin');
$variety = localized_value($p, 'variety');
$fermentation = localized_value($p, 'fermentation');
$altitude = localized_value($p, 'altitude');
$certification = localized_value($p, 'certification');
$presentation = localized_value($p, 'presentation');
$waProducts = $settings['whatsapp_products'] ?? '51999999999';
$waProductPrice = $salePrice ?? $price;
$waStock = $p['stock'] ?? null;
$waPresentation = $presentation;
$waStockLabel = ($waStock === null) ? t('product.available') : ((int)$waStock > 0 ? t('product.in_stock', ['count' => (int)$waStock]) : t('product.out_stock'));
$waStockClass = ($waStock !== null && (int)$waStock === 0) ? ' pd-stock-out' : '';
$waStockQty = ($waStock === null) ? '' : (int)$waStock;
$waJson = json_encode([
    'id' => (int)$p['id'],
    'slug' => $p['slug'] ?? '',
    'name' => $productName,
    'presentation' => $waPresentation,
    'price' => $waProductPrice,
    'price_label' => number_format($waProductPrice, 2),
    'stock' => $waStock,
    'image' => $cover ? url('/' . $cover) : '',
    'url' => lurl('/producto?slug=' . ($p['slug'] ?? '')),
    'phone' => $waProducts,
    'wa_buy' => t('product.wa_buy'),
    'wa_buy_presentation' => t('product.wa_buy_presentation'),
    'wa_out' => t('product.wa_out'),
    'wa_out_presentation' => t('product.wa_out_presentation'),
]);
require __DIR__ . '/partials/header.php';
?>

<!-- BREADCRUMB -->
<div class="pd-breadcrumb">
    <div class="lp-container">
        <a href="<?= e(lurl('/')) ?>"><?= e(t('breadcrumb.home')) ?></a>
        <span>/</span>
        <a href="<?= e(lurl('/#productos')) ?>"><?= e(t('nav.products')) ?></a>
        <span>/</span>
        <strong><?= e($productName) ?></strong>
    </div>
</div>

<!-- HERO PRODUCTO -->
<section class="pd-hero">
    <div class="lp-container">
        <div class="pd-hero-grid">
            <!-- IMAGEN -->
            <div class="pd-gallery">
                <div class="pd-main-image">
                    <?php if ($cover): ?>
                        <img src="<?= e(url('/' . $cover)) ?>" alt="<?= e($productName) ?>" id="pdMainImg">
                    <?php else: ?>
                        <div class="pd-main-placeholder pd-cacao-placeholder-<?= ((int)$p['id'] % 6) + 1 ?>">
                            <div class="pd-placeholder-icon"><?= icon('package') ?></div>
                            <span><?= e(config_app('name')) ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if ($featured): ?>
                        <span class="pd-featured-badge">* <?= e(t('product.featured')) ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- INFO -->
            <div class="pd-info">
                <?php if ($cats): ?>
                <div class="pd-cats">
                    <?php foreach ($cats as $cat): ?>
                        <span class="pd-cat-tag"><?= e(localized_value($cat, 'name')) ?></span>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <h1 class="pd-title"><?= e($productName) ?></h1>

                <?php if ($productShort !== ''): ?>
                    <p class="pd-excerpt"><?= e($productShort) ?></p>
                <?php endif; ?>

                <!-- PRICE -->
                <div class="pd-price-block">
                    <?php if ($salePrice !== null): ?>
                        <span class="pd-price-old">S/ <?= e(number_format($price, 2)) ?></span>
                        <span class="pd-price">S/ <?= e(number_format($salePrice, 2)) ?></span>
                        <span class="pd-discount"><?= e(t('product.offer')) ?></span>
                    <?php elseif ($price > 0): ?>
                        <span class="pd-price">S/ <?= e(number_format($price, 2)) ?></span>
                    <?php endif; ?>
                </div>

                <!-- STOCK -->
                <div class="pd-stock<?= $waStockClass ?>"><?= e($waStockLabel) ?></div>

                <!-- ACTION BUTTONS -->
                <div class="pd-actions">
                    <div class="pd-qty-wrap">
                        <label class="pd-qty-label" for="pdQuantity"><?= e(t('product.quantity')) ?></label>
                        <input type="number" id="pdQuantity" class="pd-qty-input" min="1"<?php if ($waStockQty !== ''): ?> max="<?= $waStockQty ?>"<?php endif; ?> value="1"<?php if ($waStock !== null && (int)$waStock === 0): ?> disabled<?php endif; ?>>
                    </div>
                    <button type="button" id="pdAddCartBtn" class="pd-btn-cart" data-product='<?= e($waJson) ?>'<?php if ($waStock !== null && (int)$waStock === 0): ?> disabled<?php endif; ?>>
                        <?= icon('shopping-cart') ?> <?= e(t('product.add_cart')) ?>
                    </button>
                    <button type="button" id="pdWhatsappAssistBtn" class="pd-btn-contact" data-product='<?= e($waJson) ?>'>
                        <?= icon('message-circle') ?> <?= e(t('product.buy_whatsapp')) ?>
                    </button>
                </div>

                <!-- SPECS TABLE -->
                <?php if (!empty($p['sku'])): ?>
                <div class="pd-meta">
                    <div class="pd-meta-item">
                        <span class="pd-meta-label">SKU</span>
                        <span class="pd-meta-value"><?= e($p['sku']) ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- DETALLES + DESCRIPCIÓN -->
<section class="pd-content">
    <div class="lp-container">
        <div class="pd-content-grid">

            <!-- LEFT: Características -->
            <div class="pd-main-col">
                <!-- DESCRIPCIÓN -->
                <?php if ($description): ?>
                <div class="pd-section">
                    <h2 class="pd-section-title">
                        <span class="pd-section-icon"><?= icon('file-text') ?></span>
                        <?= e(t('product.description')) ?>
                    </h2>
                    <div class="pd-description">
                        <?= nl2br(e($description)) ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- CARACTERÍSTICAS COOPAECA -->
                <div class="pd-section">
                    <h2 class="pd-section-title">
                        <span class="pd-section-icon"><?= icon('info') ?></span>
                        <?= e(t('product.features')) ?>
                    </h2>
                    <div class="pd-features-grid">
                        <?php if ($origin !== ''): ?>
                        <div class="pd-feature-card">
                            <div class="pd-feature-icon">🌱</div>
                            <h4><?= e(t('product.origin')) ?></h4>
                            <p><?= e($origin) ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if ($altitude !== ''): ?>
                        <div class="pd-feature-card">
                            <div class="pd-feature-icon">🏔️</div>
                            <h4><?= e(t('product.altitude')) ?></h4>
                            <p><?= e($altitude) ?></p>
                        </div>
                        <?php endif; ?>
                        <div class="pd-feature-card">
                            <div class="pd-feature-icon">🤝</div>
                            <h4><?= e(t('product.coop_sourcing')) ?></h4>
                            <p><?= e(t('product.coop_sourcing_text')) ?></p>
                        </div>
                        <div class="pd-feature-card">
                            <div class="pd-feature-icon">🔬</div>
                            <h4><?= e(t('product.quality_control')) ?></h4>
                            <p><?= e(t('product.quality_control_text')) ?></p>
                        </div>
                        <?php if ($fermentation !== ''): ?>
                        <div class="pd-feature-card">
                            <div class="pd-feature-icon">🍫</div>
                            <h4><?= e(t('product.fermentation')) ?></h4>
                            <p><?= e($fermentation) ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($p['humidity'])): ?>
                        <div class="pd-feature-card">
                            <div class="pd-feature-icon">☀️</div>
                            <h4><?= e(t('product.natural_drying')) ?></h4>
                            <p><?= e(t('product.natural_drying_text', ['humidity' => $p['humidity']])) ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- RIGHT: Sidebar -->
            <div class="pd-side-col">
                <!-- DATOS TÉCNICOS COOPAECA -->
                <div class="pd-side-card">
                    <h3 class="pd-side-title"><?= icon('clipboard') ?> <?= e(t('product.tech_sheet')) ?></h3>
                    <ul class="pd-specs">
                        <?php if ($origin !== ''): ?>
                        <li><span><?= e(t('product.origin')) ?></span><strong><?= e($origin) ?></strong></li>
                        <?php endif; ?>
                        <?php if ($variety !== ''): ?>
                        <li><span><?= e(t('product.variety')) ?></span><strong><?= e($variety) ?></strong></li>
                        <?php endif; ?>
                        <?php if ($fermentation !== ''): ?>
                        <li><span><?= e(t('product.fermentation')) ?></span><strong><?= e($fermentation) ?></strong></li>
                        <?php endif; ?>
                        <?php if (!empty($p['humidity'])): ?>
                        <li><span><?= e(t('product.humidity')) ?></span><strong><?= e($p['humidity']) ?></strong></li>
                        <?php endif; ?>
                        <?php if (!empty($p['grain_count'])): ?>
                        <li><span><?= e(t('product.grain_count')) ?></span><strong><?= e($p['grain_count']) ?></strong></li>
                        <?php endif; ?>
                        <?php if (!empty($p['grain_index'])): ?>
                        <li><span><?= e(t('product.grain_index')) ?></span><strong><?= e($p['grain_index']) ?></strong></li>
                        <?php endif; ?>
                        <?php if ($altitude !== ''): ?>
                        <li><span><?= e(t('product.altitude')) ?></span><strong><?= e($altitude) ?></strong></li>
                        <?php endif; ?>
                        <li><span><?= e(t('product.sourcing')) ?></span><strong>COOPAECA - Cooperativa Agraria</strong></li>
                        <?php if ($certification !== ''): ?>
                        <li><span><?= e(t('product.certification')) ?></span><strong><?= e($certification) ?></strong></li>
                        <?php endif; ?>
                        <?php if ($presentation !== ''): ?>
                        <li><span><?= e(t('product.presentation')) ?></span><strong><?= e($presentation) ?></strong></li>
                        <?php endif; ?>
                    </ul>
                </div>

                <!-- CATEGORÍAS -->
                <?php if ($cats): ?>
                <div class="pd-side-card">
                    <h3 class="pd-side-title"><?= icon('tag') ?> <?= e(t('product.categories')) ?></h3>
                    <div class="pd-side-tags">
                        <?php foreach ($cats as $cat): ?>
                            <span class="pd-side-tag"><?= e(localized_value($cat, 'name')) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- CONTACTO RÁPIDO -->
                <div class="pd-side-card pd-side-cta">
                    <h3 class="pd-side-title"><?= icon('shopping-cart') ?> <?= e(t('product.buy')) ?></h3>
                    <p><?= e(t('product.buy_text')) ?></p>
                    <button type="button" class="pd-btn-whatsapp js-whatsapp-assist" data-product='<?= e($waJson) ?>'>
                        <?= icon('message-circle') ?> <?= e(t('product.buy_whatsapp')) ?>
                    </button>
                </div>
            </div>

        </div>
    </div>
</section>

<!-- PRODUCTOS RELACIONADOS -->
<?php if (!empty($related)): ?>
<section class="pd-related">
    <div class="lp-container">
        <div class="lp-section-head reveal">
            <span class="lp-tag"><?= e(t('products.tag')) ?></span>
            <h2><?= e(t('product.related')) ?></h2>
            <p><?= e(t('product.related_text')) ?></p>
        </div>
        <div class="lp-grid-3">
            <?php foreach ($related as $i => $rp):
                $rpName = localized_value($rp, 'name');
                $rpShort = localized_value($rp, 'short_description');
                $rpImage = $rp['cover_path'] ?? null;
                $rpPrice = (float)($rp['price'] ?? 0);
                $rpSale = isset($rp['sale_price']) && $rp['sale_price'] !== null ? (float)$rp['sale_price'] : null;
            ?>
            <a href="<?= e(lurl('/producto?slug=' . $rp['slug'])) ?>" class="lp-card lp-card-pro reveal">
                <div class="lp-card-img-wrap">
                    <?php if ($rpImage): ?>
                        <img class="lp-card-img-real" src="<?= e(url('/' . $rpImage)) ?>" alt="<?= e($rpName) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="lp-card-img lp-card-img-placeholder lp-cacao-placeholder lp-cacao-placeholder-<?= ($i % 6) + 1 ?>">
                            <div><?= icon('package') ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($rp['is_featured'])): ?>
                        <span class="lp-card-badge-featured">★ <?= e(t('product.featured')) ?></span>
                    <?php endif; ?>
                    <?php if ($rpPrice > 0): ?>
                    <div class="lp-card-price-tag">
                        <?php if ($rpSale !== null): ?>
                            <span class="lp-price-old">S/ <?= e(number_format($rpPrice, 2)) ?></span>
                            <span class="lp-price-new">S/ <?= e(number_format($rpSale, 2)) ?></span>
                        <?php else: ?>
                            <span class="lp-price-new">S/ <?= e(number_format($rpPrice, 2)) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="lp-card-body">
                    <h3><?= e($rpName) ?></h3>
                    <p><?= e($rpShort) ?></p>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <div class="pd-back-wrap">
            <a href="<?= e(lurl('/#productos')) ?>" class="lp-btn lp-btn-primary"><?= icon('arrow-left') ?> <?= e(t('product.all_catalog')) ?></a>
        </div>
</section>
<?php endif; ?>

<?php require __DIR__ . '/partials/footer.php'; ?>

<script>
(function() {
    var btn = document.getElementById('pdWhatsappBtn');
    var qtyInput = document.getElementById('pdQuantity');
    if (!btn || !qtyInput) return;
    var data = JSON.parse(btn.getAttribute('data-product'));
    btn.addEventListener('click', function(e) {
        e.preventDefault();
        var qty = parseInt(qtyInput.value) || 1;
        if (qty < 1) qty = 1;
        if (qtyInput.max && qty > parseInt(qtyInput.max)) qty = parseInt(qtyInput.max);
        var msg;
        if (data.stock !== null && data.stock == 0) {
            msg = 'Hola, quiero consultar disponibilidad de: ' + data.name
                + (data.presentation ? ' - Presentación: ' + data.presentation : '')
                + '. Precio: S/ ' + data.price
                + '. Entiendo que podría estar agotado, ¿cuándo tendrían nuevo stock?';
        } else {
            msg = 'Hola, quiero comprar: ' + data.name
                + (data.presentation ? ' - Presentación: ' + data.presentation : '')
                + '. Cantidad deseada: ' + qty
                + '. Precio: S/ ' + data.price
                + '. Quedo atento a la disponibilidad.';
        }
        var template = (data.stock !== null && data.stock == 0)
            ? (data.presentation ? data.wa_out_presentation : data.wa_out)
            : (data.presentation ? data.wa_buy_presentation : data.wa_buy);
        msg = template
            .replace(':name', data.name)
            .replace(':presentation', data.presentation || '')
            .replace(':qty', qty)
            .replace(':price', data.price);
        window.open(window.lpWhatsAppLink(data.phone, msg), '_blank');
    });
})();
</script>
