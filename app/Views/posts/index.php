<section class="posts-admin-head">
    <div>
        <span class="eyebrow">Publicaciones</span>
        <h2>Noticias y publicaciones</h2>
        <p>Administra las notas que se muestran en la seccion publica Historias del cacao.</p>
    </div>
    <a class="button primary" href="<?= e(url('/posts/create')) ?>"><?= icon('edit') ?> Nueva noticia</a>
</section>

<section class="posts-admin-list">
    <?php foreach ($items as $item): ?>
        <article class="post-row">
            <div class="post-row-thumb">
                <?php if (!empty($item['image_path'])): ?>
                    <img src="<?= e(url('/' . $item['image_path'])) ?>" alt="<?= e($item['title']) ?>">
                <?php else: ?>
                    <?= icon('image') ?>
                <?php endif; ?>
            </div>
            <div class="post-row-body">
                <div class="post-row-title">
                    <h3><?= e($item['title']) ?></h3>
                    <span class="badge <?= $item['status'] === 'published' ? 'ok' : 'off' ?>"><?= e($item['status']) ?></span>
                </div>
                <p><?= e($item['excerpt'] ?: 'Sin resumen registrado.') ?></p>
                <div class="post-row-meta">
                    <span><?= icon('tag') ?> <?= e($item['category'] ?? 'General') ?></span>
                    <span><?= icon('user') ?> <?= e($item['author_name']) ?></span>
                    <span><?= icon('calendar') ?> <?= e($item['published_at'] ?: $item['created_at']) ?></span>
                </div>
            </div>
            <div class="post-row-actions">
                <a class="button small" href="<?= e(url('/posts/edit?id=' . $item['id'])) ?>">Editar</a>
                <form method="post" action="<?= e(url('/posts/delete')) ?>" data-confirm="Eliminar esta noticia?">
                    <?= csrf_field() ?><input type="hidden" name="id" value="<?= e($item['id']) ?>">
                    <button class="button small ghost" type="submit">Eliminar</button>
                </form>
            </div>
        </article>
    <?php endforeach; ?>

    <?php if (!$items): ?>
        <div class="card">Sin noticias registradas.</div>
    <?php endif; ?>
</section>
