<?php
$landingTitle = old('about_title', $settings['about_title'] ?? 'Una cooperativa comprometida con el cacao peruano');
$landingBody = old('about_body', $settings['about_body'] ?? 'Somos una cooperativa agraria dedicada al acopio, procesamiento y comercializacion de cacao. Trabajamos junto a productores para mejorar calidad, precio, trazabilidad y acceso a nuevos mercados.');
$landingValues = old('about_values', $settings['about_values'] ?? "Acopio transparente y pago justo al productor\nTrazabilidad de origen a destino\nMejora continua en fermentacion y secado");
$pageTitle = old('about_more_title', $settings['about_more_title'] ?? 'Somos una cooperativa que crece junto al cacao peruano');
$pageBody = old('about_more_body', $settings['about_more_body'] ?? 'Somos una cooperativa dedicada a fortalecer la cadena de valor del cacao, integrando acopio responsable, asistencia tecnica, control de calidad y comercializacion sostenible. Nuestro trabajo nace del compromiso con las familias productoras y con la calidad del cacao de nuestra tierra.');
$historyTitle = old('about_history_title', $settings['about_history_title'] ?? 'Nuestra historia');
$historyBody = old('about_history_body', $settings['about_history_body'] ?? 'La cooperativa nace como una iniciativa de productores organizados que buscaban mejores oportunidades para su cacao. Con el paso del tiempo, fortalecimos nuestros procesos de acopio, fermentacion, secado y trazabilidad para conectar el esfuerzo del campo con mercados mas exigentes.');
$mission = old('about_mission', $settings['about_mission'] ?? 'Impulsar el desarrollo de nuestros socios mediante servicios de calidad, comercializacion justa y mejora continua del cacao.');
$vision = old('about_vision', $settings['about_vision'] ?? 'Ser una cooperativa referente en cacao sostenible, trazable y de alta calidad.');
$valuesList = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string)$landingValues))));
?>

