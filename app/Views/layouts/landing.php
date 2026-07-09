<?php $currentLang = landing_lang(); ?>
<!doctype html>
<html lang="<?= e($currentLang) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="<?= e(config_app('name')) ?> - <?= e(t('meta.description')) ?>">
    <title><?= e($title ?? ($settings['site_title'] ?? config_app('name'))) ?></title>
    <?php if (!empty($settings['favicon_path'])): ?>
        <link rel="icon" href="<?= e(url('/' . $settings['favicon_path'])) ?>">
    <?php endif; ?>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= e(asset('css/landing.css')) ?>">
</head>
<body>
<?= $content ?>
<script>
window.LANDING_I18N = <?= json_encode([
    'lang' => $currentLang,
    'searchEmptyTitle' => t('search.empty_title'),
    'searchEmptyText' => t('search.empty_text'),
    'searchLoading' => t('search.loading'),
    'searchNoResults' => t('search.no_results'),
    'searchNoResultsText' => t('search.no_results_text'),
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/gsap.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.5/ScrollTrigger.min.js"></script>
<script src="<?= e(asset('js/landing.js')) ?>"></script>
</body>
</html>
