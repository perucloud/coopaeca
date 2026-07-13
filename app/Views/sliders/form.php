<?php $editing = is_array($item); ?>
<style>
    .slide-form-langs { display: grid; grid-template-columns: 1fr 1fr; gap: 18px; }
    .slide-form-lang { border: 1px solid var(--line); border-radius: 10px; padding: 14px; }
    .slide-form-lang h4 { margin: 0 0 10px; font-size: 13px; text-transform: uppercase; letter-spacing: .4px; color: var(--muted); }
    .slide-form-lang label { display: block; margin-bottom: 10px; }
    .slide-preview-box { position: relative; aspect-ratio: 1800 / 650; border: 1px dashed var(--line); border-radius: 10px; overflow: hidden; background: #f1f5f9; margin-bottom: 8px; }
    .slide-preview-box img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .slide-preview-empty { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; color: var(--muted); font-size: 13px; }
    .slide-img-hint { color: var(--muted); font-size: 12px; margin: 4px 0 0; }
    @media (max-width: 860px) { .slide-form-langs { grid-template-columns: 1fr; } }
</style>

<section class="card">
    <div class="section-title">
        <h2><?= $editing ? 'Editar slide' : 'Nuevo slide' ?></h2>
    </div>
    <form method="post" action="<?= e(url($editing ? '/sliders/update' : '/sliders/store')) ?>" class="form" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <?php if ($editing): ?><input type="hidden" name="id" value="<?= e($item['id']) ?>"><?php endif; ?>

        <div>
            <label>Imagen del slide <?= $editing ? '(subir una nueva la reemplaza)' : '' ?></label>
            <div class="slide-preview-box">
                <img id="slidePreviewImg" src="<?= $editing ? e(url('/' . $item['disk_path'])) : '' ?>" alt="Vista previa" <?= $editing ? '' : 'hidden' ?>>
                <?php if (!$editing): ?><span class="slide-preview-empty" id="slidePreviewEmpty">Vista previa del slide (proporción real del hero)</span><?php endif; ?>
            </div>
            <input type="file" name="image" id="slideImageInput" accept="image/jpeg,image/png,image/webp,image/gif" <?= $editing ? '' : 'required' ?>>
            <p class="slide-img-hint">Recomendado: 1800 × 650 px, máximo 10 MB (JPG, PNG, WebP o GIF). La imagen se recorta a la proporción del hero, se comprime y se convierte a WebP automáticamente.</p>
        </div>

        <div class="slide-form-langs">
            <div class="slide-form-lang">
                <h4>Español</h4>
                <label>Título *
                    <input name="title" value="<?= e(old('title', $item['title'] ?? '')) ?>" maxlength="255" required placeholder="Ej: Cacao fino, origen sostenible.">
                </label>
                <label>Descripción
                    <textarea name="subtitle" rows="3" maxlength="255" placeholder="Texto que acompaña al título en el hero."><?= e(old('subtitle', $item['subtitle'] ?? '')) ?></textarea>
                </label>
                <label>Badge
                    <input name="badge" value="<?= e(old('badge', $item['badge'] ?? '')) ?>" maxlength="255" placeholder="Ej: Acopio • Calidad • Derivados">
                </label>
            </div>
            <div class="slide-form-lang">
                <h4>English (opcional, usa el español si está vacío)</h4>
                <label>Title
                    <input name="title_en" value="<?= e(old('title_en', $item['title_en'] ?? '')) ?>" maxlength="255" placeholder="Ex: Fine cacao, sustainable origin.">
                </label>
                <label>Description
                    <textarea name="subtitle_en" rows="3" maxlength="255" placeholder="Text shown next to the title."><?= e(old('subtitle_en', $item['subtitle_en'] ?? '')) ?></textarea>
                </label>
                <label>Badge
                    <input name="badge_en" value="<?= e(old('badge_en', $item['badge_en'] ?? '')) ?>" maxlength="255" placeholder="Ex: Sourcing • Quality • Derivatives">
                </label>
            </div>
        </div>

        <label class="check">
            <input type="checkbox" name="is_active" value="1" <?= (int)old('is_active', $item['is_active'] ?? 1) === 1 ? 'checked' : '' ?>> Slide activo (visible en el landing)
        </label>

        <div class="form-actions">
            <button class="button primary" type="submit">Guardar</button>
            <a class="button ghost" href="<?= e(url('/sliders')) ?>">Cancelar</a>
        </div>
    </form>
</section>

<script>
(function () {
    const input = document.getElementById('slideImageInput');
    const img = document.getElementById('slidePreviewImg');
    const empty = document.getElementById('slidePreviewEmpty');
    input.addEventListener('change', function () {
        const file = input.files && input.files[0];
        if (!file || !file.type.startsWith('image/')) return;
        img.src = URL.createObjectURL(file);
        img.hidden = false;
        if (empty) empty.hidden = true;
    });
})();
</script>