<form method="post" action="<?= e(url('/about')) ?>" class="about-editor" id="aboutEditorForm">
    <?= csrf_field() ?>

    <div class="about-hero">
        <div>
            <a href="<?= e(url('/dashboard')) ?>" class="about-back"><?= icon('arrow-left') ?> Volver al dashboard</a>
            <span class="about-eyebrow"><?= icon('users') ?> Modulo institucional</span>
            <h2>Nosotros</h2>
            <p>Administra el contenido institucional que aparece en la seccion breve del landing y en la pagina publica completa.</p>
        </div>
        <div class="about-hero-actions">
            <a class="button ghost" href="<?= e(url('/nosotros')) ?>" target="_blank" rel="noopener"><?= icon('external-link') ?> Ver pagina</a>
            <button class="button primary" type="submit"><?= icon('save') ?> Guardar cambios</button>
        </div>
    </div>

    <div class="about-metrics">
        <div class="about-metric-card">
            <span><?= icon('layout') ?></span>
            <strong>Landing</strong>
            <small><?= e(mb_strlen((string)$landingBody)) ?> caracteres</small>
        </div>
        <div class="about-metric-card">
            <span><?= icon('file-text') ?></span>
            <strong>Pagina completa</strong>
            <small><?= e(mb_strlen((string)$pageBody) + mb_strlen((string)$historyBody)) ?> caracteres</small>
        </div>
        <div class="about-metric-card">
            <span><?= icon('check-circle') ?></span>
            <strong>Compromisos</strong>
            <small><?= count($valuesList) ?> items registrados</small>
        </div>
        <div class="about-metric-card">
            <span><?= icon('target') ?></span>
            <strong>Mision y vision</strong>
            <small>Contenido institucional</small>
        </div>
    </div>

    <div class="about-layout">
        <section class="about-workspace">
            <div class="about-tabs" role="tablist" aria-label="Secciones de Nosotros">
                <button type="button" class="about-tab is-active" data-about-tab="landing"><?= icon('home') ?> Landing</button>
                <button type="button" class="about-tab" data-about-tab="page"><?= icon('file-text') ?> Pagina completa</button>
                <button type="button" class="about-tab" data-about-tab="identity"><?= icon('target') ?> Mision y vision</button>
            </div>

            <div class="about-panel is-active" data-about-panel="landing">
                <div class="about-card">
                    <div class="about-card-head">
                        <span><?= icon('layout') ?></span>
                        <div>
                            <h3>Seccion breve del landing</h3>
                            <p>Contenido compacto que aparece en la pagina principal.</p>
                        </div>
                    </div>
                    <div class="about-card-body">
                        <label>Titulo principal
                            <input name="about_title" value="<?= e($landingTitle) ?>" data-preview-source="landing-title">
                        </label>
                        <label>Descripcion breve
                            <textarea name="about_body" rows="6" data-preview-source="landing-body"><?= e($landingBody) ?></textarea>
                        </label>
                        <label>Checks del landing, uno por linea
                            <textarea name="about_values" rows="5" data-preview-source="landing-values"><?= e($landingValues) ?></textarea>
                        </label>
                    </div>
                </div>
            </div>

            <div class="about-panel" data-about-panel="page">
                <div class="about-card">
                    <div class="about-card-head">
                        <span><?= icon('file-text') ?></span>
                        <div>
                            <h3>Pagina completa Nosotros</h3>
                            <p>Texto institucional principal y relato historico de la cooperativa.</p>
                        </div>
                    </div>
                    <div class="about-card-body">
                        <label>Titulo de la pagina
                            <input name="about_more_title" value="<?= e($pageTitle) ?>" data-preview-source="page-title">
                        </label>
                        <label>Texto institucional amplio
                            <textarea name="about_more_body" rows="8" data-preview-source="page-body"><?= e($pageBody) ?></textarea>
                        </label>
                        <div class="about-field-grid">
                            <label>Titulo de historia
                                <input name="about_history_title" value="<?= e($historyTitle) ?>" data-preview-source="history-title">
                            </label>
                            <label>Historia de la cooperativa
                                <textarea name="about_history_body" rows="8" data-preview-source="history-body"><?= e($historyBody) ?></textarea>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="about-panel" data-about-panel="identity">
                <div class="about-card">
                    <div class="about-card-head">
                        <span><?= icon('target') ?></span>
                        <div>
                            <h3>Identidad institucional</h3>
                            <p>Define la direccion y el proposito de la cooperativa.</p>
                        </div>
                    </div>
                    <div class="about-card-body">
                        <div class="about-field-grid">
                            <label>Mision
                                <textarea name="about_mission" rows="7" data-preview-source="mission"><?= e($mission) ?></textarea>
                            </label>
                            <label>Vision
                                <textarea name="about_vision" rows="7" data-preview-source="vision"><?= e($vision) ?></textarea>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <aside class="about-preview">
            <div class="about-preview-card">
                <div class="about-preview-cover">
                    <div>
                        <span>Vista previa</span>
                        <strong>Nosotros</strong>
                    </div>
                </div>
                <div class="about-preview-body">
                    <span class="about-preview-tag">Landing</span>
                    <h3 data-preview-target="landing-title"><?= e($landingTitle) ?></h3>
                    <p data-preview-target="landing-body"><?= e($landingBody) ?></p>
                    <ul data-preview-target="landing-values">
                        <?php foreach (array_slice($valuesList, 0, 4) as $value): ?>
                            <li><?= icon('check-circle') ?> <?= e($value) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <div class="about-preview-card is-compact">
                <span class="about-preview-tag">Pagina publica</span>
                <h4 data-preview-target="page-title"><?= e($pageTitle) ?></h4>
                <p data-preview-target="page-body"><?= e($pageBody) ?></p>
                <div class="about-preview-split">
                    <div>
                        <strong>Mision</strong>
                        <p data-preview-target="mission"><?= e($mission) ?></p>
                    </div>
                    <div>
                        <strong>Vision</strong>
                        <p data-preview-target="vision"><?= e($vision) ?></p>
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <div class="about-savebar">
        <div>
            <strong>Contenido institucional</strong>
            <span>Los cambios se reflejan en el landing y en la pagina Nosotros.</span>
        </div>
        <div class="about-savebar-actions">
            <a class="button ghost" href="<?= e(url('/nosotros')) ?>" target="_blank" rel="noopener"><?= icon('external-link') ?> Ver pagina</a>
            <button class="button primary" type="submit"><?= icon('save') ?> Guardar Nosotros</button>
        </div>
    </div>
</form>

<script>
(function () {
    const tabs = document.querySelectorAll('[data-about-tab]');
    const panels = document.querySelectorAll('[data-about-panel]');
    tabs.forEach((tab) => {
        tab.addEventListener('click', () => {
            const target = tab.dataset.aboutTab;
            tabs.forEach((item) => item.classList.toggle('is-active', item === tab));
            panels.forEach((panel) => panel.classList.toggle('is-active', panel.dataset.aboutPanel === target));
        });
    });

    const sourceMap = {
        'landing-title': 'landing-title',
        'landing-body': 'landing-body',
        'page-title': 'page-title',
        'page-body': 'page-body',
        'mission': 'mission',
        'vision': 'vision'
    };

    document.querySelectorAll('[data-preview-source]').forEach((input) => {
        input.addEventListener('input', () => {
            const key = input.dataset.previewSource;
            if (key === 'landing-values') {
                const list = document.querySelector('[data-preview-target="landing-values"]');
                const values = input.value.split(/\r?\n/).map((item) => item.trim()).filter(Boolean).slice(0, 4);
                list.innerHTML = values.map((item) => '<li><?= icon('check-circle') ?> ' + escapeHtml(item) + '</li>').join('');
                return;
            }
            const target = document.querySelector('[data-preview-target="' + sourceMap[key] + '"]');
            if (target) target.textContent = input.value.trim() || 'Sin contenido';
        });
    });

    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' }[char]));
    }
})();
</script>
