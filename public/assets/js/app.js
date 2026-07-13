document.querySelectorAll('[data-toggle-sidebar]').forEach((button) => {
  button.addEventListener('click', () => {
    document.body.classList.toggle('sidebar-open');
  });
});

// Delegado en document (no por elemento) para que siga funcionando en
// contenido inyectado despues por AJAX (ej. auto-refresh de tablas).
document.addEventListener('submit', (event) => {
  const form = event.target.closest('form[data-confirm]');
  if (form && !window.confirm(form.dataset.confirm || 'Confirmar accion')) {
    event.preventDefault();
  }
});

// Bloqueo de scroll del body: varios modales pueden estar abiertos a la vez
// (ej. el modal "Ver pedido" y, dentro de el, aprobar/voucher). Se reevalua
// tras cada apertura/cierre en vez de forzar la clase, para no desbloquear
// el scroll si todavia queda otro modal visible detras.
window.syncModalBodyScroll = function () {
  const anyOpen = Array.from(document.querySelectorAll('.modal-overlay'))
    .some((el) => getComputedStyle(el).display !== 'none');
  document.body.classList.toggle('modal-open', anyOpen);
};

// Modales genericos [data-open]/[data-close] (patron usado por los
// sub-modales de aprobar/rechazar/reenviar dentro del detalle de un pedido).
// Delegado en document: funciona igual con contenido inyectado por AJAX.
document.addEventListener('click', (event) => {
  const openBtn = event.target.closest('[data-open]');
  if (openBtn) {
    const modal = document.getElementById(openBtn.dataset.open);
    if (modal) { modal.style.display = 'flex'; window.syncModalBodyScroll(); }
    return;
  }
  const closeBtn = event.target.closest('.modal-overlay [data-close]');
  if (closeBtn) {
    const modal = closeBtn.closest('.modal-overlay');
    if (modal) { modal.style.display = 'none'; window.syncModalBodyScroll(); }
    return;
  }
  if (event.target.classList && event.target.classList.contains('modal-overlay')) {
    event.target.style.display = 'none';
    window.syncModalBodyScroll();
  }
});

document.addEventListener('submit', (event) => {
  const form = event.target.closest('[data-lock-submit]');
  if (!form) return;
  const btn = form.querySelector('[type=submit]');
  if (!btn || btn.disabled) return;
  btn.disabled = true;
  btn.setAttribute('aria-busy', 'true');
  btn.dataset.label = btn.innerHTML;
  btn.innerHTML = 'Procesando…';
});

function positionNavSubmenu(group) {
  const button = group.querySelector('[data-submenu-toggle]');
  const submenu = group.querySelector('.nav-submenu');
  if (!button || !submenu) return;
  const rect = button.getBoundingClientRect();
  submenu.style.top = Math.round(rect.top - 4) + 'px';
  submenu.style.left = Math.round(rect.right + 10) + 'px';
}

document.querySelectorAll('.nav-group').forEach((group) => {
  group.addEventListener('mouseenter', () => positionNavSubmenu(group));
});

document.querySelectorAll('[data-submenu-toggle]').forEach((button) => {
  const group = button.closest('.nav-group');
  button.addEventListener('click', () => {
    const isOpen = group.classList.contains('open');
    document.querySelectorAll('.nav-group.open').forEach((open) => {
      if (open !== group) open.classList.remove('open');
    });
    positionNavSubmenu(group);
    group.classList.toggle('open', !isOpen);
  });
});

document.addEventListener('click', (event) => {
  document.querySelectorAll('.nav-group.open').forEach((group) => {
    if (!group.contains(event.target)) {
      group.classList.remove('open');
    }
  });
});

const sidebarNavEl = document.getElementById('sidebarNav');
if (sidebarNavEl) {
  sidebarNavEl.addEventListener('scroll', () => {
    document.querySelectorAll('.nav-group.open').forEach((group) => group.classList.remove('open'));
  });
}

