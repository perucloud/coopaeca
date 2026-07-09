<h1>Iniciar sesion</h1>
<p class="muted">Accede al panel administrativo.</p>
<form method="post" action="<?= e(url('/login')) ?>" class="form">
    <?= csrf_field() ?>
    <label>Correo
        <input type="email" name="email" value="<?= e(old('email')) ?>" required autocomplete="email">
    </label>
    <label>Contrasena
        <input type="password" name="password" required autocomplete="current-password">
    </label>
    <label class="check">
        <input type="checkbox" name="remember" value="1"> Recordarme
    </label>
    <button class="button primary full" type="submit">Entrar</button>
</form>
<p class="auth-link"><a href="<?= e(url('/forgot-password')) ?>">Olvide mi contrasena</a></p>
