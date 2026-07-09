document.querySelectorAll('[data-toggle-sidebar]').forEach((button) => {
  button.addEventListener('click', () => {
    document.body.classList.toggle('sidebar-open');
  });
});

document.querySelectorAll('form[data-confirm]').forEach((form) => {
  form.addEventListener('submit', (event) => {
    if (!window.confirm(form.dataset.confirm || 'Confirmar accion')) {
      event.preventDefault();
    }
  });
});

document.querySelectorAll('[data-submenu-toggle]').forEach((button) => {
  const group = button.closest('.nav-group');
  button.addEventListener('click', () => {
    const isOpen = group.classList.contains('open');
    document.querySelectorAll('.nav-group.open').forEach((open) => {
      if (open !== group) open.classList.remove('open');
    });
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
