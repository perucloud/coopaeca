<h1>Nueva contrasena</h1>
<form method="post" action="<?= e(url('/reset-password')) ?>" class="form">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token) ?>">
    <label>Nueva contrasena
        <input type="password" name="password" required minlength="8" autocomplete="new-password">
    </label>
    <label>Confirmar contrasena
        <input type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">
    </label>
    <button class="button primary full" type="submit">Actualizar</button>
</form>
