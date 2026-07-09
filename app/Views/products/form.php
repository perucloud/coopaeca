<?php
$editing = is_array($item);
$coverId = (int)old('cover_image_id', $item['cover_image_id'] ?? 0);
$coverPreview = '';
if ($coverId && !empty($allImages)) {
    foreach ($allImages as $img) {
        if ((int)$img['id'] === $coverId) {
            $coverPreview = url('/' . $img['disk_path']);
            break;
        }
    }
}
$productStatus = old('status', $item['status'] ?? 'draft');
$productFeatured = (int)old('is_featured', $item['is_featured'] ?? 0);
?>

<!-- ════ PAGE HEADER ════ -->
<div class="prod-editor-header">
    <div>
        <a href="<?= e(url('/products')) ?>" class="prod-back-link"><?= icon('arrow-left') ?> Volver a Productos</a>
        <h1 class="prod-editor-title"><?= $editing ? 'Editar producto' : 'Nuevo producto' ?></h1>
        <p class="prod-editor-sub"><?= $editing ? 'Modifica la información y guarda los cambios.' : 'Completa los datos para publicar un nuevo producto en el catálogo.' ?></p>
    </div>
    <?php if ($editing): ?>
    <div class="prod-status-badge <?= $productStatus === 'published' ? 'is-live' : '' ?>">
        <span class="prod-status-dot"></span>
        <?= $productStatus === 'published' ? 'Publicado' : 'Borrador' ?>
    </div>
    <?php endif; ?>
</div>

