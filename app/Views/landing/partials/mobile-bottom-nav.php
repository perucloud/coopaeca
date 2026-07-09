<nav class="lp-mobile-nav" id="lpMobileNav" aria-label="<?= e(t('nav.menu')) ?>">
    <a href="<?= e($activeLandingNav === 'inicio' ? '#inicio' : $homeUrl) ?>" class="lp-mobile-nav-link <?= $activeLandingNav === 'inicio' ? 'is-active' : '' ?>">
        <?= icon('home') ?><span><?= e(t('nav.home')) ?></span>
    </a>
    <a href="<?= e($sectionUrl('productos')) ?>" class="lp-mobile-nav-link <?= $activeLandingNav === 'productos' ? 'is-active' : '' ?>">
        <?= icon('package') ?><span><?= e(t('nav.products')) ?></span>
    </a>
    <a href="<?= e($sectionUrl('publicaciones')) ?>" class="lp-mobile-nav-link <?= $activeLandingNav === 'publicaciones' ? 'is-active' : '' ?>">
        <?= icon('edit') ?><span><?= e(t('nav.posts')) ?></span>
    </a>
    <a href="<?= e($sectionUrl('contacto')) ?>" class="lp-mobile-nav-link <?= $activeLandingNav === 'contacto' ? 'is-active' : '' ?>">
        <?= icon('mail') ?><span><?= e(t('mobile.email')) ?></span>
    </a>
</nav>
