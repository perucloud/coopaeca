<?php
$editing = is_array($item);
$status = old('status', $item['status'] ?? 'draft');
$category = old('category', $item['category'] ?? 'General');
$categoryEn = old('category_en', $item['category_en'] ?? '');
$contentValue = old('content', $item['content'] ?? '');
$contentEnValue = old('content_en', $item['content_en'] ?? '');
$authorName = $item['author_name'] ?? user()['name'] ?? 'Admin';
$featuredId = (int)old('featured_image_id', $item['featured_image_id'] ?? 0);
?>

<div class="post-editor-back">
    <a href="<?= e(url('/posts')) ?>"><?= icon('chevron-right') ?><span>Volver a Noticias</span></a>
    <?php if ($editing && ($item['status'] ?? '') === 'published'): ?>
        <a href="<?= e(url('/#galeria')) ?>" target="_blank" rel="noopener"><?= icon('share') ?><span>Ver en sitio</span></a>
    <?php endif; ?>
</div>

<form method="post" action="<?= e(url($editing ? '/posts/update' : '/posts/store')) ?>" enctype="multipart/form-data" class="post-editor" id="postEditorForm">
    <?= csrf_field() ?>
    <?php if ($editing): ?><input type="hidden" name="id" value="<?= e($item['id']) ?>"><?php endif; ?>
    <input type="hidden" name="featured_image_id" id="featuredImageId" value="<?= e($featuredId ?: '') ?>">

    <div class="post-editor-main">
        <section class="post-panel">
            <label for="title">Titulo de la noticia *</label>
            <input id="title" name="title" value="<?= e(old('title', $item['title'] ?? '')) ?>" placeholder="Escribe un titulo claro y descriptivo..." required>
        </section>

        <section class="post-panel">
            <label for="titleEn">Titulo en ingles</label>
            <input id="titleEn" name="title_en" value="<?= e(old('title_en', $item['title_en'] ?? '')) ?>" placeholder="English title for the public landing">
        </section>

        <section class="post-panel">
            <div class="post-panel-head">
                <label for="contentEditor">Contenido</label>
                <div class="post-editor-tools">
                    <button type="button" class="button ghost small" data-open-media="content"><?= icon('image') ?> Biblioteca Media</button>
                    <span id="postCharCount">0 caracteres</span>
                </div>
            </div>
            <textarea name="content" id="contentEditor"><?= e($contentValue) ?></textarea>
        </section>

        <section class="post-panel">
            <div class="post-panel-head">
                <label for="contentEditorEn">Contenido en ingles</label>
                <div class="post-editor-tools">
                    <button type="button" class="button ghost small" data-open-media="content_en"><?= icon('image') ?> Biblioteca Media</button>
                </div>
            </div>
            <textarea name="content_en" id="contentEditorEn"><?= e($contentEnValue) ?></textarea>
        </section>

        <section class="post-panel">
            <div class="post-panel-head">
                <label>Imagen destacada</label>
                <button type="button" class="button ghost small" data-open-media="featured"><?= icon('image') ?> Elegir desde Media</button>
            </div>
            <div class="featured-upload">
                <?php if (!empty($item['image_path'])): ?>
                    <img src="<?= e(url('/' . $item['image_path'])) ?>" alt="<?= e($item['title'] ?? '') ?>" id="featuredPreview">
                <?php else: ?>
                    <div class="featured-preview-empty" id="featuredPreview"><?= icon('image') ?></div>
                <?php endif; ?>
                <div>
                    <input type="file" name="featured_image" id="featuredImage" accept="image/jpeg,image/png,image/webp" hidden>
                    <label for="featuredImage" class="button ghost"><?= icon('upload') ?> <?= $editing && !empty($item['image_path']) ? 'Cambiar imagen' : 'Subir imagen' ?></label>
                    <p>Tambien puedes elegir una imagen ya cargada en Media. JPG, PNG o WebP, max. 5 MB.</p>
                    <strong id="featuredFileName"><?= e($item['image_name'] ?? '') ?></strong>
                </div>
            </div>
        </section>
    </div>

    <aside class="post-editor-side">
        <section class="post-panel">
            <h3>Publicar</h3>
            <label for="status">Estado</label>
            <select name="status" id="status">
                <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>Borrador</option>
                <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>Publicado</option>
                <option value="scheduled" <?= $status === 'scheduled' ? 'selected' : '' ?>>Programado</option>
            </select>

            <label for="categoryEn">Categoría en inglés</label>
            <input name="category_en" id="categoryEn" value="<?= e($categoryEn) ?>" maxlength="100" placeholder="Ex: Sourcing, Quality, Community">

            <label for="category">Categoria</label>
            <select name="category" id="category">
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                <?php endforeach; ?>
            </select>

            <div class="post-author">
                <div><?= strtoupper(substr($authorName, 0, 1)) ?></div>
                <span><strong><?= e($authorName) ?></strong><small>Autor de esta nota</small></span>
            </div>

            <button class="button primary full post-submit" type="submit"><?= icon('edit') ?> <?= $editing ? 'Actualizar noticia' : 'Publicar noticia' ?></button>
        </section>

        <section class="post-panel">
            <label for="slug">URL (slug)</label>
            <small class="post-help">/noticias/mi-publicacion</small>
            <input name="slug" id="slug" value="<?= e(old('slug', $item['slug'] ?? '')) ?>" placeholder="se-genera-automaticamente">
            <small class="post-help">Se genera del titulo si se deja vacio.</small>
        </section>

        <section class="post-panel post-seo">
            <button type="button" class="post-seo-toggle" id="seoToggle">
                <span><?= icon('search') ?> SEO</span><?= icon('chevron-down') ?>
            </button>
            <div class="post-seo-body" id="seoBody">
                <label for="excerpt">Resumen</label>
                <textarea name="excerpt" id="excerpt" rows="4" maxlength="300" placeholder="Resumen breve para tarjetas y buscadores"><?= e(old('excerpt', $item['excerpt'] ?? '')) ?></textarea>

                <label for="excerptEn">Resumen en ingles</label>
                <textarea name="excerpt_en" id="excerptEn" rows="4" maxlength="300" placeholder="English summary for cards and search"><?= e(old('excerpt_en', $item['excerpt_en'] ?? '')) ?></textarea>

                <label for="metaTitle">Meta titulo <small id="metaTitleCount"></small></label>
                <input name="meta_title" id="metaTitle" maxlength="100" value="<?= e(old('meta_title', $item['meta_title'] ?? '')) ?>">

                <label for="metaTitleEn">Meta titulo en ingles</label>
                <input name="meta_title_en" id="metaTitleEn" maxlength="100" value="<?= e(old('meta_title_en', $item['meta_title_en'] ?? '')) ?>">

                <label for="metaDescription">Meta descripcion <small id="metaDescCount"></small></label>
                <textarea name="meta_description" id="metaDescription" rows="3" maxlength="200"><?= e(old('meta_description', $item['meta_description'] ?? '')) ?></textarea>

                <label for="metaDescriptionEn">Meta descripcion en ingles</label>
                <textarea name="meta_description_en" id="metaDescriptionEn" rows="3" maxlength="200"><?= e(old('meta_description_en', $item['meta_description_en'] ?? '')) ?></textarea>

                <label for="metaKeywords">Palabras clave</label>
                <input name="meta_keywords" id="metaKeywords" value="<?= e(old('meta_keywords', $item['meta_keywords'] ?? '')) ?>" placeholder="cacao, acopio, cooperativa">

                <label for="metaKeywordsEn">Palabras clave en ingles</label>
                <input name="meta_keywords_en" id="metaKeywordsEn" value="<?= e(old('meta_keywords_en', $item['meta_keywords_en'] ?? '')) ?>" placeholder="cacao, sourcing, cooperative">
            </div>
        </section>
    </aside>