<form method="post" action="<?= e(url($editing ? '/products/update' : '/products/store')) ?>">
    <?= csrf_field() ?>
    <?php if ($editing): ?><input type="hidden" name="id" value="<?= e($item['id']) ?>"><?php endif; ?>

    <!-- ════ 2-COLUMN GRID ════ -->
    <div class="prod-editor-grid">

        <!-- ════ LEFT COLUMN ════ -->
        <div class="prod-editor-main">

            <!-- CARD ①: Información del producto -->
            <div class="prod-card">
                <div class="prod-card-head">
                    <span class="prod-card-icon"><?= icon('file-text') ?></span>
                    <div>
                        <h3>Información del producto</h3>
                        <span>Datos principales visibles en el catálogo</span>
                    </div>
                </div>
                <div class="prod-card-body">
                    <div class="prod-field">
                        <label class="prod-label">Nombre del producto <span class="req">*</span></label>
                        <input class="prod-input" type="text" name="name" value="<?= e(old('name', $item['name'] ?? '')) ?>" placeholder="Ej: Cacao en grano fermentado" required>
                    </div>
                    <div class="prod-row-2">
                        <div class="prod-field">
                            <label class="prod-label">SKU</label>
                            <input class="prod-input" type="text" name="sku" value="<?= e(old('sku', $item['sku'] ?? '')) ?>" placeholder="CAC-GR-001">
                        </div>
                        <?php if ($editing && !empty($item['slug'])): ?>
                        <div class="prod-field">
                            <label class="prod-label">Slug</label>
                            <div class="prod-input-readonly"><?= e($item['slug']) ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="prod-field">
                        <label class="prod-label">Descripción corta</label>
                        <input class="prod-input" type="text" name="short_description" value="<?= e(old('short_description', $item['short_description'] ?? '')) ?>" placeholder="Resumen de una línea visible en las cards del landing">
                        <span class="prod-hint">Máximo recomendado: 120 caracteres. Aparece bajo el nombre en las tarjetas.</span>
                    </div>
                    <div class="prod-field">
                        <label class="prod-label">Descripción completa</label>
                        <textarea class="prod-input prod-textarea" name="description" rows="6" placeholder="Descripción detallada del producto, características, origen, usos..."><?= e(old('description', $item['description'] ?? '')) ?></textarea>
                        <span class="prod-hint">Texto enriquecido para la página de detalle del producto.</span>
                    </div>
                </div>
            </div>

            <!-- CARD ②: Comercial -->
            <div class="prod-card">
                <div class="prod-card-head">
                    <span class="prod-card-icon"><?= icon('globe') ?></span>
                    <div>
                        <h3>Contenido en ingles</h3>
                        <span>Obligatorio cuando el producto esta publicado</span>
                    </div>
                </div>
                <div class="prod-card-body">
                    <div class="prod-field">
                        <label class="prod-label">Nombre del producto en ingles</label>
                        <input class="prod-input" type="text" name="name_en" value="<?= e(old('name_en', $item['name_en'] ?? '')) ?>" placeholder="Ex: Fermented cacao beans">
                    </div>
                    <div class="prod-field">
                        <label class="prod-label">Descripcion corta en ingles</label>
                        <input class="prod-input" type="text" name="short_description_en" value="<?= e(old('short_description_en', $item['short_description_en'] ?? '')) ?>" placeholder="Short summary shown on landing cards">
                    </div>
                    <div class="prod-field">
                        <label class="prod-label">Descripcion completa en ingles</label>
                        <textarea class="prod-input prod-textarea" name="description_en" rows="6" placeholder="Detailed product description in English"><?= e(old('description_en', $item['description_en'] ?? '')) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="prod-card">
                <div class="prod-card-head">
                    <span class="prod-card-icon"><?= icon('dollar-sign') ?></span>
                    <div>
                        <h3>Información comercial</h3>
                        <span>Precios, stock y visibilidad</span>
                    </div>
                </div>
                <div class="prod-card-body">
                    <div class="prod-row-3">
                        <div class="prod-field">
                            <label class="prod-label">Precio (S/)</label>
                            <div class="prod-input-wrap">
                                <span class="prod-input-prefix">S/</span>
                                <input class="prod-input has-prefix" type="number" step="0.01" min="0" name="price" value="<?= e(old('price', $item['price'] ?? '0.00')) ?>">
                            </div>
                        </div>
                        <div class="prod-field">
                            <label class="prod-label">Precio oferta (S/)</label>
                            <div class="prod-input-wrap">
                                <span class="prod-input-prefix">S/</span>
                                <input class="prod-input has-prefix" type="number" step="0.01" min="0" name="sale_price" value="<?= e(old('sale_price', $item['sale_price'] ?? '')) ?>" placeholder="Opcional">
                            </div>
                            <span class="prod-hint">Si se deja vacío, solo se muestra el precio normal.</span>
                        </div>
                        <div class="prod-field">
                            <label class="prod-label">Stock</label>
                            <input class="prod-input" type="number" min="0" name="stock" value="<?= e(old('stock', $item['stock'] ?? '')) ?>" placeholder="Sin límite">
                        </div>
                    </div>
                    <div class="prod-row-2">
                        <div class="prod-field">
                            <label class="prod-label">Estado</label>
                            <div class="prod-toggle-group">
                                <label class="prod-toggle <?= $productStatus === 'draft' ? 'active' : '' ?>">
                                    <input type="radio" name="status" value="draft" <?= $productStatus === 'draft' ? 'checked' : '' ?>>
                                    <span class="prod-toggle-btn"><?= icon('edit-3') ?> Borrador</span>
                                </label>
                                <label class="prod-toggle <?= $productStatus === 'published' ? 'active' : '' ?>">
                                    <input type="radio" name="status" value="published" <?= $productStatus === 'published' ? 'checked' : '' ?>>
                                    <span class="prod-toggle-btn"><?= icon('globe') ?> Publicado</span>
                                </label>
                            </div>
                        </div>
                        <div class="prod-field">
                            <label class="prod-label">Destacado</label>
                            <label class="prod-switch">
                                <input type="checkbox" name="is_featured" value="1" <?= $productFeatured === 1 ? 'checked' : '' ?>>
                                <span class="prod-switch-track">
                                    <span class="prod-switch-thumb"></span>
                                </span>
                                <span class="prod-switch-label">Mostrar primero en la landing</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CARD ⑥: Ficha Técnica — COOPAECA -->
            <div class="prod-card">
                <div class="prod-card-head">
                    <span class="prod-card-icon"><?= icon('clipboard') ?></span>
                    <div>
                        <h3>Ficha técnica</h3>
                        <span>Especificaciones del producto para la página de detalle</span>
                    </div>
                </div>
                <div class="prod-card-body">
                    <div class="prod-row-2">
                        <div class="prod-field">
                            <label class="prod-label">Origen</label>
                            <input class="prod-input" type="text" name="origin" value="<?= e(old('origin', $item['origin'] ?? 'Pangoa – Mazamari, Satipo, Junín')) ?>" placeholder="Pangoa – Mazamari, Satipo, Junín">
                        </div>
                        <div class="prod-field">
                            <label class="prod-label">Variedad</label>
                            <input class="prod-input" type="text" name="variety" value="<?= e(old('variety', $item['variety'] ?? 'VRAE 99, VRAE 15, Bellavista y Nativo')) ?>" placeholder="VRAE 99, VRAE 15, Bellavista y Nativo">
                        </div>
                    </div>
                    <div class="prod-row-2">
                        <div class="prod-field">
                            <label class="prod-label">Fermentación</label>
                            <input class="prod-input" type="text" name="fermentation" value="<?= e(old('fermentation', $item['fermentation'] ?? '80% mínimo (grano marrón)')) ?>" placeholder="80% mínimo (grano marrón)">
                        </div>
                        <div class="prod-field">
                            <label class="prod-label">Humedad</label>
                            <input class="prod-input" type="text" name="humidity" value="<?= e(old('humidity', $item['humidity'] ?? '7.5% – 7.0%')) ?>" placeholder="7.5% – 7.0%">
                        </div>
                    </div>
                    <div class="prod-row-2">
                        <div class="prod-field">
                            <label class="prod-label">Altitud</label>
                            <input class="prod-input" type="text" name="altitude" value="<?= e(old('altitude', $item['altitude'] ?? '550 a 1,200 m.s.n.m.')) ?>" placeholder="550 a 1,200 m.s.n.m.">
                        </div>
                        <div class="prod-field">
                            <label class="prod-label">Granos por 100g</label>
                            <input class="prod-input" type="text" name="grain_count" value="<?= e(old('grain_count', $item['grain_count'] ?? '85 – 95 granos')) ?>" placeholder="85 – 95 granos">
                        </div>
                    </div>
                    <div class="prod-row-2">
                        <div class="prod-field">
                            <label class="prod-label">Índice de grano</label>
                            <input class="prod-input" type="text" name="grain_index" value="<?= e(old('grain_index', $item['grain_index'] ?? '1.05 – 1.18 g/grano')) ?>" placeholder="1.05 – 1.18 g/grano">
                        </div>
                        <div class="prod-field">
                            <label class="prod-label">Certificación</label>
                            <input class="prod-input" type="text" name="certification" value="<?= e(old('certification', $item['certification'] ?? 'Comercio justo y trazabilidad de origen')) ?>" placeholder="Comercio justo y trazabilidad de origen">
                        </div>
                    </div>
                    <div class="prod-field">
                        <label class="prod-label">Presentación</label>
                        <input class="prod-input" type="text" name="presentation" value="<?= e(old('presentation', $item['presentation'] ?? 'Sacos de 46 kg (yute) y empaques de 85g')) ?>" placeholder="Sacos de 46 kg (yute) y empaques de 85g">
                    </div>
                </div>
            </div>

            <!-- CARD ③: Multimedia -->
            <div class="prod-card">
                <div class="prod-card-head">
                    <span class="prod-card-icon"><?= icon('clipboard') ?></span>
                    <div>
                        <h3>Ficha tecnica en ingles</h3>
                        <span>Traducciones de las especificaciones visibles en la pagina de detalle</span>
                    </div>
                </div>
                <div class="prod-card-body">
                    <div class="prod-row-2">
                        <div class="prod-field">
                            <label class="prod-label">Origin</label>
                            <input class="prod-input" type="text" name="origin_en" value="<?= e(old('origin_en', $item['origin_en'] ?? '')) ?>" placeholder="Pangoa - Mazamari, Satipo, Junin">
                        </div>
                        <div class="prod-field">
                            <label class="prod-label">Variety</label>
                            <input class="prod-input" type="text" name="variety_en" value="<?= e(old('variety_en', $item['variety_en'] ?? '')) ?>" placeholder="VRAE 99, VRAE 15, Bellavista and native cacao">
                        </div>
                    </div>
                    <div class="prod-row-2">
                        <div class="prod-field">
                            <label class="prod-label">Fermentation</label>
                            <input class="prod-input" type="text" name="fermentation_en" value="<?= e(old('fermentation_en', $item['fermentation_en'] ?? '')) ?>" placeholder="80% minimum">
                        </div>
                        <div class="prod-field">
                            <label class="prod-label">Altitude</label>
                            <input class="prod-input" type="text" name="altitude_en" value="<?= e(old('altitude_en', $item['altitude_en'] ?? '')) ?>" placeholder="550 to 1,200 m.a.s.l.">
                        </div>
                    </div>
                    <div class="prod-row-2">
                        <div class="prod-field">
                            <label class="prod-label">Certification</label>
                            <input class="prod-input" type="text" name="certification_en" value="<?= e(old('certification_en', $item['certification_en'] ?? '')) ?>" placeholder="Fair trade and origin traceability">
                        </div>
                        <div class="prod-field">
                            <label class="prod-label">Presentation</label>
                            <input class="prod-input" type="text" name="presentation_en" value="<?= e(old('presentation_en', $item['presentation_en'] ?? '')) ?>" placeholder="46 kg jute bags and 85 g packages">
                        </div>
                    </div>
                </div>
            </div>

            <div class="prod-card">
                <div class="prod-card-head">
                    <span class="prod-card-icon"><?= icon('image') ?></span>
                    <div>
                        <h3>Multimedia</h3>
                        <span>Imagen de portada del producto</span>
                    </div>
                </div>
                <div class="prod-card-body">
                    <input type="hidden" name="cover_image_id" id="coverImageId" value="<?= $coverId ?>">
                    <div class="prod-media-area" id="coverPicker">
                        <div class="prod-media-preview <?= $coverPreview ? 'has-image' : '' ?>" id="coverPreview">
                            <?php if ($coverPreview): ?>
                                <img src="<?= e($coverPreview) ?>" alt="Portada del producto">
                                <div class="prod-media-actions">
                                    <button type="button" class="prod-media-change" id="coverBrowse"><?= icon('refresh-cw') ?> Cambiar</button>
                                    <button type="button" class="prod-media-remove" id="coverRemove" title="Quitar"><?= icon('trash-2') ?></button>
                                </div>
                            <?php else: ?>
                                <div class="prod-media-empty">
                                    <div class="prod-media-empty-icon"><?= icon('image') ?></div>
                                    <strong>Sin imagen de portada</strong>
                                    <p>Arrastra una imagen o haz clic para seleccionar</p>
                                    <button type="button" class="prod-media-upload-btn" id="coverBrowse"><?= icon('upload') ?> Seleccionar imagen</button>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- ════ RIGHT COLUMN ════ -->
        <div class="prod-editor-side">

            <!-- CARD ④: Categorías -->
            <?php if (!empty($productCats)): ?>
            <div class="prod-card">
                <div class="prod-card-head">
                    <span class="prod-card-icon"><?= icon('tag') ?></span>
                    <div>
                        <h3>Categorías</h3>
                        <span>Agrupa el producto</span>
                    </div>
                </div>
                <div class="prod-card-body">
                    <div class="prod-pills">
                        <?php foreach ($productCats as $cat):
                            $checked = in_array((int)$cat['id'], $selectedCatIds ?? []) || in_array((int)$cat['id'], old('category_ids', []));
                        ?>
                        <label class="prod-pill <?= $checked ? 'selected' : '' ?>">
                            <input type="checkbox" name="category_ids[]" value="<?= e($cat['id']) ?>" <?= $checked ? 'checked' : '' ?>>
                            <span class="prod-pill-check"><?= icon('check') ?></span>
                            <?= e($cat['name']) ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- CARD ⑤: SEO -->
            <div class="prod-card">
                <div class="prod-card-head">
                    <span class="prod-card-icon"><?= icon('search') ?></span>
                    <div>
                        <h3>SEO &amp; Metadatos</h3>
                        <span>Motores de búsqueda</span>
                    </div>
                </div>
                <div class="prod-card-body">
                    <div class="prod-field">
                        <label class="prod-label">Meta título</label>
                        <input class="prod-input" type="text" name="meta_title" value="<?= e(old('meta_title', $item['meta_title'] ?? '')) ?>" placeholder="Título para Google y redes sociales">
                        <span class="prod-hint">Ideal: 50-60 caracteres. Si se deja vacío se usa el nombre.</span>
                    </div>
                    <div class="prod-field">
                        <label class="prod-label">Meta descripción</label>
                        <textarea class="prod-input prod-textarea-sm" name="meta_description" rows="3" placeholder="Descripción breve para resultados de búsqueda"><?= e(old('meta_description', $item['meta_description'] ?? '')) ?></textarea>
                        <span class="prod-hint">Ideal: 140-160 caracteres.</span>
                    </div>
                    <div class="prod-field">
                        <label class="prod-label">Meta titulo en ingles</label>
                        <input class="prod-input" type="text" name="meta_title_en" value="<?= e(old('meta_title_en', $item['meta_title_en'] ?? '')) ?>" placeholder="English SEO title">
                    </div>
                    <div class="prod-field">
                        <label class="prod-label">Meta descripcion en ingles</label>
                        <textarea class="prod-input prod-textarea-sm" name="meta_description_en" rows="3" placeholder="English SEO description"><?= e(old('meta_description_en', $item['meta_description_en'] ?? '')) ?></textarea>
                    </div>
                    <?php if ($editing): ?>
                    <div class="prod-seo-preview">
                        <span class="prod-seo-label">Vista previa en Google</span>
                        <div class="prod-seo-card">
                            <span class="prod-seo-url"><?= e(absolute_url('/producto/' . ($item['slug'] ?? '...'))) ?></span>
                            <strong class="prod-seo-title"><?= e(old('meta_title', $item['meta_title'] ?? '') ?: ($item['name'] ?? 'Nombre del producto')) ?></strong>
                            <span class="prod-seo-desc"><?= e(old('meta_description', $item['meta_description'] ?? '') ?: 'Descripción del producto...') ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <!-- ════ STICKY BOTTOM BAR ════ -->
    <div class="prod-sticky-bar">
        <div class="prod-sticky-inner">
            <div class="prod-sticky-info">
                <?php if ($editing): ?>
                    <span>Editando: <strong><?= e($item['name']) ?></strong></span>
                <?php else: ?>
                    <span>Los productos en borrador no serán visibles en el landing.</span>
                <?php endif; ?>
            </div>
            <div class="prod-sticky-actions">
                <a class="prod-btn-cancel" href="<?= e(url('/products')) ?>">Cancelar</a>
                <button class="prod-btn-save" type="submit">
                    <?= icon('save') ?> <?= $editing ? 'Guardar cambios' : 'Crear producto' ?>
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Media picker modal -->
<div class="modal-overlay" id="mediaModal" style="display:none">
    <div class="modal-box modal-lg">
        <div class="modal-header">
            <h3>Seleccionar imagen de portada</h3>
            <button type="button" class="modal-close" id="mediaModalClose">&times;</button>
        </div>
        <div class="modal-body">
            <div class="media-grid" id="mediaGrid">
                <?php foreach ($allImages as $img): ?>
                <div class="media-grid-item <?= (int)$img['id'] === $coverId ? 'selected' : '' ?>"
                     data-id="<?= e($img['id']) ?>"
                     data-path="<?= e(url('/' . $img['disk_path'])) ?>">
                    <img src="<?= e(url('/' . $img['disk_path'])) ?>" alt="<?= e($img['original_name']) ?>" loading="lazy">
                    <span class="media-grid-name"><?= e($img['original_name']) ?></span>
                </div>
                <?php endforeach; ?>
                <?php if (empty($allImages)): ?>
                    <div class="empty-state">
                        <p>No hay imágenes disponibles. <a href="<?= e(url('/media')) ?>">Sube imágenes aquí</a>.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="button ghost" id="mediaClear">Quitar imagen</button>
            <button type="button" class="button primary" id="mediaSelect">Seleccionar</button>
        </div>
    </div>
