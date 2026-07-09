<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title ?? 'Error') ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
</head>
<body class="guest-body">
    <main class="guest-shell">
        <section class="auth-card center">
            <div class="error-code"><?= e($code ?? 500) ?></div>
            <h1><?= e($message ?? 'Ocurrio un error') ?></h1>
            <a class="button primary" href="<?= e(url('/dashboard')) ?>">Volver</a>
        </section>
    </main>
</body>
</html>
