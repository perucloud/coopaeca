<style>
    .mail-layout { display: grid; grid-template-columns: 220px 1fr; gap: 16px; align-items: start; }
    .mail-folders { display: flex; flex-direction: column; gap: 2px; }
    .mail-folder { display: flex; align-items: center; gap: 8px; padding: 8px 10px; border-radius: 8px; color: inherit; text-decoration: none; font-size: .92rem; }
    .mail-folder:hover { background: rgba(125,125,125,.12); }
    .mail-folder.active { background: rgba(125,125,125,.18); font-weight: 600; }
    .mail-folder .nav-badge { margin-left: auto; }
    .mail-list { display: flex; flex-direction: column; }
    .mail-row { display: grid; grid-template-columns: 200px 1fr auto auto; gap: 12px; align-items: center; padding: 10px 12px; border-bottom: 1px solid rgba(125,125,125,.18); color: inherit; text-decoration: none; }
    .mail-row:hover { background: rgba(125,125,125,.08); }
    .mail-row.unread { font-weight: 700; }
    .mail-row .mail-from, .mail-row .mail-subject { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .mail-row .mail-date { font-size: .82rem; opacity: .7; white-space: nowrap; }
    .mail-toolbar { display: flex; gap: 8px; align-items: center; flex-wrap: wrap; }
    .mail-pager { display: flex; gap: 8px; align-items: center; justify-content: flex-end; padding-top: 12px; }
    @media (max-width: 800px) { .mail-layout { grid-template-columns: 1fr; } .mail-row { grid-template-columns: 1fr auto; } .mail-row .mail-from { display: none; } }
</style>

<section class="card">
    <div class="section-title row">
        <div>
            <h2>Correo</h2>
            <span><?= e($cuenta['email']) ?> — <?= e($carpeta) ?> (<?= (int)$total ?> mensajes en cache)</span>
        </div>
        <div class="mail-toolbar">
            <?php if (count($cuentas) > 1): ?>
            <select id="mailAccountSelect" class="input" title="Cuenta">
                <?php foreach ($cuentas as $c): ?>
                <option value="<?= (int)$c['id'] ?>" <?= (int)$c['id'] === (int)$cuenta['id'] ? 'selected' : '' ?>><?= e($c['email']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <a class="button primary" href="<?= e(url('/dashboard/mail/compose?account=' . (int)$cuenta['id'])) ?>"><?= icon('edit') ?> Redactar</a>
            <button class="button" id="mailSyncBtn" type="button"><?= icon('refresh') ?> <span>Actualizar</span></button>
            <a class="button" href="<?= e(url('/dashboard/mail/accounts')) ?>"><?= icon('settings') ?> Cuentas</a>
        </div>
    </div>

    <div class="mail-layout">
        <aside class="mail-folders">
            <?php foreach ($carpetas as $f): ?>
            <a class="mail-folder <?= strcasecmp($f['path'], $carpeta) === 0 ? 'active' : '' ?>"
               href="<?= e(url('/dashboard/mail?account=' . (int)$cuenta['id'] . '&folder=' . rawurlencode($f['path']))) ?>">
                <?= icon(strcasecmp($f['path'], 'INBOX') === 0 ? 'inbox' : 'folder') ?>
                <span><?= e($f['name']) ?></span>
                <?php if ($f['unseen'] > 0): ?><em class="nav-badge"><?= (int)$f['unseen'] ?></em><?php endif; ?>
            </a>
            <?php endforeach; ?>
        </aside>

        <div>
            <div class="mail-list" id="mailList">
                <?php foreach ($mensajes as $m): ?>
                <a class="mail-row <?= $m['is_seen'] ? '' : 'unread' ?>"
                   href="<?= e(url('/dashboard/mail/read?account=' . (int)$cuenta['id'] . '&folder=' . rawurlencode($carpeta) . '&uid=' . (int)$m['uid'])) ?>">
                    <span class="mail-from"><?= e($m['from_name'] ?: $m['from_email'] ?: '(sin remitente)') ?></span>
                    <span class="mail-subject"><?= e($m['subject'] ?: '(sin asunto)') ?></span>
                    <span><?= $m['has_attachments'] ? icon('paperclip') : '' ?></span>
                    <span class="mail-date"><?= e($m['date'] ? date('d/m/Y H:i', strtotime($m['date'])) : '') ?></span>
                </a>
                <?php endforeach; ?>

                <?php if (!$mensajes): ?>
                <div class="empty-state">
                    <div class="empty-icon"><?= icon('inbox') ?></div>
                    <p>No hay mensajes en cache para esta carpeta.</p>
                    <p>Presiona <strong>Actualizar</strong> para sincronizar con el servidor.</p>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($paginas > 1): ?>
            <div class="mail-pager">
                <?php $base = url('/dashboard/mail?account=' . (int)$cuenta['id'] . '&folder=' . rawurlencode($carpeta)); ?>
                <?php if ($pagina > 1): ?><a class="button small" href="<?= e($base . '&page=' . ($pagina - 1)) ?>">&larr; Anteriores</a><?php endif; ?>
                <span class="text-muted">Pagina <?= (int)$pagina ?> de <?= (int)$paginas ?></span>
                <?php if ($pagina < $paginas): ?><a class="button small" href="<?= e($base . '&page=' . ($pagina + 1)) ?>">Siguientes &rarr;</a><?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
(function () {
    // Boton Actualizar: sincroniza via /sync y recarga la bandeja
    var btn = document.getElementById('mailSyncBtn');
    var syncUrl = <?= json_encode(url('/dashboard/mail/sync?account=' . (int)$cuenta['id'] . '&folder=' . rawurlencode($carpeta))) ?>;

    btn.addEventListener('click', function () {
        var label = btn.querySelector('span');
        btn.disabled = true;
        label.textContent = 'Sincronizando...';

        fetch(syncUrl, { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (!data.ok) throw new Error(data.error || 'Error');
                window.location.reload();
            })
            .catch(function () {
                label.textContent = 'Error al sincronizar';
                btn.disabled = false;
                setTimeout(function () { label.textContent = 'Actualizar'; }, 2500);
            });
    });

    // Selector de cuenta (si hay varias)
    var select = document.getElementById('mailAccountSelect');
    if (select) {
        select.addEventListener('change', function () {
            window.location.href = <?= json_encode(url('/dashboard/mail')) ?> + '?account=' + encodeURIComponent(select.value);
        });
    }
})();
</script>
