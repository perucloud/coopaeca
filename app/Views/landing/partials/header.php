<?php
$activeLandingNav = $activeLandingNav ?? 'inicio';
$headerLogo = $headerLogo ?? asset('img/logo-ccopaeca.png');
$homeUrl = lurl('/');
$sectionUrl = fn (string $section): string => $activeLandingNav === 'inicio' ? '#' . $section : lurl('/#' . $section);
$nosotrosUrl = $activeLandingNav === 'nosotros' ? lurl('/nosotros') : $sectionUrl('nosotros');
$currentLang = landing_lang();

$navProducts = Database::connection()->query(
    "SELECT name, name_en, slug FROM products WHERE status = 'published' ORDER BY is_featured DESC, name ASC LIMIT 12"
)->fetchAll();
?>

<div class="lp-topbar" id="lpTopbar">
    <div class="lp-container lp-topbar-inner">
        <div class="lp-topbar-item">
            <?= icon('phone', 'lp-topbar-icon') ?>
            <div><span><?= e(t('topbar.contact')) ?></span><strong><?= e($settings['topbar_phone'] ?? '+51 999 999 999') ?></strong></div>
        </div>
        <div class="lp-topbar-item">
            <?= icon('mail', 'lp-topbar-icon') ?>
            <div><span><?= e(t('topbar.email')) ?></span><strong><?= e($settings['topbar_email'] ?? 'comercio@coopaeca.org.pe') ?></strong></div>
        </div>
        <div class="lp-topbar-item">
            <?= icon('map-pin', 'lp-topbar-icon') ?>
            <div><span><?= e(t('topbar.address')) ?></span><strong><?= e($settings['topbar_address'] ?? 'Av. Principal s/n, Peru') ?></strong></div>
        </div>
    </div>
</div>

