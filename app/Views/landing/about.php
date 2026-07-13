<?php
$headerLogo = !empty($settings['header_logo_path']) ? url('/' . $settings['header_logo_path']) : asset('img/logo-ccopaeca.png');
$footerLogo = !empty($settings['footer_logo_path']) ? url('/' . $settings['footer_logo_path']) : asset('img/logowhite.png');
$aboutTitle = localized_setting($settings, 'about_more_title', t('about.more_title'));
$aboutBody = localized_setting($settings, 'about_more_body', t('about.more_body'));
$historyTitle = localized_setting($settings, 'about_history_title', t('about.history_title'));
$historyBody = localized_setting($settings, 'about_history_body', t('about.history_body'));
$mission = localized_setting($settings, 'about_mission', t('about.mission_text'));
$vision = localized_setting($settings, 'about_vision', t('about.vision_text'));
$values = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', localized_setting($settings, 'about_values', t('about.values'))))));
$activeLandingNav = 'nosotros';
?>

<?php require __DIR__ . '/partials/header.php'; ?>

<main class="lp-about-page">
    <section class="lp-about-hero">
        <div class="lp-container lp-about-hero-inner">
            <div>
                <span class="lp-tag"><?= e(t('about.tag')) ?></span>
                <h1><?= e($aboutTitle) ?></h1>
                <p><?= e($aboutBody) ?></p>
                <a href="<?= e(lurl('/#contacto')) ?>" class="lp-btn lp-btn-primary"><?= e(t('nav.contact')) ?></a>
            </div>
            <div class="lp-about-hero-image" style="background-image:url('<?= e(asset('img/hero/cacao1.png')) ?>')"></div>
        </div>
    </section>

    <section class="lp-section">
        <div class="lp-container lp-about-content">
            <article>
                <span class="lp-tag"><?= e(t('about.history_tag')) ?></span>
                <h2><?= e($historyTitle) ?></h2>
                <p><?= e($historyBody) ?></p>
            </article>
            <aside>
                <h3><?= e(t('about.mission')) ?></h3>
                <p><?= e($mission) ?></p>
                <h3><?= e(t('about.vision')) ?></h3>
                <p><?= e($vision) ?></p>
            </aside>
        </div>
    </section>

    <section class="lp-section lp-section-alt">
        <div class="lp-container">
            <div class="lp-section-head">
                <span class="lp-tag"><?= e(t('about.commitments')) ?></span>
                <h2><?= e(t('about.defines')) ?></h2>
            </div>
            <div class="lp-about-values">
                <?php foreach ($values as $value): ?>
                    <div><?= icon('check-circle') ?><span><?= e($value) ?></span></div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
</main>

<?php require __DIR__ . '/partials/footer.php'; ?>
