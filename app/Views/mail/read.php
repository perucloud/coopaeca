<style>
    .mail-meta { display: flex; flex-direction: column; gap: 4px; font-size: .92rem; }
    .mail-meta .row-line { display: flex; gap: 8px; flex-wrap: wrap; }
    .mail-meta .label { opacity: .65; min-width: 60px; }
    .mail-attachments { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 12px; }
    .mail-body { margin-top: 16px; padding: 16px; border: 1px solid rgba(125,125,125,.2); border-radius: 10px; background: #fff; color: #111; overflow-x: auto; }
    .mail-body img { max-width: 100%; height: auto; }
    .mail-body pre { white-space: pre-wrap; word-break: break-word; font: inherit; margin: 0; }
    .mail-images-note { display: flex; align-items: center; gap: 10px; margin-top: 12px; padding: 10px 12px; border-radius: 8px; background: rgba(240,180,0,.12); font-size: .9rem; }
    .mail-actions-bar { display: flex; gap: 8px; flex-wrap: wrap; margin-top: 16px; padding-top: 16px; border-top: 1px solid rgba(125,125,125,.2); }
</style>

<section class="card">
    <div class="section-title row">
        <div>
            <h2><?= e($mensaje['subject'] ?: '(sin asunto)') ?></h2>
            <span><?= e($cuenta['email']) ?> — <?= e($carpeta) ?></span>
        </div>
        <div style="display:flex; gap:8px; flex-wrap:wrap">
            <a class="button" href="<?= e(url('/dashboard/mail?account=' . (int)$cuenta['id'] . '&folder=' . rawurlencode($carpeta))) ?>">&larr; Volver</a>
        </div>
    </div>

    <div class="mail-meta">
        <div class="row-line">
            <span class="label">De:</span>
            <strong><?= e($mensaje['from_name'] ?: $mensaje['from_email']) ?></strong>
            <?php if ($mensaje['from_name'] && $mensaje['from_email']): ?><span class="text-muted">&lt;<?= e($mensaje['from_email']) ?>&gt;</span><?php endif; ?>
        </div>
        <?php if ($mensaje['to']): ?>
        <div class="row-line"><span class="label">Para:</span><span><?= e(implode(', ', $mensaje['to'])) ?></span></div>
        <?php endif; ?>
        <?php if ($mensaje['cc']): ?>
        <div class="row-line"><span class="label">CC:</span><span><?= e(implode(', ', $mensaje['cc'])) ?></span></div>
        <?php endif; ?>
        <div class="row-line"><span class="label">Fecha:</span><span><?= e($mensaje['date'] ? date('d/m/Y H:i', strtotime($mensaje['date'])) : 'Desconocida') ?></span></div>
    </div>

    <?php if ($mensaje['adjuntos']): ?>
    <div class="mail-attachments">
        <?php foreach ($mensaje['adjuntos'] as $a): ?>
        <a class="button small"
           href="<?= e(url('/dashboard/mail/attachment?account=' . (int)$cuenta['id'] . '&folder=' . rawurlencode($carpeta) . '&uid=' . (int)$mensaje['uid'] . '&index=' . (int)$a['index'])) ?>">
            <?= icon('paperclip') ?> <?= e($a['name']) ?>
            <span class="text-muted">(<?= e($a['size'] > 0 ? number_format($a['size'] / 1024, 1) . ' KB' : '?') ?>)</span>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if ($imagenesBloqueadas > 0): ?>
    <div class="mail-images-note" id="mailImagesNote">
        <?= icon('image') ?>
        <span>Se bloquearon <?= (int)$imagenesBloqueadas ?> imagen(es) remota(s) por privacidad.</span>
        <button class="button small" type="button" id="mailShowImagesBtn">Mostrar imagenes</button>
    </div>
    <?php endif; ?>

    <div class="mail-body" id="mailBody">
        <?php if ($mensaje['html'] !== ''): ?>
            <?= $mensaje['html'] /* HTML ya sanitizado con HTMLPurifier en el controlador */ ?>
        <?php elseif ($mensaje['text'] !== ''): ?>
            <pre><?= e($mensaje['text']) ?></pre>
        <?php else: ?>
            <p class="text-muted">Este mensaje no tiene contenido visible.</p>
        <?php endif; ?>
    </div>

    <div class="mail-actions-bar">
        <?php $qs = 'account=' . (int)$cuenta['id'] . '&folder=' . rawurlencode($carpeta) . '&uid=' . (int)$mensaje['uid']; ?>
        <a class="button primary" href="<?= e(url('/dashboard/mail/compose?mode=reply&' . $qs)) ?>"><?= icon('edit') ?> Responder</a>
        <a class="button" href="<?= e(url('/dashboard/mail/compose?mode=reply_all&' . $qs)) ?>">Responder a todos</a>
        <a class="button" href="<?= e(url('/dashboard/mail/compose?mode=forward&' . $qs)) ?>">Reenviar</a>

        <form method="post" action="<?= e(url('/dashboard/mail/seen')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="account" value="<?= (int)$cuenta['id'] ?>">
            <input type="hidden" name="folder" value="<?= e($carpeta) ?>">
            <input type="hidden" name="uid" value="<?= (int)$mensaje['uid'] ?>">
            <input type="hidden" name="seen" value="">
            <button class="button ghost" type="submit"><?= icon('mail') ?> Marcar no leido</button>
        </form>

        <?php if (count($carpetas) > 1): ?>
        <form method="post" action="<?= e(url('/dashboard/mail/move')) ?>" style="display:flex; gap:6px">
            <?= csrf_field() ?>
            <input type="hidden" name="account" value="<?= (int)$cuenta['id'] ?>">
            <input type="hidden" name="folder" value="<?= e($carpeta) ?>">
            <input type="hidden" name="uid" value="<?= (int)$mensaje['uid'] ?>">
            <select name="destino" class="input">
                <?php foreach ($carpetas as $f): if (strcasecmp($f['path'], $carpeta) === 0) continue; ?>
                <option value="<?= e($f['path']) ?>"><?= e($f['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="button ghost" type="submit"><?= icon('folder') ?> Mover</button>
        </form>
        <?php endif; ?>

        <form method="post" action="<?= e(url('/dashboard/mail/delete')) ?>" data-confirm="¿Mover este mensaje a la papelera?">
            <?= csrf_field() ?>
            <input type="hidden" name="account" value="<?= (int)$cuenta['id'] ?>">
            <input type="hidden" name="folder" value="<?= e($carpeta) ?>">
            <input type="hidden" name="uid" value="<?= (int)$mensaje['uid'] ?>">
            <button class="button ghost" type="submit"><?= icon('trash') ?> Eliminar</button>
        </form>
    </div>
</section>

<script>
(function () {
    // Restaura las imagenes remotas bloqueadas solo si el usuario lo pide
    var btn = document.getElementById('mailShowImagesBtn');
    if (!btn) return;
    btn.addEventListener('click', function () {
        document.querySelectorAll('#mailBody img[data-remote-src]').forEach(function (img) {
            img.src = img.getAttribute('data-remote-src');
            img.removeAttribute('data-remote-src');
        });
        document.getElementById('mailImagesNote').remove();
    });
})();
</script>
