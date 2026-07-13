<?php
$headerLogo = !empty($settings['header_logo_path']) ? url('/' . $settings['header_logo_path']) : asset('img/logo-ccopaeca.png');
$footerLogo = !empty($settings['footer_logo_path']) ? url('/' . $settings['footer_logo_path']) : asset('img/logowhite.png');
$aboutTitle = localized_setting($settings, 'about_title', t('about.title'));
$aboutBody = localized_setting($settings, 'about_body', t('about.body'));
$aboutValues = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', localized_setting($settings, 'about_values', t('about.values'))))));
$activeLandingNav = 'inicio';
?>

<?php require __DIR__ . '/partials/header.php'; ?>

<?php $heroSlides = $heroSlides ?? []; ?>
<section class="lp-hero" id="inicio">
    <?php if ($heroSlides): ?>
    <?php // Slides administrables (dashboard > Hero / Sliders): variante mobile
          // por defecto y variante desktop (1800x650) desde 768px. ?>
    <style>
        <?php foreach ($heroSlides as $s): ?>
        .lp-hs-<?= (int)$s['id'] ?> { background-image: url('<?= e(url('/' . ImageOptimizerService::mobileVariantPath((string)$s['image_path']))) ?>'); }
        @media (min-width: 768px) { .lp-hs-<?= (int)$s['id'] ?> { background-image: url('<?= e(url('/' . $s['image_path'])) ?>'); } }
        <?php endforeach; ?>
    </style>
    <div class="lp-hero-slides" id="lpHeroSlides">
        <?php foreach ($heroSlides as $idx => $s): ?>
        <div class="lp-hero-slide lp-hs-<?= (int)$s['id'] ?> <?= $idx === 0 ? 'is-active' : '' ?>"></div>
        <?php endforeach; ?>
    </div>
    <div class="lp-hero-overlay"></div>
    <div class="lp-hero-inner" id="lpHeroTexts">
        <?php foreach ($heroSlides as $idx => $s): ?>
        <?php $slideSubtitle = localized_value($s, 'subtitle'); $slideBadge = localized_value($s, 'badge'); ?>
        <div class="lp-hero-text <?= $idx === 0 ? 'is-active' : '' ?>" data-slide="<?= $idx ?>">
            <h1><?= nl2br(e(localized_value($s, 'title'))) ?></h1>
            <div class="lp-hero-divider"><?= icon('layers') ?></div>
            <?php if ($slideSubtitle !== ''): ?>
            <p><?= e($slideSubtitle) ?></p>
            <?php endif; ?>
            <?php if ($slideBadge !== ''): ?>
            <div class="lp-hero-badge"><?= icon('check-circle') ?> <?= e($slideBadge) ?></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <?php // Fallback: hero por defecto cuando no hay slides activos en BD. ?>
    <div class="lp-hero-slides" id="lpHeroSlides">
        <div class="lp-hero-slide is-active" style="background-image:url('<?= e(asset('img/hero/aereo.png')) ?>')"></div>
        <div class="lp-hero-slide" style="background-image:url('<?= e(asset('img/hero/cacao1.png')) ?>')"></div>
        <div class="lp-hero-slide" style="background-image:url('<?= e(asset('img/hero/cacao3.png')) ?>')"></div>
    </div>
    <div class="lp-hero-overlay"></div>
    <div class="lp-hero-inner" id="lpHeroTexts">
        <?php for ($i = 1; $i <= 3; $i++): ?>
        <div class="lp-hero-text <?= $i === 1 ? 'is-active' : '' ?>" data-slide="<?= $i - 1 ?>">
            <h1><?= t('hero.' . $i . '.title') ?></h1>
            <div class="lp-hero-divider"><?= icon('layers') ?></div>
            <p><?= e(t('hero.' . $i . '.text')) ?></p>
            <div class="lp-hero-badge"><?= icon('check-circle') ?> <?= t('hero.' . $i . '.badge') ?></div>
        </div>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
    <?php if (!$heroSlides || count($heroSlides) > 1): ?>
    <button class="lp-hero-next" id="lpHeroNext" aria-label="<?= e(t('hero.next')) ?>"><?= icon('chevron-right') ?></button>
    <?php endif; ?>
</section>

