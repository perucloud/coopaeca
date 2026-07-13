<?php
// Identidad de la cooperativa (Configuracion) para el encabezado del visor.
// Consulta directa con fallback: el modal nunca debe romper la pagina.
try {
    $voucherBrand = Database::connection()->query(
        "SELECT setting_key, setting_value FROM settings
         WHERE setting_key IN ('cooperative_name','header_logo_path','topbar_phone','topbar_address','ruc')"
    )->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (Throwable $e) {
    $voucherBrand = [];
}
$voucherBrandName = trim((string)($voucherBrand['cooperative_name'] ?? '')) ?: 'COOPAECA';
$voucherBrandLogo = trim((string)($voucherBrand['header_logo_path'] ?? ''));
$voucherBrandPhone = trim((string)($voucherBrand['topbar_phone'] ?? ''));
$voucherBrandAddress = trim((string)($voucherBrand['topbar_address'] ?? ''));
$voucherBrandRuc = trim((string)($voucherBrand['ruc'] ?? ''));
$voucherBrandContact = implode(' · ', array_filter([$voucherBrandAddress, $voucherBrandPhone]));
?>
<div class="modal-overlay voucher-viewer" id="voucherModal" style="display:none" role="dialog" aria-modal="true" aria-labelledby="voucherModalTitle">
 <div class="modal-box voucher-viewer-box" role="document">
  <div class="modal-header voucher-viewer-header">
   <div class="voucher-viewer-brand">
    <?php if ($voucherBrandLogo): ?>
    <span class="voucher-viewer-logo"><img src="<?= e(url('/' . $voucherBrandLogo)) ?>" alt="<?= e($voucherBrandName) ?>"></span>
    <?php endif; ?>
    <div class="voucher-viewer-brand-info">
     <strong><?= e($voucherBrandName) ?></strong>
     <?php if ($voucherBrandContact !== ''): ?><small><?= e($voucherBrandContact) ?></small><?php endif; ?>
     <?php if ($voucherBrandRuc !== ''): ?><small>RUC <?= e($voucherBrandRuc) ?></small><?php endif; ?>
    </div>
   </div>
   <div class="voucher-viewer-title"><span class="voucher-viewer-eyebrow">COMPROBANTE DE PAGO</span><h3 id="voucherModalTitle">Voucher <span id="voucherModalCode"></span></h3></div>
   <button type="button" class="modal-close" data-voucher-close aria-label="Cerrar visor de voucher">&times;</button>
  </div>
  <div class="modal-body voucher-viewer-body"><div class="voucher-viewer-loader" id="voucherModalLoader" role="status"><span class="voucher-spinner" aria-hidden="true"></span><strong>Cargando voucher...</strong><small>Validando y preparando la vista segura</small></div><img class="voucher-viewer-image" id="voucherModalImage" alt="Voucher de pago" hidden><iframe class="voucher-viewer-frame" id="voucherModalFrame" title="Vista previa del voucher de pago" hidden></iframe></div>
  <div class="modal-footer voucher-viewer-footer"><span class="voucher-viewer-hint">El documento se muestra desde una ruta protegida.</span><div><button type="button" class="button ghost" data-voucher-close>Cerrar</button><a class="button primary" id="voucherModalExternal" href="#" target="_blank" rel="noopener"><?= icon('external-link') ?> Abrir en pestaña</a></div></div>
 </div>
</div>
