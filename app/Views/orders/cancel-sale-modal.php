<div class="modal-overlay" id="cancelSaleModal" style="display:none" role="dialog" aria-modal="true" aria-labelledby="cancelSaleModalTitle">
 <div class="modal-box modal-sm">
  <div class="modal-header">
   <h3 id="cancelSaleModalTitle">Anular venta</h3>
   <button type="button" class="modal-close" data-cancel-sale-close aria-label="Cerrar">&times;</button>
  </div>
  <form method="post" action="<?= e(url('/sales/cancel')) ?>" id="cancelSaleForm">
   <?= csrf_field() ?>
   <input type="hidden" name="id" id="cancelSaleId" value="">
   <div class="modal-body">
    <div class="modal-icon danger"><?= icon('x') ?></div>
    <p>¿Deseas anular la venta <strong id="cancelSaleCode"></strong>? Esta acción revertirá el stock y no se puede deshacer.</p>
    <label>Motivo (opcional)
     <textarea class="form-control" name="notes" rows="2" placeholder="Ej. Cliente solicitó cancelar el pedido"></textarea>
    </label>
   </div>
   <div class="modal-footer">
    <button class="button ghost" type="button" data-cancel-sale-close>Cancelar</button>
    <button class="button danger" type="submit"><?= icon('x') ?> Sí, anular venta</button>
   </div>
  </form>
 </div>
</div>
