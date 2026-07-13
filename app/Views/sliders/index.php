<style>
    .slider-admin-list { display: flex; flex-direction: column; gap: 14px; }
    .slider-admin-card { display: grid; grid-template-columns: 280px 1fr; gap: 16px; border: 1px solid var(--line); border-radius: 12px; background: var(--panel); padding: 14px; align-items: start; }
    .slider-admin-card.is-muted { opacity: .55; }
    .slider-admin-preview { position: relative; aspect-ratio: 1800 / 650; border-radius: 8px; overflow: hidden; background: #f1f5f9; }
    .slider-admin-preview img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .slider-admin-pos { position: absolute; top: 6px; left: 6px; background: rgba(15, 23, 42, .75); color: #fff; font-size: 12px; font-weight: 600; padding: 2px 8px; border-radius: 999px; }
    .slider-admin-body h3 { margin: 0 0 4px; font-size: 16px; }
    .slider-admin-body p { margin: 0 0 6px; color: var(--muted); font-size: 13px; }
    .slider-admin-langs { display: flex; gap: 6px; margin-bottom: 8px; }
    .slider-admin-langs span { font-size: 11px; font-weight: 600; padding: 2px 8px; border-radius: 999px; background: #eef2ff; color: #4338ca; }
    .slider-admin-langs span.missing { background: #fef3c7; color: #92400e; }
    .slider-admin-actions { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; }
    .slider-admin-actions form { display: inline; }
    @media (max-width: 720px) { .slider-admin-card { grid-template-columns: 1fr; } }
</style>

<section class="service-admin-head">
    <div>
        <span class="eyebrow">Landing page</span>
        <h2>Hero / Sliders</h2>
        <p>Slides del hero principal del landing. Solo los slides activos se muestran, en el orden configurado. Imagen recomendada: 1800 × 650 px (se optimiza automáticamente).</p>
    </div>
    <a class="button primary" href="<?= e(url('/sliders/create')) ?>"><?= icon('image') ?> Nuevo slide</a>
</section>

<section class="slider-admin-list">
    <?php $total = count($items); ?>
    <?php foreach ($items as $i => $item): ?>
        <article class="slider-admin-card <?= $item['is_active'] ? '' : 'is-muted' ?>">
            <div class="slider-admin-preview">
                <img src="<?= e(url('/' . $item['disk_path'])) ?>" alt="<?= e($item['title']) ?>" loading="lazy">
                <span class="slider-admin-pos">#<?= $i + 1 ?></span>
            </div>
            <div class="slider-admin-body">
                <div class="service-admin-title">
                    <h3><?= e($item['title']) ?></h3>
                    <span class="badge <?= $item['is_active'] ? 'ok' : 'off' ?>"><?= $item['is_active'] ? 'Activo' : 'Inactivo' ?></span>
                </div>
                <div class="slider-admin-langs">
                    <span>ES</span>
                    <span class="<?= trim((string)$item['title_en']) !== '' ? '' : 'missing' ?>">EN<?= trim((string)$item['title_en']) !== '' ? '' : ' pendiente' ?></span>
                </div>
                <p><?= e($item['subtitle'] ?: 'Sin descripción.') ?></p>
                <?php if ($item['badge']): ?><p><strong>Badge:</strong> <?= e($item['badge']) ?></p><?php endif; ?>
                <div class="slider-admin-actions">
                    <form method="post" action="<?= e(url('/sliders/move')) ?>">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($item['id']) ?>"><input type="hidden" name="dir" value="up">
                        <button class="button small ghost" type="submit" <?= $i === 0 ? 'disabled' : '' ?>>▲ Subir</button>
                    </form>
                    <form method="post" action="<?= e(url('/sliders/move')) ?>">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($item['id']) ?>"><input type="hidden" name="dir" value="down">
                        <button class="button small ghost" type="submit" <?= $i === $total - 1 ? 'disabled' : '' ?>>▼ Bajar</button>
                    </form>
                    <a class="button small" href="<?= e(url('/sliders/edit?id=' . $item['id'])) ?>">Editar</a>
                    <form method="post" action="<?= e(url('/sliders/toggle')) ?>">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($item['id']) ?>">
                        <button class="button small ghost" type="submit"><?= $item['is_active'] ? 'Desactivar' : 'Activar' ?></button>
                    </form>
                    <form method="post" action="<?= e(url('/sliders/delete')) ?>" data-confirm="¿Eliminar definitivamente este slide y su imagen?">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($item['id']) ?>">
                        <button class="button small danger" type="submit">Eliminar</button>
                    </form>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if (!$items): ?>
        <article class="card">
            <p class="muted">Aún no hay slides registrados. El landing mostrará el hero por defecto hasta que crees y actives al menos un slide.</p>
        </article>
    <?php endif; ?>
</section>
