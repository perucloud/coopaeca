<section class="service-admin-head">
    <div>
        <span class="eyebrow">Landing page</span>
        <h2>Galería</h2>
        <p>Álbumes de fotos que se muestran en la página pública de galería. Solo los álbumes activos son visibles.</p>
    </div>
    <a class="button primary" href="<?= e(url('/galleries/create')) ?>"><?= icon('image') ?> Nuevo álbum</a>
</section>

<section class="service-admin-grid">
    <?php foreach ($items as $item): ?>
        <article class="service-admin-card <?= $item['is_active'] ? '' : 'is-muted' ?>">
            <div class="service-admin-icon">
                <?php if ($item['cover_path']): ?>
                    <img src="<?= e(url('/' . $item['cover_path'])) ?>" alt="<?= e($item['title']) ?>" style="width:100%;height:100%;object-fit:cover;border-radius:8px">
                <?php else: ?>
                    <?= icon('image') ?>
                <?php endif; ?>
            </div>
            <div class="service-admin-body">
                <div class="service-admin-title">
                    <h3><?= e($item['title']) ?></h3>
                    <span class="badge <?= $item['is_active'] ? 'ok' : 'off' ?>"><?= $item['is_active'] ? 'Activo' : 'Inactivo' ?></span>
                </div>
                <p><?= e($item['description'] ?: 'Sin descripción.') ?></p>
                <div class="service-admin-meta">
                    <span>Orden <?= e($item['position']) ?></span>
                    <span><?= (int)$item['image_count'] ?> foto(s)</span>
                </div>
                <div class="actions">
                    <a class="button small" href="<?= e(url('/galleries/edit?id=' . $item['id'])) ?>">Editar</a>
                    <form method="post" action="<?= e(url('/galleries/toggle')) ?>">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($item['id']) ?>">
                        <button class="button small ghost" type="submit"><?= $item['is_active'] ? 'Ocultar del landing' : 'Mostrar en landing' ?></button>
                    </form>
                    <form method="post" action="<?= e(url('/galleries/delete')) ?>" data-confirm="¿Eliminar definitivamente este álbum?">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($item['id']) ?>">
                        <button class="button small danger" type="submit">Eliminar</button>
                    </form>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if (!$items): ?>
        <article class="card">
            <p class="muted">Aún no hay álbumes registrados.</p>
        </article>
    <?php endif; ?>
</section>