<header class="lp-nav" id="lpNav">
    <div class="lp-container lp-nav-inner">
        <a href="<?= e($homeUrl) ?>" class="lp-brand" aria-label="COOPAECA">
            <img src="<?= e($headerLogo) ?>" alt="COOPAECA">
        </a>
        <nav class="lp-menu" id="lpMenu">
            <a href="<?= e($activeLandingNav === 'inicio' ? '#inicio' : $homeUrl) ?>" class="lp-nav-link <?= $activeLandingNav === 'inicio' ? 'is-active' : '' ?>"><?= icon('home') ?><span><?= e(t('nav.home')) ?></span></a>
            <div class="lp-nav-dropdown <?= $activeLandingNav === 'nosotros' ? 'is-active' : '' ?>">
                <a href="<?= e($nosotrosUrl) ?>" class="lp-nav-link <?= $activeLandingNav === 'nosotros' ? 'is-active' : '' ?>"><?= icon('users') ?><span><?= e(t('nav.about')) ?></span><?= icon('chevron-down', 'lp-nav-caret') ?></a>
                <div class="lp-submenu">
                    <a href="<?= e(lurl('/galeria')) ?>" class="lp-submenu-link"><?= icon('image') ?><span><?= e(t('nav.gallery')) ?></span></a>
                    <a href="<?= e($sectionUrl('ubicanos')) ?>" class="lp-submenu-link"><?= icon('map-pin') ?><span><?= e(t('nav.location')) ?></span></a>
                </div>
            </div>
            <a href="<?= e($sectionUrl('servicios')) ?>" class="lp-nav-link <?= $activeLandingNav === 'servicios' ? 'is-active' : '' ?>"><?= icon('layers') ?><span><?= e(t('nav.services')) ?></span></a>
            <div class="lp-nav-dropdown <?= $activeLandingNav === 'productos' ? 'is-active' : '' ?>">
                <a href="<?= e($sectionUrl('productos')) ?>" class="lp-nav-link <?= $activeLandingNav === 'productos' ? 'is-active' : '' ?>"><?= icon('package') ?><span><?= e(t('nav.products')) ?></span><?= icon('chevron-down', 'lp-nav-caret') ?></a>
                <?php if ($navProducts): ?>
                <div class="lp-submenu lp-submenu-products">
                    <?php foreach ($navProducts as $np): ?>
                        <a href="<?= e(lurl('/producto?slug=' . $np['slug'])) ?>" class="lp-submenu-link"><?= icon('package') ?><span><?= e(localized_value($np, 'name')) ?></span></a>
                    <?php endforeach; ?>
                    <a href="<?= e($sectionUrl('productos')) ?>" class="lp-submenu-link lp-submenu-all"><?= icon('grid') ?><span><?= e(t('nav.all_products')) ?></span></a>
                </div>
                <?php endif; ?>
            </div>
            <a href="<?= e($sectionUrl('publicaciones')) ?>" class="lp-nav-link <?= $activeLandingNav === 'publicaciones' ? 'is-active' : '' ?>"><?= icon('edit') ?><span><?= e(t('nav.posts')) ?></span></a>
            <a href="<?= e($sectionUrl('contacto')) ?>" class="lp-nav-link <?= $activeLandingNav === 'contacto' ? 'is-active' : '' ?>"><?= icon('mail') ?><span><?= e(t('nav.contact')) ?></span></a>
            <a href="https://coopaeca.org.pe:2096/webmaillogout.cgi" class="lp-admin-link lp-webmail-link" target="_blank" rel="noopener" aria-label="<?= e(t('nav.webmail')) ?>" title="<?= e(t('nav.webmail')) ?>"><?= icon('mail') ?></a>
            <a href="<?= e(url('/login')) ?>" class="lp-admin-link" aria-label="<?= e(t('nav.admin')) ?>" title="<?= e(t('nav.admin')) ?>"><?= icon('lock') ?></a>
        </nav>
        <div class="lp-nav-icons">
            <div class="lp-lang-switch is-<?= e($currentLang) ?>" aria-label="<?= e(t('lang.label')) ?>" data-lang-switch data-current="<?= e($currentLang) ?>" data-url-es="<?= e(lang_switch_url('es')) ?>" data-url-en="<?= e(lang_switch_url('en')) ?>">
                <span class="lp-lang-knob" data-lang-knob aria-hidden="true">
                    <img class="lp-lang-flag lp-lang-flag-es" src="<?= e(asset('img/flag-peru.png')) ?>" alt="">
                    <img class="lp-lang-flag lp-lang-flag-en" src="<?= e(asset('img/flag-britain.png')) ?>" alt="">
                </span>
                <a href="<?= e(lang_switch_url('es')) ?>" class="<?= $currentLang === 'es' ? 'is-active' : '' ?>"><?= e(t('lang.es')) ?></a>
                <a href="<?= e(lang_switch_url('en')) ?>" class="<?= $currentLang === 'en' ? 'is-active' : '' ?>"><?= e(t('lang.en')) ?></a>
            </div>
            <button class="lp-icon-btn" id="searchTriggerLp" aria-label="<?= e(t('search.open')) ?>"><?= icon('search') ?></button>
        </div>
        <button class="lp-burger" id="lpBurger" aria-label="<?= e(t('nav.menu')) ?>" aria-expanded="false" aria-controls="lpMenu"><?= icon('menu') ?></button>
    </div>
</header>

<div class="lp-search-overlay" id="lpSearchOverlay" aria-hidden="true">
    <div class="lp-search-modal">
        <div class="lp-search-bar">
            <?= icon('search', 'lp-search-icon') ?>
            <input type="text" id="lpSearchInput" class="lp-search-input" placeholder="<?= e(t('search.placeholder')) ?>" autocomplete="off">
            <kbd class="lp-search-kbd">ESC</kbd>
        </div>
        <div class="lp-search-results" id="lpSearchResults">
            <div class="lp-search-empty">
                <div class="lp-search-empty-icon"><?= icon('search') ?></div>
                <strong><?= e(t('search.empty_title')) ?></strong>
                <p><?= e(t('search.empty_text')) ?></p>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/mobile-bottom-nav.php'; ?>