<section class="lp-features">
    <div class="lp-container lp-features-grid">
        <?php foreach ([1 => 'package', 2 => 'layers', 3 => 'shield'] as $i => $featureIcon): ?>
        <div class="lp-feature-card reveal">
            <span class="lp-feature-tag"><?= e(t('feature.' . $i . '.tag')) ?></span>
            <h3><?= e(t('feature.' . $i . '.title')) ?></h3>
            <div class="lp-feature-icon"><?= icon($featureIcon) ?></div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="lp-section" id="productos">
    <div class="lp-container">
        <div class="lp-section-head reveal">
            <span class="lp-tag"><?= e(t('products.tag')) ?></span>
            <h2><?= e(t('products.title')) ?></h2>
            <p><?= e(t('products.text')) ?></p>
        </div>
        <div class="lp-grid-3">
            <?php
            $fallbackProducts = [
                ['name' => landing_lang() === 'en' ? 'Fermented cacao beans' : 'Cacao en grano fermentado', 'short_description' => landing_lang() === 'en' ? 'Selected, fermented and dried beans for specialized buyers.' : 'Granos seleccionados, fermentados y secados para compradores especializados.'],
                ['name' => landing_lang() === 'en' ? 'Roasted cacao nibs' : 'Nibs de cacao tostado', 'short_description' => landing_lang() === 'en' ? 'Crunchy cacao pieces with intense aroma for chocolate and pastry.' : 'Trozos crujientes de cacao con aroma intenso para chocolateria y reposteria.'],
                ['name' => landing_lang() === 'en' ? 'Natural cacao paste' : 'Pasta de cacao natural', 'short_description' => landing_lang() === 'en' ? 'Pure cacao mass for chocolates, coatings and beverages.' : 'Masa pura de cacao para elaboracion de chocolates, coberturas y bebidas.'],
                ['name' => landing_lang() === 'en' ? 'Cacao butter' : 'Manteca de cacao', 'short_description' => landing_lang() === 'en' ? 'Premium derivative for fine chocolate, natural cosmetics and special formulations.' : 'Derivado premium para chocolateria fina, cosmetica natural y formulaciones especiales.'],
                ['name' => landing_lang() === 'en' ? 'Cacao powder' : 'Cacao en polvo', 'short_description' => landing_lang() === 'en' ? 'Aromatic cacao powder for drinks, bakery, ice cream and healthy cooking.' : 'Polvo de cacao aromatico para bebidas, panaderia, heladeria y cocina saludable.'],
                ['name' => landing_lang() === 'en' ? 'Artisanal dark chocolate' : 'Chocolate bitter artesanal', 'short_description' => landing_lang() === 'en' ? 'High percentage bars inspired by cooperative-origin cacao.' : 'Tabletas de alto porcentaje, inspiradas en cacao de origen cooperativo.'],
            ];
            $productList = $products ?: $fallbackProducts;
            $isFromDB = !empty($products);
            foreach ($productList as $i => $p):
                $pName = localized_value($p, 'name');
                $pDescription = localized_value($p, 'short_description');
                $pImage = $p['cover_path'] ?? null;
                $pPrice = (float)($p['price'] ?? 0);
                $pSalePrice = isset($p['sale_price']) && $p['sale_price'] !== null ? (float)$p['sale_price'] : null;
                $pFeatured = !empty($p['is_featured']);
                $pCats = $p['categories'] ?? [];
                $pCatName = !empty($pCats) ? localized_value($pCats[0], 'name') : null;
                $pSlug = $p['slug'] ?? null;
                $cardTag = ($isFromDB && $pSlug) ? 'a href="' . e(lurl('/producto?slug=' . $pSlug)) . '"' : 'div';
                $cardClose = ($isFromDB && $pSlug) ? 'a' : 'div';
            ?>
            <<?= $cardTag ?> class="lp-card lp-card-pro reveal">
                <div class="lp-card-img-wrap">
                    <?php if ($pImage): ?>
                        <img class="lp-card-img-real" src="<?= e(url('/' . $pImage)) ?>" alt="<?= e($pName) ?>" loading="lazy">
                    <?php else: ?>
                        <div class="lp-card-img lp-card-img-placeholder lp-cacao-placeholder lp-cacao-placeholder-<?= ($i % 6) + 1 ?>">
                            <div><?= icon('package') ?></div>
                        </div>
                    <?php endif; ?>
                    <?php if ($pFeatured): ?><span class="lp-card-badge-featured">* <?= e(t('product.featured')) ?></span><?php endif; ?>
                    <?php if ($pCatName): ?><span class="lp-card-badge-cat"><?= e($pCatName) ?></span><?php endif; ?>
                    <?php if ($pPrice > 0): ?>
                        <div class="lp-card-price-tag">
                            <?php if ($pSalePrice !== null): ?>
                                <span class="lp-price-old">S/ <?= e(number_format($pPrice, 2)) ?></span>
                                <span class="lp-price-new">S/ <?= e(number_format($pSalePrice, 2)) ?></span>
                            <?php else: ?>
                                <span class="lp-price-new">S/ <?= e(number_format($pPrice, 2)) ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="lp-card-body">
                    <h3><?= e($pName) ?></h3>
                    <p><?= e($pDescription) ?></p>
                </div>
            </<?= $cardClose ?>>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="lp-section lp-section-alt" id="servicios">
    <div class="lp-container">
        <div class="lp-section-head reveal">
            <span class="lp-tag"><?= e(t('services.tag')) ?></span>
            <h2><?= e(t('services.title')) ?></h2>
            <p><?= e(t('services.text')) ?></p>
        </div>
        <div class="lp-grid-3">
            <?php
            $defaultServices = [
                ['icon_name' => 'package', 'name' => landing_lang() === 'en' ? 'Cacao sourcing' : 'Acopio de cacao', 'desc' => landing_lang() === 'en' ? 'Reception, weighing and classification of fresh and dry cacao from partner producers.' : 'Recepcion, pesaje y clasificacion de cacao fresco y seco de productores aliados.'],
                ['icon_name' => 'activity', 'name' => landing_lang() === 'en' ? 'Fermentation and drying' : 'Fermentacion y secado', 'desc' => landing_lang() === 'en' ? 'Technical support to improve aroma, color, moisture and bean performance.' : 'Acompanamiento tecnico para mejorar aroma, color, humedad y rendimiento del grano.'],
                ['icon_name' => 'shield', 'name' => landing_lang() === 'en' ? 'Quality control' : 'Control de calidad', 'desc' => landing_lang() === 'en' ? 'Evaluation of moisture, fermentation, defects, aroma and traceability by lot.' : 'Evaluacion de humedad, fermentacion, defectos, aroma y trazabilidad por lote.'],
                ['icon_name' => 'layers', 'name' => landing_lang() === 'en' ? 'Derivative transformation' : 'Transformacion de derivados', 'desc' => landing_lang() === 'en' ? 'Production of nibs, paste, butter, powder and demonstration chocolate.' : 'Elaboracion de nibs, pasta, manteca, polvo y chocolate demostrativo.'],
                ['icon_name' => 'share', 'name' => landing_lang() === 'en' ? 'Associative commercialization' : 'Comercializacion asociativa', 'desc' => landing_lang() === 'en' ? 'Connection with buyers and commercial preparation of cacao and derivatives.' : 'Articulacion con compradores y preparacion comercial de cacao y derivados.'],
                ['icon_name' => 'users', 'name' => landing_lang() === 'en' ? 'Producer training' : 'Capacitacion a productores', 'desc' => landing_lang() === 'en' ? 'Assistance in harvest, post-harvest handling, quality and sustainability.' : 'Asistencia en cosecha, manejo postcosecha, calidad y sostenibilidad.'],
            ];
            $list = $services ?: $defaultServices;
            foreach ($list as $s):
                $name = localized_value($s, 'name');
                $desc = localized_value($s, 'short_description') ?: ($s['desc'] ?? '');
                $serviceIcon = $s['icon_name'] ?? $s['icon'] ?? 'layers';
            ?>
            <div class="lp-card lp-card-service reveal">
                <div class="lp-icon-circle"><?= icon($serviceIcon) ?></div>
                <h3><?= e($name) ?></h3>
                <p><?= e($desc) ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="lp-section lp-split lp-section-dark" id="nosotros">
    <div class="lp-container lp-split-inner">
        <div class="lp-split-media reveal">
            <div class="lp-media-block" style="background-image:url('<?= e(asset('img/hero/cacao1.png')) ?>')"></div>
        </div>
        <div class="lp-split-text reveal">
            <span class="lp-tag lp-tag-light"><?= e(t('about.tag')) ?></span>
            <h2><?= e($aboutTitle) ?></h2>
            <p><?= e($aboutBody) ?></p>
            <ul class="lp-check-list">
                <?php foreach (array_slice($aboutValues, 0, 3) as $value): ?>
                    <li><?= icon('check-circle') ?> <?= e($value) ?></li>
                <?php endforeach; ?>
            </ul>
            <a href="<?= e(lurl('/nosotros')) ?>" class="lp-btn lp-btn-primary lp-about-more"><?= e(t('about.more')) ?></a>
        </div>
    </div>