// Colapsar sidebar
const collapseBtn = document.getElementById('collapseBtn');
const appShell = document.getElementById('appShell');
if (collapseBtn && appShell) {
  if (localStorage.getItem('sidebar_collapsed') === '1') {
    appShell.classList.add('collapsed');
  }
  collapseBtn.addEventListener('click', () => {
    const collapsed = appShell.classList.toggle('collapsed');
    localStorage.setItem('sidebar_collapsed', collapsed ? '1' : '0');
  });
}

// Modo oscuro
const themeToggle = document.getElementById('themeToggle');
const iconDark = document.getElementById('iconDark');
const iconLight = document.getElementById('iconLight');
function applyTheme(theme) {
  document.documentElement.setAttribute('data-theme', theme);
  if (iconDark && iconLight) {
    iconDark.style.display = theme === 'dark' ? 'none' : '';
    iconLight.style.display = theme === 'dark' ? '' : 'none';
  }
}
applyTheme(localStorage.getItem('theme') === 'dark' ? 'dark' : '');
if (themeToggle) {
  themeToggle.addEventListener('click', () => {
    const next = document.documentElement.getAttribute('data-theme') === 'dark' ? '' : 'dark';
    localStorage.setItem('theme', next === 'dark' ? 'dark' : 'light');
    applyTheme(next);
  });
}

// Dropdowns (notificaciones, usuario)
document.querySelectorAll('[data-dropdown]').forEach((trigger) => {
  trigger.addEventListener('click', (event) => {
    event.stopPropagation();
    const wrapper = trigger.closest('.dropdown');
    const isOpen = wrapper.classList.contains('open');
    document.querySelectorAll('.dropdown.open').forEach((open) => open.classList.remove('open'));
    wrapper.classList.toggle('open', !isOpen);
  });
});
document.addEventListener('click', (event) => {
  document.querySelectorAll('.dropdown.open').forEach((wrapper) => {
    if (!wrapper.contains(event.target)) wrapper.classList.remove('open');
  });
});

// Buscador global
const searchTrigger = document.getElementById('searchTrigger');
const searchOverlay = document.getElementById('searchOverlay');
const searchInput = document.getElementById('searchInput');
function openSearch() {
  if (!searchOverlay) return;
  searchOverlay.classList.add('open');
  searchInput?.focus();
}
function closeSearch() {
  searchOverlay?.classList.remove('open');
}
searchTrigger?.addEventListener('click', openSearch);
searchOverlay?.addEventListener('click', (event) => {
  if (event.target === searchOverlay) closeSearch();
});
document.addEventListener('keydown', (event) => {
  if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
    event.preventDefault();
    openSearch();
  } else if (event.key === 'Escape') {
    closeSearch();
  }
});

