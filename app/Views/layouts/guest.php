<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? config_app('name')) ?></title>
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
</body>
</html>