</section>

<section class="lp-section" id="publicaciones">
    <div class="lp-container">
        <div class="lp-section-head reveal">
            <span class="lp-tag"><?= e(t('posts.tag')) ?></span>
            <h2><?= e(t('posts.title')) ?></h2>
            <p><?= e(t('posts.text')) ?></p>
        </div>
        <div class="lp-news-grid">
            <?php
            $fallbackPosts = [
                ['title' => landing_lang() === 'en' ? 'Community sourcing' : 'Acopio comunitario', 'category' => landing_lang() === 'en' ? 'Sourcing' : 'Acopio', 'excerpt' => landing_lang() === 'en' ? 'Producer families organize cacao delivery with transparent records by lot.' : 'Familias productoras organizan la entrega de cacao con registros transparentes por lote.'],
                ['title' => landing_lang() === 'en' ? 'Controlled fermentation' : 'Fermentacion controlada', 'category' => landing_lang() === 'en' ? 'Quality' : 'Calidad', 'excerpt' => landing_lang() === 'en' ? 'Fermentation and drying processes that improve aroma, color and commercial performance.' : 'Procesos de fermentacion y secado que mejoran aroma, color y rendimiento comercial.'],
                ['title' => landing_lang() === 'en' ? 'Productive plots' : 'Parcelas productivas', 'category' => landing_lang() === 'en' ? 'Community' : 'Comunidad', 'excerpt' => landing_lang() === 'en' ? 'Support for partner plots to strengthen productivity and traceability.' : 'Acompanamiento a parcelas aliadas para fortalecer productividad y trazabilidad.'],
            ];
            $realPosts = !empty($posts);
            $postList = $posts ?: $fallbackPosts;
            foreach ($postList as $i => $post):
                $image = $post['image_path'] ?? null;
                $postTitle = localized_value($post, 'title');
                $excerpt = localized_value($post, 'excerpt');
                $tag = $realPosts ? 'a' : 'div';
            ?>
                <<?= $tag ?> <?= $realPosts ? 'href="' . e(lurl('/publicacion?slug=' . $post['slug'])) . '"' : '' ?> class="lp-news-card reveal">
                    <?php if ($image): ?>
                        <div class="lp-news-image" style="background-image:url('<?= e(url('/' . $image)) ?>')"></div>
                    <?php else: ?>
                        <div class="lp-news-image lp-gallery-placeholder lp-gallery-placeholder-<?= ($i % 6) + 1 ?>"></div>
                    <?php endif; ?>
                    <div class="lp-news-body">
                        <span><?= e(localized_value($post, 'category') ?: t('post.category_general')) ?></span>
                        <h3><?= e($postTitle) ?></h3>
                        <p><?= e($excerpt) ?></p>
                    </div>
                </<?= $tag ?>>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="lp-section lp-section-dark" id="ubicanos">
    <div class="lp-container lp-export-inner reveal">
        <span class="lp-tag lp-tag-light"><?= e(localized_setting($settings, 'map_tag', t('map.tag'))) ?></span>
        <h2><?= e(localized_setting($settings, 'map_title', t('map.title'))) ?></h2>
        <p><?= e(localized_setting($settings, 'map_description', t('map.text'))) ?></p>
        <?php if (!empty($settings['map_embed_html'])): ?>
            <div class="lp-map-placeholder">
                <?= localized_map_embed_html($settings['map_embed_html']) ?>
            </div>
        <?php else: ?>
            <div class="lp-map-placeholder lp-cacao-map-placeholder"></div>
        <?php endif; ?>
    </div>
