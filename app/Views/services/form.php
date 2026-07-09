<?php
$editing = is_array($item);
$icons = [
    'layers' => 'Capas',
    'package' => 'Producto',
    'shield' => 'Calidad',
    'activity' => 'Proceso',
    'share' => 'Comercialización',
    'users' => 'Productores',
    'check-circle' => 'Verificado',
    'tag' => 'Etiqueta',
    'image' => 'Imagen',
];
$currentIcon = old('icon_name', $item['icon_name'] ?? 'layers');
?>
<section class="card narrow">
    <div class="section-title">
        <h2><?= $editing ? 'Editar servicio' : 'Nuevo servicio' ?></h2>
        <span>Este contenido se mostrará en la sección Servicios del landing page.</span>
    </div>
    <form method="post" action="<?= e(url($editing ? '/services/update' : '/services/store')) ?>" class="form">
        <?= csrf_field() ?>
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= e($item['id']) ?>"><?php endif; ?>

        <label>Nombre del servicio
            <input name="name" value="<?= e(old('name', $item['name'] ?? '')) ?>" required maxlength="180">
        </label>

        <label>Nombre del servicio en ingles
            <input name="name_en" value="<?= e(old('name_en', $item['name_en'] ?? '')) ?>" maxlength="180" placeholder="English service name">
        </label>

        <label>Descripción corta para la tarjeta
            <textarea name="short_description" rows="3" maxlength="300"><?= e(old('short_description', $item['short_description'] ?? '')) ?></textarea>
        </label>

        <label>Descripcion corta en ingles
            <textarea name="short_description_en" rows="3" maxlength="300" placeholder="English short description for the landing card"><?= e(old('short_description_en', $item['short_description_en'] ?? '')) ?></textarea>
        </label>

        <label>Descripción interna o ampliada
            <textarea name="description" rows="7"><?= e(old('description', $item['description'] ?? '')) ?></textarea>
        </label>

        <label>Descripcion en ingles
            <textarea name="description_en" rows="7" placeholder="English extended description"><?= e(old('description_en', $item['description_en'] ?? '')) ?></textarea>
        </label>

        <div class="form-grid-3">
            <label>Icono
                <select name="icon_name">
                    <?php foreach ($icons as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= $currentIcon === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label>Orden
                <input type="number" name="position" value="<?= e(old('position', $item['position'] ?? 0)) ?>">
            </label>
            <label>Estado
                <select name="is_active">
                    <option value="1" <?= (int)old('is_active', $item['is_active'] ?? 1) === 1 ? 'selected' : '' ?>>Activo en landing</option>
                    <option value="0" <?= (int)old('is_active', $item['is_active'] ?? 1) === 0 ? 'selected' : '' ?>>Oculto</option>
                </select>
            </label>
        </div>

        <label>Meta título
            <input name="meta_title" value="<?= e(old('meta_title', $item['meta_title'] ?? '')) ?>">
        </label>
        <label>Meta titulo en ingles
            <input name="meta_title_en" value="<?= e(old('meta_title_en', $item['meta_title_en'] ?? '')) ?>">
        </label>
        <label>Meta descripción
            <input name="meta_description" value="<?= e(old('meta_description', $item['meta_description'] ?? '')) ?>">
        </label>
        <label>Meta descripcion en ingles
            <input name="meta_description_en" value="<?= e(old('meta_description_en', $item['meta_description_en'] ?? '')) ?>">
        </label>
        <div class="form-actions">
            <button class="button primary" type="submit">Guardar servicio</button>
            <a class="button ghost" href="<?= e(url('/services')) ?>">Cancelar</a>
        </div>
    </form>
</section>
