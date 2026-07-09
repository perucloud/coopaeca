<style>
    .settings-head { margin-bottom: 20px; }
    .settings-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 20px; align-items: start; }
    .settings-card { background: var(--panel); border-radius: 14px; padding: 22px; box-shadow: var(--shadow); border-top: 4px solid transparent; }
    .settings-card-head { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 18px; }
    .settings-card-icon { width: 40px; height: 40px; border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0; }
    .settings-card-icon .icon, .settings-card-icon .social-icon { width: 20px; height: 20px; }
    .settings-card-head h3 { margin: 0 0 3px; font-size: 15px; }
    .settings-card-head p { margin: 0; font-size: 12.5px; color: var(--muted); line-height: 1.4; }
    .settings-card label { display: block; margin-bottom: 14px; font-size: 13px; font-weight: 700; }
    .settings-card label:last-child { margin-bottom: 0; }
    .settings-card input, .settings-card textarea { margin-top: 6px; }
    .settings-card .settings-hint { display: block; margin-top: 4px; font-size: 11.5px; font-weight: 500; color: var(--muted); }
    .settings-card .settings-file-current { display: flex; align-items: center; gap: 8px; margin-top: 8px; font-size: 12px; font-weight: 500; color: var(--muted); }
    .settings-card .settings-file-current img { border-radius: 4px; }
    .settings-card-full { grid-column: 1 / -1; }
    .settings-map-preview { width: 100%; max-width: 560px; aspect-ratio: 16/9; border-radius: 12px; overflow: hidden; margin-top: 14px; box-shadow: 0 8px 20px rgba(0,0,0,.1); }

    .sc-sistema     { border-top-color: #6366f1; } .sc-sistema     .settings-card-icon { background: #eef2ff; color: #4f46e5; }
    .sc-soporte     { border-top-color: #059669; } .sc-soporte     .settings-card-icon { background: #ecfdf5; color: #059669; }
    .sc-seo         { border-top-color: #9333ea; } .sc-seo         .settings-card-icon { background: #f5f0ff; color: #7c3aed; }
    .sc-topbar      { border-top-color: #d97706; } .sc-topbar      .settings-card-icon { background: #fffbeb; color: #d97706; }
    .sc-logos       { border-top-color: #db2777; } .sc-logos       .settings-card-icon { background: #fdf0f5; color: #db2777; }
    .sc-whatsapp    { border-top-color: #16a34a; } .sc-whatsapp    .settings-card-icon { background: #f0fdf4; color: #16a34a; }
    .sc-mapa        { border-top-color: #0284c7; } .sc-mapa        .settings-card-icon { background: #eff9ff; color: #0284c7; }

    .settings-actions { margin-top: 20px; padding: 16px 22px; background: var(--panel); border-radius: 14px; box-shadow: var(--shadow); display: flex; justify-content: flex-end; }
</style>

<div class="settings-head">
    <h2 style="margin:0 0 4px">Configuración del sistema</h2>
    <span class="muted">Datos generales de la cooperativa, identidad visual y soporte. Todo se guarda junto con un solo botón.</span>
</div>

<form method="post" action="<?= e(url('/settings')) ?>" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="settings-grid">

        <!-- IDENTIDAD DEL SISTEMA -->
        <div class="settings-card sc-sistema">
            <div class="settings-card-head">
                <div class="settings-card-icon"><?= icon('layers') ?></div>
                <div>
                    <h3>Identidad del sistema</h3>
                    <p>Nombre del panel admin y datos generales de la cooperativa.</p>
                </div>
            </div>
            <label>Nombre del sistema (sidebar del panel admin)
                <input name="app_name" value="<?= e($settings['app_name'] ?? config_app('name')) ?>">
            </label>
            <label>Nombre de la cooperativa
                <input name="cooperative_name" value="<?= e($settings['cooperative_name'] ?? 'COOPAECA') ?>">
            </label>
            <label>RUC
                <input name="ruc" value="<?= e($settings['ruc'] ?? '') ?>">
            </label>
        </div>

        <!-- CONTACTO Y SOPORTE -->
        <div class="settings-card sc-soporte">
            <div class="settings-card-head">
                <div class="settings-card-icon"><?= icon('mail') ?></div>
                <div>
                    <h3>Contacto y soporte</h3>
                    <p>Correo que recibe las notificaciones del formulario de contacto.</p>
                </div>
            </div>
            <label>Correo de soporte
                <input type="email" name="support_email" value="<?= e($settings['support_email'] ?? '') ?>">
            </label>
        </div>

        <!-- SITIO WEB PUBLICO -->
        <div class="settings-card sc-seo">
            <div class="settings-card-head">
                <div class="settings-card-icon"><?= icon('layout') ?></div>
                <div>
                    <h3>Sitio web público</h3>
                    <p>Título de la pestaña del navegador y favicon.</p>
                </div>
            </div>
            <label>Título del sitio web
                <input name="site_title" value="<?= e($settings['site_title'] ?? ($settings['cooperative_name'] ?? 'COOPAECA') . ' - Cooperativa Agraria de Exportación') ?>">
            </label>
            <label>Favicon (ícono de la pestaña)
                <input type="file" name="favicon" accept="image/png,image/x-icon,.ico">
                <?php if (!empty($settings['favicon_path'])): ?>
                    <span class="settings-file-current"><img src="<?= e(url('/' . $settings['favicon_path'])) ?>" alt="Favicon" width="20" height="20"> Favicon actual</span>
                <?php endif; ?>
            </label>
        </div>

        <!-- BARRA SUPERIOR DEL LANDING -->
        <div class="settings-card sc-topbar">
            <div class="settings-card-head">
                <div class="settings-card-icon"><?= icon('columns') ?></div>
                <div>
                    <h3>Barra superior del landing</h3>
                    <p>Se muestra en la franja verde superior de todas las páginas públicas.</p>
                </div>
            </div>
            <label>Teléfono de contacto
                <input name="topbar_phone" value="<?= e($settings['topbar_phone'] ?? '+51 999 999 999') ?>">
            </label>
            <label>Correo de contacto
                <input type="email" name="topbar_email" value="<?= e($settings['topbar_email'] ?? 'comercio@coopaeca.org.pe') ?>">
            </label>
            <label>Dirección
                <input name="topbar_address" value="<?= e($settings['topbar_address'] ?? 'Av. Principal s/n, Perú') ?>">
            </label>
        </div>

        <!-- IDENTIDAD VISUAL -->
        <div class="settings-card sc-logos">
            <div class="settings-card-head">
                <div class="settings-card-icon"><?= icon('image') ?></div>
                <div>
                    <h3>Identidad visual</h3>
                    <p>Logos que se muestran en el encabezado y pie de página del sitio.</p>
                </div>
            </div>
            <label>Logo header
                <input type="file" name="header_logo" accept="image/png,image/jpeg,image/webp">
                <?php if (!empty($settings['header_logo_path'])): ?>
                    <span class="settings-file-current"><img src="<?= e(url('/' . $settings['header_logo_path'])) ?>" alt="Logo header" width="20" height="20"> Logo actual</span>
                <?php endif; ?>
            </label>
            <label>Logo footer
                <input type="file" name="footer_logo" accept="image/png,image/jpeg,image/webp">
                <?php if (!empty($settings['footer_logo_path'])): ?>
                    <span class="settings-file-current"><img src="<?= e(url('/' . $settings['footer_logo_path'])) ?>" alt="Logo footer" width="20" height="20"> Logo actual</span>
                <?php endif; ?>
            </label>
        </div>

        <!-- WHATSAPP -->
        <div class="settings-card sc-whatsapp">
            <div class="settings-card-head">
                <div class="settings-card-icon"><?= social_icon('whatsapp') ?></div>
                <div>
                    <h3>WhatsApp</h3>
                    <p>Números usados en los botones de contacto directo del sitio.</p>
                </div>
            </div>
            <label>Número en el Landing (botón flotante)
                <input name="whatsapp_landing" value="<?= e($settings['whatsapp_landing'] ?? '51999999999') ?>" placeholder="51999999999">
                <span class="settings-hint">Formato: 51999999999 (sin +, sin espacios)</span>
            </label>
            <label>Número en Productos ("Consultar por WhatsApp")
                <input name="whatsapp_products" value="<?= e($settings['whatsapp_products'] ?? '51999999999') ?>" placeholder="51999999999">
                <span class="settings-hint">Formato: 51999999999 (sin +, sin espacios)</span>
            </label>
        </div>

        <!-- MAPA -->
        <div class="settings-card sc-mapa settings-card-full">
            <div class="settings-card-head">
                <div class="settings-card-icon"><?= icon('map-pin') ?></div>
                <div>
                    <h3>Mapa de ubicación</h3>
                    <p>En Google Maps: busca tu ubicación → botón <strong>Compartir</strong> → pestaña <strong>Insertar un mapa</strong> → copia el HTML. Solo se acepta código de Google Maps.</p>
                </div>
            </div>
            <div class="form-grid-2">
                <label>Etiqueta pequeña (encima del título)
                    <input name="map_tag" value="<?= e($settings['map_tag'] ?? 'Ubícanos') ?>">
                </label>
                <label>Título del mapa
                    <input name="map_title" value="<?= e($settings['map_title'] ?? 'Localice nuestra empresa') ?>">
                </label>
            </div>
            <label>Descripción del mapa
                <textarea name="map_description" rows="2"><?= e($settings['map_description'] ?? 'Visítenos y conozca dónde nace la calidad de nuestros productos. Estamos listos para atenderlo y brindarle información sobre nuestros servicios, productos y oportunidades de negocio.') ?></textarea>
            </label>
            <label>Código del mapa (iframe)
                <textarea name="map_embed_html" rows="4" placeholder='&lt;iframe src="https://www.google.com/maps/embed?..." ...&gt;&lt;/iframe&gt;'><?= e($settings['map_embed_html'] ?? '') ?></textarea>
            </label>
            <?php if (!empty($settings['map_embed_html'])): ?>
            <span class="settings-hint">Vista previa actual:</span>
            <div class="settings-map-preview">
                <?= $settings['map_embed_html'] /* ya sanitizado por SettingsController::sanitizeMapEmbed() al guardarse */ ?>
            </div>
            <?php endif; ?>
        </div>

    </div>

    <div class="settings-actions">
        <button class="button primary" type="submit"><?= icon('check-circle') ?> Guardar configuración</button>
    </div>
</form>