</form>

<div class="media-picker-modal" id="mediaPickerModal" hidden>
    <div class="media-picker-dialog">
        <div class="media-picker-head">
            <div>
                <strong>Biblioteca Media</strong>
                <span>Selecciona una imagen cargada en el modulo Media.</span>
            </div>
            <button type="button" class="media-picker-close" id="mediaPickerClose"><?= icon('x') ?></button>
        </div>
        <div class="media-picker-toolbar">
            <input type="search" id="mediaPickerSearch" placeholder="Buscar imagen...">
            <a class="button ghost small" href="<?= e(url('/media')) ?>" target="_blank" rel="noopener"><?= icon('share') ?> Abrir Media</a>
        </div>
        <div class="media-picker-grid" id="mediaPickerGrid"></div>
        <div class="media-picker-empty" id="mediaPickerEmpty" hidden>No hay imagenes disponibles.</div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>
<script>
(function () {
    const csrf = <?= json_encode(csrf_token()) ?>;
    const form = document.getElementById('postEditorForm');
    const counter = document.getElementById('postCharCount');
    const title = document.getElementById('title');
    const slug = document.getElementById('slug');
    const featuredId = document.getElementById('featuredImageId');
    const imageInput = document.getElementById('featuredImage');
    let slugTouched = slug.value.trim() !== '';
    let mediaTarget = 'content';
    let mediaItems = [];

    function makeSlug(value) {
        return value.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9\s-]/g, '').replace(/[\s-]+/g, '-').replace(/^-+|-+$/g, '').substring(0, 120);
    }

    function updateCounter() {
        const editor = tinymce.get('contentEditor');
        const text = editor ? editor.getContent({ format: 'text' }).trim() : '';
        counter.textContent = text.length + ' caracteres';
    }

    tinymce.init({
        selector: '#contentEditor,#contentEditorEn',
        license_key: 'gpl',
        height: 430,
        menubar: false,
        branding: false,
        promotion: false,
        convert_urls: false,
        plugins: 'advlist autolink lists link image media table code preview fullscreen wordcount autoresize',
        toolbar: 'undo redo | blocks | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist blockquote | link image media table | mediaLibrary | removeformat code fullscreen',
        content_style: 'body{font-family:Inter,Arial,sans-serif;font-size:15px;line-height:1.7;color:#172033} img{max-width:100%;height:auto;border-radius:8px} iframe{max-width:100%;border-radius:8px}',
        images_upload_handler: (blobInfo, progress) => new Promise((resolve, reject) => {
            const data = new FormData();
            data.append('_csrf', csrf);
            data.append('file', blobInfo.blob(), blobInfo.filename());
            fetch('<?= e(url('/media/upload-json')) ?>', { method: 'POST', body: data })
                .then((response) => response.ok ? response.json() : response.json().then((json) => Promise.reject(json.error || 'No se pudo subir la imagen.')))
                .then((json) => resolve(json.location))
                .catch((error) => reject(String(error)));
        }),
        setup: (editor) => {
            editor.ui.registry.addButton('mediaLibrary', {
                text: 'Media',
                icon: 'image',
                tooltip: 'Insertar desde biblioteca Media',
                onAction: () => openMediaPicker(editor.id === 'contentEditorEn' ? 'content_en' : 'content')
            });
            editor.on('input change keyup setcontent', updateCounter);
        }
    });

    form.addEventListener('submit', () => tinymce.triggerSave());
    slug.addEventListener('input', () => slugTouched = slug.value.trim() !== '');
    title.addEventListener('input', () => {
        if (!slugTouched) slug.value = makeSlug(title.value);
    });

    imageInput.addEventListener('change', function () {
        const file = this.files && this.files[0];
        if (!file) return;
        featuredId.value = '';
        setFeaturedPreview(URL.createObjectURL(file), file.name);
    });

    document.getElementById('seoToggle').addEventListener('click', () => {
        document.getElementById('seoBody').classList.toggle('is-collapsed');
    });

    function count(inputId, outputId, max) {
        const input = document.getElementById(inputId);
        const output = document.getElementById(outputId);
        const update = () => output.textContent = '(' + input.value.length + '/' + max + ')';
        input.addEventListener('input', update);
        update();
    }
    count('metaTitle', 'metaTitleCount', 60);
    count('metaDescription', 'metaDescCount', 160);

    document.querySelectorAll('[data-open-media]').forEach((button) => {
        button.addEventListener('click', () => openMediaPicker(button.dataset.openMedia));
    });

    const modal = document.getElementById('mediaPickerModal');
    const grid = document.getElementById('mediaPickerGrid');
    const empty = document.getElementById('mediaPickerEmpty');
    const search = document.getElementById('mediaPickerSearch');
    document.getElementById('mediaPickerClose').addEventListener('click', closeMediaPicker);
    modal.addEventListener('click', (event) => {
        if (event.target === modal) closeMediaPicker();
    });
    search.addEventListener('input', renderMediaItems);

    function openMediaPicker(target) {
        mediaTarget = target || 'content';
        modal.hidden = false;
        search.value = '';
        loadMediaItems();
    }

    function closeMediaPicker() {
        modal.hidden = true;
    }

    function loadMediaItems() {
        grid.innerHTML = '<div class="media-picker-loading">Cargando imagenes...</div>';
        fetch('<?= e(url('/media/picker?type=image')) ?>')
            .then((response) => response.json())
            .then((json) => {
                mediaItems = json.items || [];
                renderMediaItems();
            })
            .catch(() => {
                mediaItems = [];
                renderMediaItems();
            });
    }

    function renderMediaItems() {
        const term = search.value.trim().toLowerCase();
        const filtered = mediaItems.filter((item) => !term || item.name.toLowerCase().includes(term) || (item.alt || '').toLowerCase().includes(term));
        empty.hidden = filtered.length > 0;
        grid.innerHTML = filtered.map((item) => `
            <button type="button" class="media-picker-item" data-id="${item.id}" data-url="${item.url}" data-path="${item.path}" data-alt="${escapeHtml(item.alt || item.name)}" data-name="${escapeHtml(item.name)}">
                <img src="${item.path}" alt="${escapeHtml(item.alt || item.name)}" loading="lazy">
                <span>${escapeHtml(item.name)}</span>
            </button>
        `).join('');
        grid.querySelectorAll('.media-picker-item').forEach((button) => {
            button.addEventListener('click', () => selectMediaItem(button));
        });
    }

    function selectMediaItem(button) {
        const item = {
            id: button.dataset.id,
            url: button.dataset.url,
            path: button.dataset.path,
            alt: button.dataset.alt,
            name: button.dataset.name,
        };
        if (mediaTarget === 'featured') {
            featuredId.value = item.id;
            imageInput.value = '';
            setFeaturedPreview(item.path, item.name);
        } else {
            const editorId = mediaTarget === 'content_en' ? 'contentEditorEn' : 'contentEditor';
            tinymce.get(editorId).insertContent(`<figure><img src="${item.url}" alt="${escapeHtml(item.alt)}"><figcaption>${escapeHtml(item.alt)}</figcaption></figure>`);
            updateCounter();
        }
        closeMediaPicker();
    }

    function setFeaturedPreview(url, name) {
        const current = document.getElementById('featuredPreview');
        const fileName = document.getElementById('featuredFileName');
        if (current.tagName === 'IMG') {
            current.src = url;
        } else {
            const img = document.createElement('img');
            img.id = 'featuredPreview';
            img.src = url;
            img.alt = '';
            current.replaceWith(img);
        }
        fileName.textContent = name || '';
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
    }
})();
</script>