</div>

<script>
(function() {
    const modal = document.getElementById('mediaModal');
    const grid = document.getElementById('mediaGrid');
    const inputId = document.getElementById('coverImageId');
    const preview = document.getElementById('coverPreview');
    let selectedId = <?= $coverId ?: 'null' ?>;
    let selectedPath = <?= json_encode($coverPreview ?: null) ?>;

    function updatePreview() {
        if (selectedId && selectedPath) {
            preview.className = 'prod-media-preview has-image';
            preview.innerHTML = '<img src="' + selectedPath + '" alt="Portada del producto">' +
                '<div class="prod-media-actions">' +
                '<button type="button" class="prod-media-change" id="coverBrowse">' +
                '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 4v6h6"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg> Cambiar</button>' +
                '<button type="button" class="prod-media-remove" id="coverRemove" title="Quitar">' +
                '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg></button></div>';
            document.getElementById('coverBrowse').onclick = openModal;
            document.getElementById('coverRemove').onclick = clearCover;
        } else {
            preview.className = 'prod-media-preview';
            preview.innerHTML = '<div class="prod-media-empty">' +
                '<div class="prod-media-empty-icon"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg></div>' +
                '<strong>Sin imagen de portada</strong>' +
                '<p>Arrastra una imagen o haz clic para seleccionar</p>' +
                '<button type="button" class="prod-media-upload-btn" id="coverBrowse"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg> Seleccionar imagen</button></div>';
            document.getElementById('coverBrowse').onclick = openModal;
        }
        inputId.value = selectedId || '';
    }

    function clearCover() { selectedId = null; selectedPath = null; updatePreview(); }

    function openModal() {
        document.querySelectorAll('.media-grid-item').forEach(el => el.classList.remove('selected'));
        if (selectedId) {
            const el = document.querySelector('.media-grid-item[data-id="' + selectedId + '"]');
            if (el) el.classList.add('selected');
        }
        modal.style.display = 'flex';
    }

    document.getElementById('mediaModalClose').onclick = function() { modal.style.display = 'none'; };
    document.getElementById('mediaClear').onclick = function() { clearCover(); modal.style.display = 'none'; };
    document.getElementById('mediaSelect').onclick = function() {
        const sel = document.querySelector('.media-grid-item.selected');
        if (sel) {
            selectedId = parseInt(sel.dataset.id);
            selectedPath = sel.dataset.path;
        }
        updatePreview();
        modal.style.display = 'none';
    };
    grid.addEventListener('click', function(e) {
        const item = e.target.closest('.media-grid-item');
        if (!item) return;
        document.querySelectorAll('.media-grid-item').forEach(el => el.classList.remove('selected'));
        item.classList.add('selected');
    });
    modal.addEventListener('click', function(e) { if (e.target === modal) modal.style.display = 'none'; });

    const existingRemove = document.getElementById('coverRemove');
    if (existingRemove) existingRemove.onclick = clearCover;

    // Bind initial browse button (exists on page load when no image selected)
    const initialBrowse = document.getElementById('coverBrowse');
    if (initialBrowse) initialBrowse.onclick = openModal;

    // Drag & drop support
    preview.addEventListener('dragover', function(e) { e.preventDefault(); preview.classList.add('drag-over'); });
    preview.addEventListener('dragleave', function() { preview.classList.remove('drag-over'); });
    preview.addEventListener('drop', function(e) {
        e.preventDefault();
        preview.classList.remove('drag-over');
        const file = e.dataTransfer.files[0];
        if (!file || !file.type.startsWith('image/')) return;
        const formData = new FormData();
        formData.append('file', file);
        fetch('<?= e(url('/media/upload-json')) ?>', { method: 'POST', body: formData, headers: { 'X-CSRF-Token': '<?= e(csrf_token()) ?>' } })
            .then(r => r.json())
            .then(data => {
                if (data.error) { alert('Error: ' + data.error); return; }
                selectedId = data.item.id;
                selectedPath = data.item.url;
                updatePreview();
                // Add to grid for future re-selection
                const gridItem = document.createElement('div');
                gridItem.className = 'media-grid-item selected';
                gridItem.dataset.id = data.item.id;
                gridItem.dataset.path = data.item.url;
                gridItem.innerHTML = '<img src="' + data.item.path + '" alt="' + data.item.name + '" loading="lazy"><span class="media-grid-name">' + data.item.name + '</span>';
                grid.insertBefore(gridItem, grid.firstChild);
            })
            .catch(() => alert('Error al subir la imagen. Verifica el tamaño (max 5 MB) y formato (JPG, PNG, WebP).'));
    });
})();
</script>
