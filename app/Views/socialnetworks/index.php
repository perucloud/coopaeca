<style>
    .sn-list { display: flex; flex-direction: column; gap: 10px; }
    .sn-row { display: flex; align-items: center; gap: 14px; padding: 14px 16px; border: 1px solid var(--line); border-radius: 12px; background: var(--panel); }
    .sn-row.is-inactive { opacity: .55; }
    .sn-icon-badge { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; flex-shrink: 0; }
    .sn-icon-badge svg { width: 20px; height: 20px; }
    .sn-info { flex: 1; min-width: 0; }
    .sn-info strong { display: block; font-size: 14px; }
    .sn-info span { display: block; font-size: 12.5px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sn-actions { display: flex; gap: 8px; flex-shrink: 0; }
</style>

<section class="card">
    <div class="section-title row">
        <div>
            <h2>Redes sociales</h2>
            <span>Enlaces que se muestran en el pie de página del sitio público.</span>
        </div>
        <button type="button" class="button primary" id="btnNuevaRed"><?= icon('share') ?> Agregar red social</button>
    </div>

    <div class="sn-list">
        <?php foreach ($items as $item): ?>
        <div class="sn-row <?= $item['is_active'] ? '' : 'is-inactive' ?>">
            <div class="sn-icon-badge" style="background:<?= e($plataformas[$item['platform_key']]['color'] ?? '#6b6355') ?>">
                <?= social_icon($item['platform_key'] ?? 'otro') ?>
            </div>
            <div class="sn-info">
                <strong><?= e($item['platform']) ?></strong>
                <span><?= e($item['url']) ?></span>
            </div>
            <div class="sn-actions">
                <button type="button" class="button small btn-editar-red"
                        data-id="<?= (int)$item['id'] ?>"
                        data-platform-key="<?= e($item['platform_key'] ?? 'otro') ?>"
                        data-custom-name="<?= e($item['platform']) ?>"
                        data-url="<?= e($item['url']) ?>"
                        data-position="<?= (int)$item['position'] ?>"
                        data-active="<?= (int)$item['is_active'] ?>">
                    Editar
                </button>
                <form method="post" action="<?= e(url('/social-networks/toggle')) ?>">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                    <button class="button small ghost" type="submit"><?= $item['is_active'] ? 'Ocultar' : 'Mostrar' ?></button>
                </form>
                <form method="post" action="<?= e(url('/social-networks/delete')) ?>" data-confirm="¿Eliminar esta red social?">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                    <button class="button small danger" type="submit">Eliminar</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
        <?php if (!$items): ?>
        <div class="empty-state">
            <div class="empty-icon"><?= icon('share') ?></div>
            <p>Aún no hay redes sociales registradas.</p>
        </div>
        <?php endif; ?>
    </div>
</section>

<div class="modal-overlay" id="snModal" style="display:none">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="snModalTitle">Nueva red social</h3>
            <button type="button" class="modal-close" id="snModalClose">&times;</button>
        </div>
        <form method="post" id="snForm" action="<?= e(url('/social-networks/store')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" id="sn_id">
            <div class="modal-body">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="sn_platform_key">Plataforma *</label>
                        <select id="sn_platform_key" name="platform_key" required>
                            <?php foreach ($plataformas as $key => $p): ?>
                            <option value="<?= e($key) ?>"><?= e($p['label']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" id="sn_custom_wrap" style="display:none">
                        <label for="sn_custom_name">Nombre a mostrar</label>
                        <input type="text" id="sn_custom_name" name="custom_name" placeholder="Ej. Behance">
                    </div>
                    <div class="form-group">
                        <label for="sn_url">URL *</label>
                        <input type="url" id="sn_url" name="url" required placeholder="https://facebook.com/tuempresa">
                    </div>
                    <div class="form-group">
                        <label for="sn_position">Orden</label>
                        <input type="number" id="sn_position" name="position" value="0">
                    </div>
                </div>
                <label class="check-box" style="margin-top:12px">
                    <input type="checkbox" name="is_active" id="sn_active" value="1" checked>
                    <span>Visible en el sitio</span>
                </label>
            </div>
            <div class="modal-footer">
                <button type="button" class="button ghost" id="snModalCancel">Cancelar</button>
                <button type="submit" class="button primary">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('snModal');
    const form = document.getElementById('snForm');
    const title = document.getElementById('snModalTitle');
    const platformSelect = document.getElementById('sn_platform_key');
    const customWrap = document.getElementById('sn_custom_wrap');

    function toggleCustomWrap() {
        customWrap.style.display = platformSelect.value === 'otro' ? 'block' : 'none';
    }
    platformSelect.addEventListener('change', toggleCustomWrap);

    function openModal() { modal.style.display = 'flex'; }
    function closeModal() { modal.style.display = 'none'; }

    function resetForm() {
        form.reset();
        document.getElementById('sn_id').value = '';
        form.action = <?= json_encode(url('/social-networks/store')) ?>;
        title.textContent = 'Nueva red social';
        toggleCustomWrap();
    }

    document.getElementById('btnNuevaRed').addEventListener('click', function () {
        resetForm();
        openModal();
    });

    document.querySelectorAll('.btn-editar-red').forEach(function (btn) {
        btn.addEventListener('click', function () {
            resetForm();
            const d = btn.dataset;
            title.textContent = 'Editar: ' + d.customName;
            form.action = <?= json_encode(url('/social-networks/update')) ?>;
            document.getElementById('sn_id').value = d.id;
            platformSelect.value = d.platformKey;
            document.getElementById('sn_custom_name').value = d.customName;
            document.getElementById('sn_url').value = d.url;
            document.getElementById('sn_position').value = d.position;
            document.getElementById('sn_active').checked = d.active === '1';
            toggleCustomWrap();
            openModal();
        });
    });

    document.getElementById('snModalClose').addEventListener('click', closeModal);
    document.getElementById('snModalCancel').addEventListener('click', closeModal);
    modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
})();
</script>
