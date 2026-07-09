<?php
$editing = is_array($item);
$coverId = (int)old('cover_image_id', $item['cover_image_id'] ?? 0);
$coverPreview = '';
foreach ($allImages as $img) {
    if ((int)$img['id'] === $coverId) { $coverPreview = url('/' . $img['disk_path']); break; }
}
// Imagenes ya asignadas al album (para precargar el selector multiple)
$seleccionadas = [];
foreach ($images as $gi) {
    $seleccionadas[(int)$gi['file_id']] = [
        'id' => (int)$gi['file_id'],
        'name' => $gi['original_name'],
        'path' => url('/' . $gi['disk_path']),
        'caption' => $gi['caption'] ?? '',
    ];
}
?>
<style>
    .gal-chips { display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); gap: 12px; margin-top: 10px; }
    .gal-chip { border: 1px solid var(--line); border-radius: 10px; overflow: hidden; background: var(--panel); }
    .gal-chip img { width: 100%; height: 100px; object-fit: cover; display: block; }
    .gal-chip-body { padding: 8px; display: flex; flex-direction: column; gap: 6px; }
    .gal-chip-body input { font-size: 12px; padding: 6px 8px; }
    .gal-chip-remove { border: none; background: #fef2f2; color: #dc2626; border-radius: 6px; padding: 4px 6px; cursor: pointer; font-size: 12px; }
    .gal-chip-remove:hover { background: #fee2e2; }
    .gal-empty-hint { color: var(--muted); font-size: 13px; padding: 12px 0; }
</style>

<section class="card narrow">
    <div class="section-title">
        <h2><?= $editing ? 'Editar álbum' : 'Nuevo álbum' ?></h2>
    </div>
    <form method="post" action="<?= e(url($editing ? '/galleries/update' : '/galleries/store')) ?>" class="form" id="galleryForm">
        <?= csrf_field() ?>
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= e($item['id']) ?>"><?php endif; ?>

        <label>Título
            <input name="title" value="<?= e(old('title', $item['title'] ?? '')) ?>" required>
        </label>
        <label>Descripción
            <textarea name="description" rows="3"><?= e(old('description', $item['description'] ?? '')) ?></textarea>
        </label>
        <div class="form-grid-2">
            <label>Orden
                <input type="number" name="position" value="<?= e(old('position', $item['position'] ?? 0)) ?>">
            </label>
            <label class="check" style="align-self:end">
                <input type="checkbox" name="is_active" value="1" <?= (int)old('is_active', $item['is_active'] ?? 1) === 1 ? 'checked' : '' ?>> Álbum activo
            </label>
        </div>

        <div>
            <label>Imagen de portada</label>
            <div id="coverPreviewBox">
                <?php if ($coverPreview): ?>
                    <img src="<?= e($coverPreview) ?>" alt="Portada" style="max-width:220px;border-radius:8px;display:block;margin-bottom:8px">
                <?php endif; ?>
            </div>
            <input type="hidden" name="cover_image_id" id="coverImageId" value="<?= $coverId ?: '' ?>">
            <button type="button" class="button ghost small" id="btnPickCover"><?= icon('image') ?> Elegir portada</button>
        </div>

        <div>
            <label>Fotos del álbum</label>
            <button type="button" class="button ghost small" id="btnPickImages"><?= icon('image') ?> Agregar fotos</button>
            <div class="gal-chips" id="galChips">
                <?php if (!$seleccionadas): ?><p class="gal-empty-hint" id="galEmptyHint">Aún no agregaste fotos a este álbum.</p><?php endif; ?>
            </div>
        </div>

        <div class="form-actions">
            <button class="button primary" type="submit">Guardar</button>
            <a class="button ghost" href="<?= e(url('/galleries')) ?>">Cancelar</a>
        </div>
    </form>
</section>

<!-- Selector de imagenes (single: portada / multiple: fotos del album) -->
<div class="media-picker-modal" id="mediaPickerModal" hidden>
    <div class="media-picker-dialog">
        <div class="media-picker-head">
            <div>
                <strong id="mediaPickerTitle">Biblioteca Media</strong>
                <span id="mediaPickerHint">Selecciona una imagen.</span>
            </div>
            <button type="button" class="media-picker-close" id="mediaPickerClose"><?= icon('x') ?></button>
        </div>
        <div class="media-picker-toolbar">
            <input type="search" id="mediaPickerSearch" placeholder="Buscar imagen...">
            <a class="button ghost small" href="<?= e(url('/media')) ?>" target="_blank" rel="noopener"><?= icon('share') ?> Abrir Media</a>
        </div>
        <div class="media-picker-grid" id="mediaPickerGrid"></div>
        <div class="media-picker-empty" id="mediaPickerEmpty" hidden>No hay imagenes disponibles.</div>
        <div class="modal-footer" id="mediaPickerFooter" style="display:none">
            <button type="button" class="button primary" id="mediaPickerConfirm">Agregar seleccionadas</button>
        </div>
    </div>
</div>

<script>
(function () {
    const chipsBox = document.getElementById('galChips');
    const emptyHint = document.getElementById('galEmptyHint');
    let seleccionadas = <?= json_encode(array_values($seleccionadas)) ?>;

    function renderChips() {
        chipsBox.innerHTML = '';
        if (!seleccionadas.length) {
            chipsBox.innerHTML = '<p class="gal-empty-hint">Aún no agregaste fotos a este álbum.</p>';
            return;
        }
        seleccionadas.forEach(function (img) {
            const div = document.createElement('div');
            div.className = 'gal-chip';
            div.innerHTML = `
                <img src="${img.path}" alt="${img.name}">
                <div class="gal-chip-body">
                    <input type="hidden" name="image_ids[]" value="${img.id}">
                    <input type="text" name="captions[${img.id}]" placeholder="Descripción (opcional)" value="${(img.caption || '').replace(/"/g, '&quot;')}">
                    <button type="button" class="gal-chip-remove" data-id="${img.id}">Quitar</button>
                </div>`;
            chipsBox.appendChild(div);
        });
        chipsBox.querySelectorAll('.gal-chip-remove').forEach(function (btn) {
            btn.addEventListener('click', function () {
                seleccionadas = seleccionadas.filter(i => String(i.id) !== btn.dataset.id);
                renderChips();
            });
        });
    }
    renderChips();

    // ---- Selector de imagenes ----
    const modal = document.getElementById('mediaPickerModal');
    const grid = document.getElementById('mediaPickerGrid');
    const empty = document.getElementById('mediaPickerEmpty');
    const search = document.getElementById('mediaPickerSearch');
    const footer = document.getElementById('mediaPickerFooter');
    const title = document.getElementById('mediaPickerTitle');
    const hint = document.getElementById('mediaPickerHint');
    let mediaItems = [];
    let modo = 'multiple'; // 'cover' | 'multiple'
    let pickedIds = new Set();

    document.getElementById('btnPickCover').addEventListener('click', function () { openPicker('cover'); });
    document.getElementById('btnPickImages').addEventListener('click', function () { openPicker('multiple'); });
    document.getElementById('mediaPickerClose').addEventListener('click', closePicker);
    modal.addEventListener('click', function (e) { if (e.target === modal) closePicker(); });
    search.addEventListener('input', renderItems);

    function openPicker(m) {
        modo = m;
        pickedIds = new Set();
        title.textContent = modo === 'cover' ? 'Elegir portada' : 'Agregar fotos al álbum';
        hint.textContent = modo === 'cover' ? 'Selecciona una imagen para la portada.' : 'Puedes seleccionar varias imagenes.';
        footer.style.display = modo === 'multiple' ? 'flex' : 'none';
        modal.hidden = false;
        search.value = '';
        loadItems();
    }
    function closePicker() { modal.hidden = true; }

    function loadItems() {
        grid.innerHTML = '<div class="media-picker-loading">Cargando imagenes...</div>';
        fetch(<?= json_encode(url('/media/picker?type=image')) ?>)
            .then(r => r.json())
            .then(json => { mediaItems = json.items || []; renderItems(); })
            .catch(() => { mediaItems = []; renderItems(); });
    }

    function renderItems() {
        const term = search.value.trim().toLowerCase();
        const filtered = mediaItems.filter(i => !term || i.name.toLowerCase().includes(term));
        empty.hidden = filtered.length > 0;
        grid.innerHTML = filtered.map(item => `
            <button type="button" class="media-picker-item ${pickedIds.has(String(item.id)) ? 'selected' : ''}" data-id="${item.id}" data-path="${item.path}" data-name="${item.name}">
                <img src="${item.path}" alt="${item.name}" loading="lazy">
                <span>${item.name}</span>
            </button>`).join('');
        grid.querySelectorAll('.media-picker-item').forEach(btn => btn.addEventListener('click', () => onPick(btn)));
    }

    function onPick(btn) {
        if (modo === 'cover') {
            document.getElementById('coverImageId').value = btn.dataset.id;
            document.getElementById('coverPreviewBox').innerHTML =
                '<img src="' + btn.dataset.path + '" alt="Portada" style="max-width:220px;border-radius:8px;display:block;margin-bottom:8px">';
            closePicker();
            return;
        }
        const id = btn.dataset.id;
        if (pickedIds.has(id)) { pickedIds.delete(id); btn.classList.remove('selected'); }
        else { pickedIds.add(id); btn.classList.add('selected'); }
    }

    document.getElementById('mediaPickerConfirm').addEventListener('click', function () {
        pickedIds.forEach(function (id) {
            if (seleccionadas.some(i => String(i.id) === id)) return;
            const btn = grid.querySelector('.media-picker-item[data-id="' + id + '"]');
            if (btn) seleccionadas.push({ id: id, name: btn.dataset.name, path: btn.dataset.path, caption: '' });
        });
        renderChips();
        closePicker();
    });
})();
</script>
