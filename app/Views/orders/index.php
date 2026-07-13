<?php
$statusLabels = ['pendiente'=>'Pendiente','voucher_enviado'=>'Voucher enviado','en_revision'=>'En revisión','aprobado'=>'Aprobado','rechazado'=>'Rechazado','cancelado'=>'Cancelado'];
$badgeClass = ['aprobado'=>'ok','rechazado'=>'off','cancelado'=>'off','en_revision'=>'warn','voucher_enviado'=>'muted','pendiente'=>'muted'];
$deliveryLabels = ['sent'=>'Enviado','failed'=>'Fallido','pending'=>'Pendiente','prepared'=>'WhatsApp preparado'];
$deliveryBadges = ['sent'=>'ok','failed'=>'off','pending'=>'warn','prepared'=>'muted'];
?>
<section class="page-card orders-page">
 <div class="page-header"><div><h2>Pedidos</h2><span>Valida pagos y administra comprobantes y entregas al cliente.</span></div><button type="button" class="button pdf-report" id="openPdfModal"><?= icon('printer') ?> Imprimir reporte de pedidos</button></div>
 <div class="stats-grid compact"><?php foreach($statusLabels as $key=>$label): ?><div class="stat-card soft"><span><?= e($label) ?></span><strong><?= (int)($stats[$key]??0) ?></strong></div><?php endforeach ?></div>
 <div class="filter-panel">
  <div class="filter-panel-head"><span class="filter-panel-icon"><?= icon('search') ?></span><div><strong>Filtros de búsqueda</strong><span>Ubica pedidos por código, comprador, estado o fecha.</span></div></div>
  <form method="get" action="<?= e(url('/orders')) ?>" class="filter-grid">
   <label class="filter-field wide"><span>Buscar</span><input class="form-control" name="q" value="<?= e($filters['q']) ?>" placeholder="Código, comprador, DNI/RUC, WhatsApp u operación"></label>
   <label class="filter-field"><span>Estado</span><select class="form-control" name="status"><option value="">Todos</option><?php foreach($statusLabels as $key=>$label): ?><option value="<?= e($key) ?>" <?= $filters['status']===$key?'selected':'' ?>><?= e($label) ?></option><?php endforeach ?></select></label>
   <label class="filter-field"><span>Desde</span><input class="form-control" type="date" name="from" value="<?= e($filters['from']) ?>"></label>
   <label class="filter-field"><span>Hasta</span><input class="form-control" type="date" name="to" value="<?= e($filters['to']) ?>"></label>
   <div class="filter-actions"><button class="button primary" type="submit"><?= icon('search') ?> Filtrar</button><a class="button ghost" href="<?= e(url('/orders')) ?>">Limpiar</a></div>
  </form>
 </div>
 <div class="table-wrap"><table class="orders-table" id="ordersTable" data-orders-refresh-url="<?= e(url('/orders/rows' . (($qs = http_build_query($filters)) !== '' ? '?' . $qs : ''))) ?>"><thead><tr><th>Pedido / cliente</th><th>Contacto</th><th>Compra</th><th>Pago</th><th>Total</th><th>Estado</th><th>Último envío</th><th>Acciones</th></tr></thead><tbody id="ordersTableBody">
 <?php require __DIR__ . '/_rows.php'; ?>
 </tbody></table></div>
</section>
<?php require __DIR__.'/voucher-modal.php'; ?>
<?php require __DIR__.'/detail-modal.php'; ?>
<?php require __DIR__.'/cancel-sale-modal.php'; ?>
<div class="modal-overlay" id="pdfModal" style="display:none"><div class="modal-box modal-xl"><div class="modal-header"><h3>Reporte de pedidos en PDF</h3><button type="button" class="modal-close" data-close>&times;</button></div><div class="modal-body pdf-modal-body"><iframe id="pdfFrame" class="pdf-frame" title="Previsualización PDF"></iframe></div><div class="modal-footer"><a class="button ghost" href="<?= e(url('/orders/pdf').'?'.http_build_query($filters)) ?>" download="pedidos.pdf"><?= icon('download') ?> Descargar</a><button type="button" class="button primary" id="pdfPrint"><?= icon('printer') ?> Imprimir</button></div></div></div>
<script>(function(){var m=document.getElementById('pdfModal'),f=document.getElementById('pdfFrame'),u=<?= json_encode(url('/orders/pdf').'?'.http_build_query($filters)) ?>;document.getElementById('openPdfModal').addEventListener('click',function(){if(!f.src)f.src=u;m.style.display='flex'});m.querySelector('[data-close]').addEventListener('click',function(){m.style.display='none'});m.addEventListener('click',function(e){if(e.target===m)m.style.display='none'});document.getElementById('pdfPrint').addEventListener('click',function(){try{f.contentWindow.focus();f.contentWindow.print()}catch(e){window.open(u,'_blank')}})})();</script>