// Visor seguro de vouchers para pedidos: las imagenes (Yape/Plin) se muestran
// contenidas dentro del recuadro con <img>; los PDF usan el iframe con scroll.
(function () {
  const modal=document.getElementById('voucherModal'),frame=document.getElementById('voucherModalFrame'),image=document.getElementById('voucherModalImage'),loader=document.getElementById('voucherModalLoader'),code=document.getElementById('voucherModalCode'),external=document.getElementById('voucherModalExternal');
  if(!modal||!frame||!image||!loader||!code||!external)return; let opener=null;
  function closeVoucher(){modal.style.display='none';window.syncModalBodyScroll();frame.removeAttribute('src');frame.hidden=true;image.removeAttribute('src');image.hidden=true;opener?.focus();}
  function openVoucher(button){
    const endpoint=button.dataset.voucherUrl;if(!endpoint)return;
    opener=button;code.textContent=button.dataset.voucherCode?'· '+button.dataset.voucherCode:'';external.href=endpoint;
    loader.hidden=false;frame.classList.remove('is-ready');image.classList.remove('is-ready');
    const isImage=(button.dataset.voucherMime||'').startsWith('image/');
    frame.hidden=isImage;image.hidden=!isImage;
    if(isImage){frame.removeAttribute('src');image.src=endpoint;}
    else{image.removeAttribute('src');frame.src=endpoint;}
    modal.style.display='flex';window.syncModalBodyScroll();modal.querySelector('[data-voucher-close]')?.focus();
  }
  frame.addEventListener('load',()=>{loader.hidden=true;frame.classList.add('is-ready');});
  image.addEventListener('load',()=>{loader.hidden=true;image.classList.add('is-ready');});
  // Si la imagen no carga (mime desconocido o error), degradar al iframe.
  image.addEventListener('error',()=>{if(image.hidden||!image.getAttribute('src'))return;image.hidden=true;image.removeAttribute('src');frame.hidden=false;frame.src=external.href;});
  // Delegado en document: funciona tambien con filas inyectadas por el
  // auto-refresh de Pedidos/Ventas sin necesidad de re-vincular listeners.
  document.addEventListener('click', event => {
    const button = event.target.closest('[data-voucher-open]');
    if (button) openVoucher(button);
  });
  modal.querySelectorAll('[data-voucher-close]').forEach(button=>button.addEventListener('click',closeVoucher));
  modal.addEventListener('click',event=>{if(event.target===modal)closeVoucher();});
  document.addEventListener('keydown',event=>{if(event.key==='Escape'&&modal.style.display!=='none')closeVoucher();});
})();

