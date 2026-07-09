<?php
if (!function_exists('media_size')) {
    function media_size(int $bytes): string {
        if ($bytes >= 1048576) return number_format($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return number_format($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
?>

<section class="media-admin-head">
    <div>
        <span class="eyebrow">Biblioteca</span>
        <h2>Archivos y Media</h2>
        <p>Gestiona imagenes y documentos reutilizables para noticias, landing y contenido del sistema.</p>
    </div>
    <button class="button primary" type="button" id="mediaUploadToggle"><?= icon('image') ?> Agregar archivos</button>
</section>

<section class="media-stats-grid">
    <article class="media-stat">
        <?= icon('folder') ?>
        <span>Total</span>
        <strong><?= (int)$stats['total'] ?></strong>
    </article>
    <article class="media-stat">
        <?= icon('image') ?>
        <span>Imagenes</span>
        <strong><?= (int)$stats['images'] ?></strong>
    </article>
    <article class="media-stat">
        <?= icon('file') ?>
        <span>PDF</span>
        <strong><?= (int)$stats['pdfs'] ?></strong>
    </article>
    <article class="media-stat">
        <?= icon('upload') ?>
        <span>Almacenado</span>
        <strong><?= e(media_size((int)$stats['bytes'])) ?></strong>
    </article>
</section>

<section class="media-upload-panel" id="mediaUploadPanel" hidden>
    <div class="section-title">
        <h2>Subir nuevos archivos</h2>
        <span>Imagenes JPG, PNG, GIF o WebP hasta 5 MB. PDF hasta 20 MB.</span>
    </div>
    <form method="post" action="<?= e(url('/media/store')) ?>" enctype="multipart/form-data" id="mediaUploadForm">
        <?= csrf_field() ?>
        <input type="file" name="files[]" id="mediaFilesInput" multiple accept="image/jpeg,image/png,image/webp,image/gif,application/pdf,.pdf" hidden>
        <div class="media-dropzone" id="mediaDropzone" role="button" tabindex="0">
            <?= icon('upload') ?>
            <strong>Arrastra archivos aqui o haz clic para seleccionar</strong>
            <span>Soporta subida multiple, imagenes y documentos PDF.</span>
        </div>
        <label>Texto alternativo comun
            <input name="alt_text" value="<?= e(old('alt_text')) ?>" placeholder="Descripcion breve para accesibilidad">
        </label>
        <div class="media-selected" id="mediaSelected"></div>
        <div class="form-actions">
            <button class="button primary" type="submit" id="mediaSubmit" disabled><?= icon('upload') ?> <span id="mediaSubmitText">Confirmar subida</span></button>
            <button class="button ghost" type="button" id="mediaCancelUpload">Cancelar</button>
        </div>
    </form>
</section>

<?php if (!$items): ?>
    <section class="media-empty">
        <?= icon('image') ?>
        <h3>Sin archivos todavia</h3>
        <p>Sube tu primera imagen o PDF usando el boton superior.</p>
    </section>
<?php else: ?>
    <section class="media-library-grid">
        <?php foreach ($items as $item):
            $isImage = str_starts_with((string)$item['mime_type'], 'image/');
            $isPdf = $item['mime_type'] === 'application/pdf';
            $url = url('/' . $item['disk_path']);
            $date = !empty($item['created_at']) ? date('d/m/Y', strtotime($item['created_at'])) : '';
        ?>
            <article class="media-library-card">
                <div class="media-library-preview">
                    <?php if ($isImage): ?>
                        <img src="<?= e($url) ?>" alt="<?= e($item['alt_text'] ?: $item['original_name']) ?>" loading="lazy" onerror="this.style.display='none';this.nextElementSibling.style.display='grid';">
                        <div class="media-preview-fallback" style="display:none"><?= icon('image') ?><span>Sin vista previa</span></div>
                    <?php elseif ($isPdf): ?>
                        <div class="media-file-type media-file-pdf"><?= icon('file') ?><strong>PDF</strong></div>
                    <?php else: ?>
                        <div class="media-file-type"><?= icon('folder') ?><strong><?= e(strtoupper(pathinfo((string)$item['disk_path'], PATHINFO_EXTENSION))) ?></strong></div>
                    <?php endif; ?>
                    <div class="media-card-overlay">
                        <a href="<?= e($url) ?>" target="_blank" rel="noopener" title="Ver archivo"><?= icon('search') ?></a>
                        <button type="button" class="media-copy-url" data-url="<?= e(absolute_url('/' . $item['disk_path'])) ?>" title="Copiar URL"><?= icon('copy') ?></button>
                        <form method="post" action="<?= e(url('/media/delete')) ?>" data-confirm="Eliminar este archivo? Esta accion no se puede deshacer.">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= e($item['id']) ?>">
                            <button type="submit" title="Eliminar"><?= icon('trash') ?></button>
                        </form>
                    </div>
                </div>
                <div class="media-library-info">
                    <strong title="<?= e($item['original_name']) ?>"><?= e($item['original_name']) ?></strong>
                    <div>
                        <span><?= e(media_size((int)$item['size_bytes'])) ?></span>
                        <span><?= e($date) ?></span>
                    </div>
                    <button type="button" class="button small ghost media-copy-url" data-url="<?= e(absolute_url('/' . $item['disk_path'])) ?>"><?= icon('copy') ?> Copiar URL</button>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<div class="media-toast" id="mediaToast" hidden><?= icon('check-circle') ?> URL copiada al portapapeles</div>

<script>
(function () {
    const toggle = document.getElementById('mediaUploadToggle');
    const panel = document.getElementById('mediaUploadPanel');
    const cancel = document.getElementById('mediaCancelUpload');
    const dropzone = document.getElementById('mediaDropzone');
    const input = document.getElementById('mediaFilesInput');
    const selected = document.getElementById('mediaSelected');
    const submit = document.getElementById('mediaSubmit');
    const toast = document.getElementById('mediaToast');
    let toastTimer = null;

    function formatSize(bytes) {
        if (bytes >= 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
        if (bytes >= 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return bytes + ' B';
    }

    function renderSelected() {
        const files = Array.from(input.files || []);
        submit.disabled = files.length === 0;
        const submitText = document.getElementById('mediaSubmitText');
        if (submitText) {
            submitText.textContent = files.length > 0 ? `Confirmar subida (${files.length})` : 'Confirmar subida';
        }
        selected.innerHTML = files.map((file) => `
            <div class="media-selected-item">
                <span>${file.name}</span>
                <strong>${formatSize(file.size)}</strong>
            </div>
        `).join('');
    }

    function openPanel() {
        panel.hidden = false;
        panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    toggle?.addEventListener('click', openPanel);
    cancel?.addEventListener('click', () => {
        panel.hidden = true;
        input.value = '';
        renderSelected();
    });

    dropzone?.addEventListener('click', () => input.click());
    dropzone?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            input.click();
        }
    });
    dropzone?.addEventListener('dragover', (event) => {
        event.preventDefault();
        dropzone.classList.add('is-dragging');
    });
    dropzone?.addEventListener('dragleave', () => dropzone.classList.remove('is-dragging'));
    dropzone?.addEventListener('drop', (event) => {
        event.preventDefault();
        dropzone.classList.remove('is-dragging');
        const dt = new DataTransfer();
        Array.from(event.dataTransfer.files || []).forEach((file) => dt.items.add(file));
        input.files = dt.files;
        renderSelected();
    });
    input?.addEventListener('change', renderSelected);

    function showToast() {
        toast.hidden = false;
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => toast.hidden = true, 2400);
    }

    document.querySelectorAll('.media-copy-url').forEach((button) => {
        button.addEventListener('click', async () => {
            const url = button.dataset.url || '';
            try {
                await navigator.clipboard.writeText(url);
            } catch (error) {
                const temp = document.createElement('textarea');
                temp.value = url;
                temp.style.position = 'fixed';
                temp.style.opacity = '0';
                document.body.appendChild(temp);
                temp.select();
                document.execCommand('copy');
                temp.remove();
            }
            showToast();
        });
    });
})();
</script>
