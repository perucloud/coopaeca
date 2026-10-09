<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#004d2e">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <title><?= e($title ?? config_app('name')) ?></title>
    <link rel="manifest" href="<?= e(url('/manifest.webmanifest')) ?>">
    <link rel="apple-touch-icon" href="<?= e(url('/assets/icons/pwa-192.png')) ?>">
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="guest-body">
    <main class="guest-shell">
        <section class="auth-card">
            <div class="brand">
                <div class="brand-mark">D</div>
                <div>
                    <strong><?= e(config_app('name')) ?></strong>
                    <span>Base administrativa</span>
                </div>
            </div>
            <?php if ($message = flash('status')): ?>
                <div class="alert success"><?= e($message) ?></div>
            <?php endif; ?>
            <?php $errors = errors(); if ($errors): ?>
                <div class="alert error">
                    <?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?>
                </div>
            <?php endif; ?>
            <?= $content ?>
        </section>
    </main>
    <script src="<?= e(asset('js/pwa.js')) ?>"></script>
</body>
</html>
