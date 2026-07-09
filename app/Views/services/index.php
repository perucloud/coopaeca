<section class="service-admin-head">
    <div>
        <span class="eyebrow">Landing page</span>
        <h2>Servicios</h2>
        <p>Estos servicios se sincronizan directamente con la sección pública de servicios. Solo los activos aparecen en el landing y se ordenan por el campo orden.</p>
    </div>
    <a class="button primary" href="<?= e(url('/services/create')) ?>"><?= icon('layers') ?> Nuevo servicio</a>
</section>

<section class="service-admin-grid">
    <?php foreach ($items as $item): ?>
        <article class="service-admin-card <?= $item['is_active'] ? '' : 'is-muted' ?>">
            <div class="service-admin-icon"><?= icon($item['icon_name'] ?: 'layers') ?></div>
            <div class="service-admin-body">
                <div class="service-admin-title">
                    <h3><?= e($item['name']) ?></h3>
                    <span class="badge <?= $item['is_active'] ? 'ok' : 'off' ?>"><?= $item['is_active'] ? 'Activo' : 'Inactivo' ?></span>
                </div>
                <p><?= e($item['short_description'] ?: 'Sin descripción corta.') ?></p>
                <div class="service-admin-meta">
                    <span>Orden <?= e($item['position']) ?></span>
                    <span>Icono <?= e($item['icon_name'] ?: 'layers') ?></span>
                </div>
                <div class="actions">
                    <a class="button small" href="<?= e(url('/services/edit?id=' . $item['id'])) ?>">Editar</a>
                    <form method="post" action="<?= e(url('/services/toggle')) ?>">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($item['id']) ?>">
                        <button class="button small ghost" type="submit"><?= $item['is_active'] ? 'Ocultar del landing' : 'Mostrar en landing' ?></button>
                    </form>
                    <form method="post" action="<?= e(url('/services/delete')) ?>" data-confirm="¿Eliminar definitivamente este servicio?">
                        <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($item['id']) ?>">
                        <button class="button small danger" type="submit">Eliminar</button>
                    </form>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if (!$items): ?>
        <article class="card">
            <p class="muted">Aún no hay servicios registrados.</p>
        </article>
    <?php endif; ?>
</section>
