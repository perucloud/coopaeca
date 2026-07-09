<div class="grid two-cols">
    <section class="card">
        <div class="section-title"><h2>Datos personales</h2></div>
        <form method="post" action="<?= e(url('/profile')) ?>" class="form">
            <?= csrf_field() ?>
            <label>Nombre
                <input name="name" value="<?= e(user()['name']) ?>" required>
            </label>
            <label>Correo
                <input value="<?= e(user()['email']) ?>" disabled>
            </label>
            <button class="button primary" type="submit">Guardar</button>
        </form>
    </section>

    <section class="card">
        <div class="section-title"><h2>Seguridad</h2></div>
        <form method="post" action="<?= e(url('/profile/password')) ?>" class="form">
            <?= csrf_field() ?>
            <label>Contrasena actual
                <input type="password" name="current_password" required>
            </label>
            <label>Nueva contrasena
                <input type="password" name="password" required minlength="8">
            </label>
            <label>Confirmar
                <input type="password" name="password_confirmation" required minlength="8">
            </label>
            <button class="button primary" type="submit">Cambiar contrasena</button>
        </form>
    </section>
</div>