// Consulta DNI/RUC para formularios administrativos
(function () {
  const form = document.getElementById('manualSaleForm');
  const button = document.getElementById('manualIdentityLookupBtn');
  if (!form || !button) return;

  const type = document.getElementById('manualDocumentType');
  const number = document.getElementById('manualDocumentNumber');
  const name = document.getElementById('manualCustomerName');
  const status = document.getElementById('manualIdentityLookupStatus');

  function setStatus(message, state) {
    if (!status) return;
    status.textContent = message || '';
    status.dataset.state = state || '';
  }

  button.addEventListener('click', () => {
    const docType = (type?.value || 'DNI').toUpperCase();
    const docNumber = (number?.value || '').replace(/\D+/g, '');
    const expected = docType === 'RUC' ? 11 : 8;
    if (docNumber.length !== expected) {
      setStatus('Ingresa un numero de documento valido.', 'error');
      number?.focus();
      return;
    }

    const csrf = form.querySelector('input[name="_csrf"]')?.value
      || document.querySelector('meta[name="csrf-token"]')?.content
      || '';
    const body = new FormData();
    body.append('_csrf', csrf);
    body.append('document_type', docType);
    body.append('document_number', docNumber);

    button.disabled = true;
    setStatus('Consultando documento...', 'loading');
    fetch(form.dataset.identityUrl || '/identity/lookup', {
      method: 'POST',
      body,
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then((response) => response.json().then((payload) => ({ response, payload })))
      .then(({ response, payload }) => {
        if (!response.ok || !payload.ok) {
          throw new Error(payload.error || 'No se pudo consultar el documento.');
        }
        const data = payload.data || {};
        if (data.customer_name && name) name.value = data.customer_name;
        setStatus('Datos cargados. Verifica antes de guardar.', 'success');
      })
      .catch((error) => setStatus(error.message || 'No se pudo consultar el documento.', 'error'))
      .finally(() => {
        button.disabled = false;
      });
  });
})();

// Venta manual: productos dinamicos y total en vivo
(function () {
  const form = document.getElementById('manualSaleForm');
  const list = document.getElementById('saleItems');
  const template = document.getElementById('saleItemTemplate');
  const addBtn = document.getElementById('addSaleItem');
  const summary = document.getElementById('saleSummaryLines');
  const grandTotal = document.getElementById('saleGrandTotal');
  const products = Array.isArray(window.MANUAL_SALE_PRODUCTS) ? window.MANUAL_SALE_PRODUCTS : [];
  if (!form || !list || !template) return;

  const escapeHtml = (value) => String(value || '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  })[char]);

  function productById(id) {
    return products.find((product) => Number(product.id) === Number(id));
  }

  function money(value) {
    return `S/ ${(Number(value) || 0).toFixed(2)}`;
  }

  function recalc() {
    let total = 0;
    const lines = [];
    list.querySelectorAll('.manual-sale-item').forEach((row) => {
      const select = row.querySelector('.sale-product');
      const quantityInput = row.querySelector('.sale-quantity');
      const priceInput = row.querySelector('.sale-price');
      const product = productById(select.value);
      const quantity = Math.max(0, Number(quantityInput.value) || 0);
      const price = Math.max(0, Number(priceInput.value) || 0);
      const max = product && product.stock !== null ? Number(product.stock) : null;
      row.classList.toggle('has-stock-warning', max !== null && quantity > max);
      const subtotal = quantity * price;
      total += subtotal;
      row.querySelector('.sale-line-total strong').textContent = money(subtotal);
      if (product && quantity > 0) {
        lines.push(`<div><span>${escapeHtml(product.name)} x ${quantity}</span><strong>${money(subtotal)}</strong></div>`);
      }
    });
    if (summary) summary.innerHTML = lines.length ? lines.join('') : '<p class="text-muted">Agrega productos para calcular el total.</p>';
    if (grandTotal) grandTotal.textContent = money(total);
  }

  function addRow() {
    const row = template.content.firstElementChild.cloneNode(true);
    const select = row.querySelector('.sale-product');
    const price = row.querySelector('.sale-price');
    select.addEventListener('change', () => {
      const product = productById(select.value);
      price.value = product ? Number(product.price).toFixed(2) : '';
      recalc();
    });
    row.querySelectorAll('input').forEach((input) => input.addEventListener('input', recalc));
    row.querySelector('.sale-remove').addEventListener('click', () => {
      if (list.children.length > 1) {
        row.remove();
        recalc();
      }
    });
    list.appendChild(row);
    recalc();
  }

  addBtn?.addEventListener('click', addRow);
  form.addEventListener('submit', (event) => {
    const warning = list.querySelector('.manual-sale-item.has-stock-warning');
    if (warning) {
      event.preventDefault();
      alert('Una cantidad supera el stock disponible. Ajusta la venta antes de guardar.');
    }
  });
  addRow();
})();

// Inventario: ingreso masivo de stock con lineas dinamicas
(function () {
  const form = document.getElementById('bulkStockForm');
  const list = document.getElementById('bulkStockItems');
  const template = document.getElementById('bulkStockItemTemplate');
  const addBtn = document.getElementById('addBulkStockItem');
  if (!form || !list || !template) return;

  function addRow() {
    const row = template.content.firstElementChild.cloneNode(true);
    row.querySelector('.bulk-stock-remove').addEventListener('click', () => {
      if (list.children.length > 1) {
        row.remove();
      }
    });
    list.appendChild(row);
  }

  addBtn?.addEventListener('click', addRow);
  form.addEventListener('submit', (event) => {
    const rows = Array.from(list.querySelectorAll('.manual-sale-item'));
    const hasValidRow = rows.some((row) => {
      const product = row.querySelector('.bulk-stock-product');
      const quantity = row.querySelector('.bulk-stock-quantity');
      return product.value && Number(quantity.value) > 0;
    });
    if (!hasValidRow) {
      event.preventDefault();
      alert('Agrega al menos un producto con cantidad valida.');
    }
  });
  addRow();
})();

// Pedidos: badge del sidebar en vivo (toda pagina del dashboard) y
// auto-refresh de la tabla en /orders, sin necesidad de F5.
(function () {
  const pendingUrl = document.body.dataset.ordersPendingUrl;
  if (!pendingUrl) return;

  const inline = document.getElementById('ordersPendingBadgeInline');
  const badge = document.getElementById('ordersPendingBadge');
  const table = document.getElementById('ordersTable');
  const tbody = document.getElementById('ordersTableBody');
  let lastCount = badge ? parseInt(badge.textContent || '0', 10) : null;
  let refreshing = false;

  function updateBadges(count) {
    [inline, badge].forEach((el) => {
      if (!el) return;
      el.textContent = String(count);
      el.hidden = count <= 0;
    });
  }

  function refreshTable() {
    if (refreshing || !table || !tbody) return;
    refreshing = true;
    fetch(table.dataset.ordersRefreshUrl)
      .then((response) => (response.ok ? response.text() : null))
      .then((html) => { if (html !== null) tbody.innerHTML = html; })
      .catch(() => {})
      .finally(() => { refreshing = false; });
  }
  // Expuesto para que el modal "Ver pedido" pueda refrescar la fila al
  // instante despues de aprobar/rechazar, sin esperar al proximo poll.
  window.refreshOrdersTable = refreshTable;

  function poll() {
    fetch(pendingUrl)
      .then((response) => response.json())
      .then((data) => {
        const count = Number(data.count) || 0;
        updateBadges(count);
        if (lastCount !== null && count !== lastCount) {
          refreshTable();
        }
        lastCount = count;
      })
      .catch(() => {});
  }
  // Expuesto para refrescar el badge del sidebar apenas se aprueba/rechaza
  // desde el modal, sin esperar al proximo ciclo de 25s.
  window.refreshOrdersPendingBadge = poll;

  setInterval(poll, 25000);
})();

// Modal "Ver pedido" (/orders): carga el detalle por AJAX sin salir de la
// lista. Ctrl/Cmd/Shift/clic-central siguen abriendo la pagina completa en
// pestana nueva (progresivo, no rompe el enlace original).
(function () {
  const modal = document.getElementById('orderDetailModal');
  if (!modal) return;
  const loader = document.getElementById('orderDetailModalLoader');
  const body = document.getElementById('orderDetailModalBody');
  const title = document.getElementById('orderDetailModalTitle');
  if (!loader || !body || !title) return;
  let opener = null;

  function openDetail(link) {
    const url = link.dataset.orderUrl;
    if (!url) return;
    opener = link;
    title.textContent = link.dataset.orderCode ? 'Pedido ' + link.dataset.orderCode : 'Pedido';
    body.innerHTML = '';
    loader.hidden = false;
    modal.style.display = 'flex';
    window.syncModalBodyScroll();
    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then((response) => (response.ok ? response.text() : Promise.reject()))
      .then((html) => { body.innerHTML = html; loader.hidden = true; })
      .catch(() => {
        loader.hidden = true;
        body.innerHTML = '<p class="text-muted" style="padding:24px">No se pudo cargar el pedido. Intenta nuevamente.</p>';
      });
  }

  function closeDetail() {
    modal.style.display = 'none';
    window.syncModalBodyScroll();
    body.innerHTML = '';
    opener?.focus();
  }

  document.addEventListener('click', (event) => {
    const link = event.target.closest('[data-order-open]');
    if (link) {
      if (event.ctrlKey || event.metaKey || event.shiftKey || event.button === 1) return;
      event.preventDefault();
      openDetail(link);
      return;
    }
    if (event.target.closest('[data-order-close]')) closeDetail();
  });
  modal.addEventListener('click', (event) => { if (event.target === modal) closeDetail(); });
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && modal.style.display !== 'none') closeDetail(); });

  // Aprobar / rechazar / marcar en revision dentro del modal: se envian por
  // fetch para no salir de la lista; al terminar, se refresca la fila y el
  // badge del sidebar en el acto (sin esperar al proximo poll).
  document.addEventListener('submit', (event) => {
    const form = event.target.closest('#orderDetailModalBody form[data-ajax-order-action]');
    if (!form) return;
    event.preventDefault();
    const btn = form.querySelector('[type=submit]');
    body.querySelectorAll('.alert-error').forEach((el) => el.remove());

    fetch(form.action, {
      method: 'POST',
      body: new FormData(form),
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then((response) => response.json().then((data) => ({ ok: response.ok, data })))
      .then(({ ok, data }) => {
        if (ok && data.ok) {
          closeDetail();
          window.refreshOrdersTable && window.refreshOrdersTable();
          window.refreshOrdersPendingBadge && window.refreshOrdersPendingBadge();
        } else {
          const errors = (data && data.errors) || ['No se pudo procesar la accion.'];
          const alert = document.createElement('div');
          alert.className = 'alert alert-error';
          alert.style.margin = '0 0 14px';
          alert.innerHTML = errors.map((msg) => '<p>' + msg.replace(/</g, '&lt;') + '</p>').join('');
          body.prepend(alert);
        }
      })
      .catch(() => {
        const alert = document.createElement('div');
        alert.className = 'alert alert-error';
        alert.style.margin = '0 0 14px';
        alert.innerHTML = '<p>No se pudo conectar con el servidor.</p>';
        body.prepend(alert);
      })
      .finally(() => {
        if (btn) { btn.disabled = false; btn.innerHTML = btn.dataset.label || btn.innerHTML; }
      });
  });
})();

// Modal "Anular venta" (/orders): confirmacion elegante en vez del confirm()
// nativo; anula la venta (y el pedido vinculado) sin salir de la lista.
(function () {
  const modal = document.getElementById('cancelSaleModal');
  if (!modal) return;
  const form = document.getElementById('cancelSaleForm');
  const idInput = document.getElementById('cancelSaleId');
  const codeEl = document.getElementById('cancelSaleCode');
  if (!form || !idInput || !codeEl) return;
  let opener = null;

  function openCancel(button) {
    opener = button;
    idInput.value = button.dataset.cancelSaleId || '';
    codeEl.textContent = button.dataset.cancelSaleCode || '';
    form.querySelectorAll('.alert-error').forEach((el) => el.remove());
    modal.style.display = 'flex';
    window.syncModalBodyScroll();
  }

  function closeCancel() {
    modal.style.display = 'none';
    window.syncModalBodyScroll();
    opener?.focus();
  }

  document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-cancel-sale-open]');
    if (trigger) { openCancel(trigger); return; }
    if (event.target.closest('[data-cancel-sale-close]')) closeCancel();
  });
  modal.addEventListener('click', (event) => { if (event.target === modal) closeCancel(); });
  document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && modal.style.display !== 'none') closeCancel(); });

  form.addEventListener('submit', (event) => {
    event.preventDefault();
    const btn = form.querySelector('[type=submit]');
    form.querySelectorAll('.alert-error').forEach((el) => el.remove());
    if (btn) { btn.disabled = true; btn.dataset.label = btn.innerHTML; btn.innerHTML = 'Anulando…'; }

    fetch(form.action, {
      method: 'POST',
      body: new FormData(form),
      headers: { 'X-Requested-With': 'XMLHttpRequest' },
    })
      .then((response) => response.json().then((data) => ({ ok: response.ok, data })))
      .then(({ ok, data }) => {
        if (ok && data.ok) {
          closeCancel();
          window.refreshOrdersTable && window.refreshOrdersTable();
          window.refreshOrdersPendingBadge && window.refreshOrdersPendingBadge();
        } else {
          const errors = (data && data.errors) || ['No se pudo anular la venta.'];
          const alert = document.createElement('div');
          alert.className = 'alert alert-error';
          alert.style.margin = '0 0 14px';
          alert.innerHTML = errors.map((msg) => '<p>' + msg.replace(/</g, '&lt;') + '</p>').join('');
          form.querySelector('.modal-body').prepend(alert);
        }
      })
      .catch(() => {
        const alert = document.createElement('div');
        alert.className = 'alert alert-error';
        alert.style.margin = '0 0 14px';
        alert.innerHTML = '<p>No se pudo conectar con el servidor.</p>';
        form.querySelector('.modal-body').prepend(alert);
      })
      .finally(() => {
        if (btn) { btn.disabled = false; btn.innerHTML = btn.dataset.label || btn.innerHTML; }
      });
  });
})();