</section>

<section class="lp-section lp-section-alt" id="contacto">
    <div class="lp-container lp-contact-inner">
        <div class="lp-split-text reveal">
            <span class="lp-tag"><?= e(t('contact.tag')) ?></span>
            <h2><?= e(t('contact.title')) ?></h2>
            <p><?= e(t('contact.text')) ?></p>
        </div>
        <form method="post" action="<?= e(lurl('/contact')) ?>" class="lp-form reveal">
            <?= csrf_field() ?>
            <?php if ($message = flash('status')): ?><div class="lp-alert lp-alert-success"><?= e($message) ?></div><?php endif; ?>
            <?php $errors = errors(); if ($errors): ?>
                <div class="lp-alert lp-alert-error"><?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?></div>
            <?php endif; ?>
            <div class="lp-form-row">
                <input type="text" name="name" placeholder="<?= e(t('contact.name')) ?>" value="<?= e(old('name')) ?>" required>
                <input type="email" name="email" placeholder="<?= e(t('contact.email')) ?>" value="<?= e(old('email')) ?>" required>
            </div>
            <div class="lp-form-row">
                <input type="text" name="phone" placeholder="<?= e(t('contact.phone')) ?>" value="<?= e(old('phone')) ?>">
                <input type="text" name="subject" placeholder="<?= e(t('contact.subject')) ?>" value="<?= e(old('subject')) ?>">
            </div>
            <textarea name="message" rows="5" placeholder="<?= e(t('contact.message')) ?>" required><?= e(old('message')) ?></textarea>
            <button type="submit" class="lp-btn lp-btn-primary"><?= e(t('contact.send')) ?></button>
        </form>
    </div>
</section>

<a
    href="<?= e(whatsapp_link((string)($settings['whatsapp_landing'] ?? '51999999999'), t('whatsapp.landing_text'))) ?>"
    class="lp-whatsapp-float"
    target="_blank"
    rel="noopener"
    aria-label="WhatsApp"
>
    <img src="<?= e(asset('img/whatsapp.png')) ?>" alt="WhatsApp">
</a>

<?php require __DIR__ . '/partials/footer.php'; ?>
