<?php $errors = errors(); ?>

<div class="page-header">
    <div>
        <h1>Metodos de pago</h1>
        <p>Configura las opciones visibles en el checkout publico.</p>
    </div>
</div>

<?php if ($errors): ?>
    <div class="alert alert-error"><?php foreach ($errors as $error): ?><p><?= e($error) ?></p><?php endforeach; ?></div>
<?php endif; ?>

<section class="payment-method-layout">
    <div class="page-card">
        <div class="card-title-row">
            <div>
                <h2>Nuevo metodo</h2>
                <p>Usa nombres claros: Transferencia bancaria, Yape, Plin u otro.</p>
            </div>
        </div>
        <form action="<?= e(url('/payment-methods/store')) ?>" method="post" enctype="multipart/form-data" class="payment-method-form">
            <?= csrf_field() ?>
            <label>Nombre
                <input type="text" name="name" value="<?= e(old('name')) ?>" required>
            </label>
            <label>Tipo
                <select name="type">
                    <option value="bank_transfer">Transferencia bancaria</option>
                    <option value="digital_wallet">Billetera digital</option>
                    <option value="other">Otro</option>
                </select>
            </label>
            <label>Etiqueta de cuenta
                <input type="text" name="account_label" value="<?= e(old('account_label')) ?>" placeholder="Cuenta corriente, Yape empresa...">
            </label>
            <label>Numero / contacto
                <input type="text" name="account_number" value="<?= e(old('account_number')) ?>">
            </label>
            <label>Titular
                <input type="text" name="holder_name" value="<?= e(old('holder_name')) ?>">
            </label>
            <label>Orden
                <input type="number" name="position" value="<?= e(old('position', 0)) ?>">
            </label>
            <label class="span-2">QR de pago
                <input type="file" name="qr_image" accept="image/jpeg,image/png,image/webp">
                <small>Opcional. Recomendado para Yape, Plin u otra billetera digital.</small>
            </label>
            <label class="span-2">Instrucciones
                <textarea name="instructions" rows="4"><?= e(old('instructions')) ?></textarea>
            </label>
            <label class="switch-line">
                <input type="checkbox" name="is_active" value="1" checked>
                <span>Activo en checkout</span>
            </label>
            <div class="form-actions">
                <button class="button primary" type="submit"><?= icon('save') ?> Guardar metodo</button>
            </div>
        </form>
    </div>

    <div class="payment-method-list">
        <?php foreach ($methods as $method): ?>
            <article class="payment-method-card">
                <header>
                    <div class="payment-method-icon"><?= icon('credit-card') ?></div>
                    <div>
                        <h2><?= e($method['name']) ?></h2>
                        <span><?= e(match ($method['type']) {
                            'bank_transfer' => 'Transferencia bancaria',
                            'digital_wallet' => 'Billetera digital',
                            default => 'Otro',
                        }) ?></span>
                    </div>
                    <em class="badge <?= (int)$method['is_active'] === 1 ? 'ok' : 'muted' ?>"><?= (int)$method['is_active'] === 1 ? 'Activo' : 'Inactivo' ?></em>
                </header>

                <?php if (!empty($method['qr_path'])): ?>
                    <div class="payment-method-qr">
                        <img src="<?= e(url('/' . $method['qr_path'])) ?>" alt="QR <?= e($method['name']) ?>">
                        <div>
                            <strong>QR activo</strong>
                            <span><?= e($method['qr_name'] ?? 'Imagen QR') ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <form action="<?= e(url('/payment-methods/update')) ?>" method="post" enctype="multipart/form-data" class="payment-method-form compact">
                    <?= csrf_field() ?>
                    <input type="hidden" name="id" value="<?= (int)$method['id'] ?>">
                    <label>Nombre
                        <input type="text" name="name" value="<?= e($method['name']) ?>" required>
                    </label>
                    <label>Tipo
                        <select name="type">
                            <option value="bank_transfer" <?= $method['type'] === 'bank_transfer' ? 'selected' : '' ?>>Transferencia bancaria</option>
                            <option value="digital_wallet" <?= $method['type'] === 'digital_wallet' ? 'selected' : '' ?>>Billetera digital</option>
                            <option value="other" <?= $method['type'] === 'other' ? 'selected' : '' ?>>Otro</option>
                        </select>
                    </label>
                    <label>Etiqueta
                        <input type="text" name="account_label" value="<?= e($method['account_label']) ?>">
                    </label>
                    <label>Numero / contacto
                        <input type="text" name="account_number" value="<?= e($method['account_number']) ?>">
                    </label>
                    <label>Titular
                        <input type="text" name="holder_name" value="<?= e($method['holder_name']) ?>">
                    </label>
                    <label>Orden
                        <input type="number" name="position" value="<?= (int)$method['position'] ?>">
                    </label>
                    <label class="span-2">Reemplazar QR
                        <input type="file" name="qr_image" accept="image/jpeg,image/png,image/webp">
                        <small>Si no seleccionas archivo, se conserva el QR actual.</small>
                    </label>
                    <label class="span-2">Instrucciones
                        <textarea name="instructions" rows="3"><?= e($method['instructions']) ?></textarea>
                    </label>
                    <label class="switch-line">
                        <input type="checkbox" name="is_active" value="1" <?= (int)$method['is_active'] === 1 ? 'checked' : '' ?>>
                        <span>Activo en checkout</span>
                    </label>
                    <div class="form-actions">
                        <button class="button primary" type="submit"><?= icon('save') ?> Actualizar</button>
                    </div>
                </form>
            </article>
        <?php endforeach; ?>
    </div>
</section>
