<section class="card">
    <div class="section-title row">
        <div>
            <h2>Cuentas de correo</h2>
            <span>Cuentas IMAP conectadas a tu usuario del dashboard.</span>
        </div>
        <a class="button" href="<?= e(url('/dashboard/mail')) ?>">&larr; Ir a la bandeja</a>
    </div>

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Correo</th>
                    <th>Nombre</th>
                    <th>Servidor IMAP</th>
                    <th>Por defecto</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($cuentas as $c): ?>
                <tr>
                    <td><strong class="table-title"><?= e($c['email']) ?></strong></td>
                    <td><?= e($c['display_name'] ?: '—') ?></td>
                    <td><span class="badge muted"><?= e($c['imap_host'] . ':' . $c['imap_port']) ?> SSL</span></td>
                    <td>
                        <?php if ($c['is_default']): ?>
                            <span class="badge ok">Por defecto</span>
                        <?php else: ?>
                            <form method="post" action="<?= e(url('/dashboard/mail/accounts/default')) ?>">
                                <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                <button class="button small ghost" type="submit">Usar por defecto</button>
                            </form>
                        <?php endif; ?>
                    </td>
                    <td class="actions">
                        <form method="post" action="<?= e(url('/dashboard/mail/accounts/delete')) ?>" data-confirm="¿Eliminar esta cuenta? Se borrara tambien su cache de mensajes.">
                            <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                            <button class="button small ghost" type="submit">Eliminar</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$cuentas): ?>
                <tr><td colspan="5" class="empty-state">
                    <div class="empty-icon"><?= icon('mail') ?></div>
                    <p>Aun no registras ninguna cuenta de correo.</p>
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="card">
    <div class="section-title">
        <div>
            <h2>Agregar cuenta</h2>
            <span>Se probara la conexion IMAP (coopaeca.org.pe:993 SSL) antes de guardar. La contrasena se almacena cifrada.</span>
        </div>
    </div>

    <form method="post" action="<?= e(url('/dashboard/mail/accounts/store')) ?>" class="form-grid">
        <?= csrf_field() ?>
        <div class="form-group">
            <label for="email">Correo electronico *</label>
            <input type="email" id="email" name="email" value="<?= e(old('email')) ?>" required placeholder="usuario@coopaeca.org.pe">
        </div>
        <div class="form-group">
            <label for="password">Contrasena *</label>
            <input type="password" id="password" name="password" required autocomplete="new-password">
        </div>
        <div class="form-group">
            <label for="display_name">Nombre para mostrar</label>
            <input type="text" id="display_name" name="display_name" value="<?= e(old('display_name')) ?>" placeholder="Ej. Ventas CCOPAECA">
        </div>
        <div class="form-group">
            <label for="signature">Firma (opcional, se puede usar al redactar correos)</label>
            <textarea id="signature" name="signature" rows="3"><?= e(old('signature')) ?></textarea>
        </div>
        <div class="form-actions">
            <button class="button primary" type="submit"><?= icon('mail') ?> Probar conexion y guardar</button>
        </div>
    </form>
</section>
