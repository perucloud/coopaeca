<h1>Recuperar contrasena</h1>
<p class="muted">Ingresa tu correo. En produccion conecta aqui tu servicio SMTP.</p>
<form method="post" action="<?= e(url('/forgot-password')) ?>" class="form">
    <?= csrf_field() ?>
    <label>Correo
        <input type="email" name="email" required autocomplete="email">
    </label>
    <button class="button primary full" type="submit">Generar enlace</button>
</form>
<p class="auth-link"><a href="<?= e(url('/login')) ?>">Volver al login</a></p>
