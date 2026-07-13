const hasGsap = typeof window.gsap !== 'undefined';

// Enlace de WhatsApp segun dispositivo (mismo criterio que whatsapp_link() en PHP):
// celular -> wa.me (app instalada) / escritorio -> WhatsApp Web directo al chat.
window.lpWhatsAppLink = function (phone, text) {
  const isMobile = /android|iphone|ipad|ipod|windows phone|opera mini|mobile/i.test(navigator.userAgent);
  const digits = String(phone || '').replace(/\D+/g, '');
  const encoded = text ? encodeURIComponent(text) : '';
  if (isMobile) {
    if (!digits) return 'https://api.whatsapp.com/send' + (encoded ? '?text=' + encoded : '');
    return 'https://wa.me/' + digits + (encoded ? '?text=' + encoded : '');
  }
  const params = [];
  if (digits) params.push('phone=' + digits);
  if (encoded) params.push('text=' + encoded);
  return 'https://web.whatsapp.com/send' + (params.length ? '?' + params.join('&') : '');
};

function onScroll() {
  document.body.classList.toggle('lp-scrolled', window.scrollY > 40);
}
document.addEventListener('scroll', onScroll);
onScroll();

const lpBurger = document.getElementById('lpBurger');
const lpMenu = document.getElementById('lpMenu');
lpBurger?.addEventListener('click', () => {
  const isOpen = lpMenu?.classList.toggle('open') || false;
  lpBurger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
});
lpMenu?.querySelectorAll('a').forEach((a) => a.addEventListener('click', () => {
  lpMenu.querySelectorAll('.lp-nav-dropdown').forEach((dropdown) => dropdown.classList.remove('is-active'));
  if (a.classList.contains('lp-nav-link')) {
    lpMenu.querySelectorAll('.lp-nav-link').forEach((link) => link.classList.remove('is-active'));
    a.classList.add('is-active');
  } else if (a.classList.contains('lp-submenu-link')) {
    lpMenu.querySelectorAll('.lp-nav-link').forEach((link) => link.classList.remove('is-active'));
    const dropdown = a.closest('.lp-nav-dropdown');
    dropdown?.classList.add('is-active');
    dropdown?.querySelector('.lp-nav-link')?.classList.add('is-active');
  }
  lpMenu.classList.remove('open');
  lpBurger?.setAttribute('aria-expanded', 'false');
}));

document.querySelectorAll('[data-lang-switch]').forEach((switcher) => {
  const knob = switcher.querySelector('[data-lang-knob]');
  if (!knob) return;

  let dragging = false;
  let moved = false;
  let startX = 0;
  let startPosition = 0;

  function maxMove() {
    return Math.max(0, switcher.clientWidth - knob.clientWidth - 10);
  }

  function setDragPosition(clientX) {
    const delta = clientX - startX;
    const next = Math.max(0, Math.min(maxMove(), startPosition + delta));
    knob.style.transform = `translateX(${next}px)`;
    moved = moved || Math.abs(delta) > 4;
    return next;
  }

  knob.addEventListener('pointerdown', (event) => {
    dragging = true;
    moved = false;
    startX = event.clientX;
    startPosition = switcher.dataset.current === 'en' ? maxMove() : 0;
    switcher.classList.add('is-dragging');
    knob.setPointerCapture?.(event.pointerId);
  });

  knob.addEventListener('pointermove', (event) => {
    if (!dragging) return;
    event.preventDefault();
    setDragPosition(event.clientX);
  });

  function finishDrag(event) {
    if (!dragging) return;
    const position = setDragPosition(event.clientX);
    dragging = false;
    switcher.classList.remove('is-dragging');
    knob.style.transform = '';

    if (!moved) return;
    const target = position > maxMove() / 2 ? 'en' : 'es';
    if (target !== switcher.dataset.current) {
      window.location.href = switcher.dataset[target === 'en' ? 'urlEn' : 'urlEs'];
    }
  }

  knob.addEventListener('pointerup', finishDrag);
  knob.addEventListener('pointercancel', finishDrag);

  switcher.querySelectorAll('a').forEach((link) => {
    link.addEventListener('click', (event) => {
      if (moved) {
        event.preventDefault();
      }
    });
  });
});

// Hero slideshow (crossfade imagen + texto sincronizados)
const heroSlides = document.querySelectorAll('#lpHeroSlides .lp-hero-slide');
const heroTexts = document.querySelectorAll('#lpHeroTexts .lp-hero-text');
let heroCurrent = 0;
function goToHeroSlide(index) {
  if (heroSlides.length < 2) return;
  heroSlides[heroCurrent].classList.remove('is-active');
  heroTexts[heroCurrent]?.classList.remove('is-active');
  heroCurrent = index % heroSlides.length;
  heroSlides[heroCurrent].classList.add('is-active');
  const nextText = heroTexts[heroCurrent];
  if (nextText) {
    nextText.classList.add('is-active');
  }
}
function nextHeroSlide() {
  goToHeroSlide(heroCurrent + 1);
}
let heroTimer = heroSlides.length > 1 ? setInterval(nextHeroSlide, 6000) : null;

// Cuando el usuario regresa a la pestaña, forzar visibilidad del texto activo
document.addEventListener('visibilitychange', () => {
  if (document.visibilityState === 'visible') {
    heroTexts.forEach((t, i) => {
      if (hasGsap) gsap.set(t.children, { clearProps: 'all' });
      t.classList.toggle('is-active', i === heroCurrent);
    });
  }
});
document.getElementById('lpHeroNext')?.addEventListener('click', () => {
  nextHeroSlide();
  if (heroTimer) { clearInterval(heroTimer); heroTimer = setInterval(nextHeroSlide, 6000); }
});

if (hasGsap) {
  gsap.registerPlugin(ScrollTrigger);

  // Entrada del primer texto del hero
  const firstHeroText = document.querySelector('#lpHeroTexts .lp-hero-text.is-active');
  if (firstHeroText) {
    gsap.from(firstHeroText.children, { y: 40, opacity: 0, duration: 1, stagger: 0.15, ease: 'power3.out' });
  }

  // Parallax del hero al hacer scroll
  gsap.to('#lpHeroSlides', {
    yPercent: 18,
    ease: 'none',
    scrollTrigger: { trigger: '#lpHero', start: 'top top', end: 'bottom top', scrub: true },
  });

  // Reveal de secciones
  document.querySelectorAll('.reveal').forEach((el) => {
    gsap.fromTo(el, { y: 30, opacity: 0 }, {
      y: 0, opacity: 1, duration: 0.8, ease: 'power2.out',
      scrollTrigger: { trigger: el, start: 'top 85%' },
    });
  });
} else {
  const revealEls = document.querySelectorAll('.reveal');
  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        observer.unobserve(entry.target);
      }
    });
  }, { threshold: 0.15 });
  revealEls.forEach((el) => observer.observe(el));
}

/* ═══════════════════════════════════════════════════════
   SEARCH — Buscador global del landing
   ═══════════════════════════════════════════════════════ */
(function () {
  const i18n = window.LANDING_I18N || {};
  const overlay = document.getElementById('lpSearchOverlay');
  const input = document.getElementById('lpSearchInput');
  const results = document.getElementById('lpSearchResults');
  const trigger = document.getElementById('searchTriggerLp');
  const kbd = document.querySelector('.lp-search-kbd');
  let debounceTimer;

  if (!overlay || !input || !results) return;

  function openSearch() {
    overlay.classList.add('is-open');
    overlay.setAttribute('aria-hidden', 'false');
    input.value = '';
    input.focus();
    showEmpty();
  }

  function closeSearch() {
    overlay.classList.remove('is-open');
    overlay.setAttribute('aria-hidden', 'true');
  }

  function showEmpty() {
    results.innerHTML = `<div class="lp-search-empty">
      <div class="lp-search-empty-icon"><svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></div>
      <strong>${i18n.searchEmptyTitle || 'Empieza a escribir para buscar'}</strong>
      <p>${i18n.searchEmptyText || 'Encuentra productos de cacao, servicios y publicaciones de COOPAECA.'}</p>
    </div>`;
  }

  function showLoading() {
    results.innerHTML = `<div class="lp-search-empty"><p>${i18n.searchLoading || 'Buscando...'}</p></div>`;
  }

  function showNoResults(q) {
    const title = (i18n.searchNoResults || 'Sin resultados para ":query"').replace(':query', q);
    results.innerHTML = `<div class="lp-search-empty">
      <strong>${title}</strong>
      <p>${i18n.searchNoResultsText || 'Intenta con otro termino o navega por las secciones del sitio.'}</p>
    </div>`;
  }

  function renderItems(items) {
    results.innerHTML = items.map((item) => {
      const thumb = item.cover
        ? `<img src="${item.cover}" alt="${item.name}" loading="lazy">`
        : `<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="m21 15-5-5L5 21"/></svg>`;
      const price = item.price ? `<span class="lp-search-price">S/ ${item.price}</span>` : '';
      return `<a href="${item.url}" class="lp-search-item">
        <div class="lp-search-thumb">${thumb}</div>
        <div class="lp-search-info">
          <span class="lp-search-name">${item.name}</span>
          <span class="lp-search-desc">${item.excerpt || ''}</span>
        </div>
        ${price}
        <span class="lp-search-badge ${item.type}">${item.type_label}</span>
      </a>`;
    }).join('');
  }

  function doSearch(q) {
    if (q.length < 2) { showEmpty(); return; }
    showLoading();
    const lang = i18n.lang ? `&lang=${encodeURIComponent(i18n.lang)}` : '';
    fetch(`/buscar?q=${encodeURIComponent(q)}${lang}`)
      .then((r) => r.json())
      .then((data) => {
        if (data.items && data.items.length > 0) {
          renderItems(data.items);
        } else {
          showNoResults(q);
        }
      })
      .catch(() => showNoResults(q));
  }

  // Open
  trigger?.addEventListener('click', openSearch);

  // Close on overlay click
  overlay.addEventListener('click', function (e) { if (e.target === overlay) closeSearch(); });

  // Close on ESC
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && overlay.classList.contains('is-open')) closeSearch();
    // Cmd/Ctrl + K shortcut
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') { e.preventDefault(); openSearch(); }
  });

  // Close on kbd click
  kbd?.addEventListener('click', closeSearch);

  // Search on input
  input.addEventListener('input', function () {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => doSearch(this.value.trim()), 250);
  });

  // Navigate to first result on Enter
  input.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      const firstLink = results.querySelector('.lp-search-item');
      if (firstLink) firstLink.click();
    }
  });
})();

/* Ecommerce cart and checkout */
(function () {
  const KEY = 'ccopaeca_cart_v1';
  const i18n = window.LANDING_I18N || {};
  const isEn = i18n.lang === 'en';
  const money = (value) => `S/ ${(Number(value) || 0).toFixed(2)}`;
  const esc = (value) => String(value || '').replace(/[&<>"']/g, (char) => ({
    '&': '&amp;',
    '<': '&lt;',
    '>': '&gt;',
    '"': '&quot;',
    "'": '&#039;',
  }[char]));
  const text = {
    cart: isEn ? 'Cart' : 'Carrito',
    empty: isEn ? 'Your cart is empty' : 'Tu carrito esta vacio',
    checkout: isEn ? 'Checkout' : 'Finalizar compra',
    added: isEn ? 'Product added to cart' : 'Producto agregado al carrito',
    whatsappTitle: isEn ? 'WhatsApp is for coordination' : 'WhatsApp es para coordinacion',
    whatsappBody: isEn
      ? 'To register the purchase, complete the cart and upload the payment voucher. Then send the order summary by WhatsApp.'
      : 'Para registrar la compra, completa el carrito y adjunta el voucher de pago. Luego envia el resumen del pedido por WhatsApp.',
    continueShopping: isEn ? 'Continue shopping' : 'Seguir comprando',
    lookupIdle: isEn ? 'Search DNI/RUC data' : 'Consulta datos DNI/RUC',
    lookupLoading: isEn ? 'Searching document...' : 'Consultando documento...',
    lookupSuccess: isEn ? 'Data loaded. Please verify it.' : 'Datos cargados. Verificalos antes de continuar.',
    lookupMissing: isEn ? 'Enter a valid document number.' : 'Ingresa un numero de documento valido.',
    lookupError: isEn ? 'Document could not be checked.' : 'No se pudo consultar el documento.',
    total: 'Total',
  };

  function readCart() {
    try {
      const data = JSON.parse(localStorage.getItem(KEY) || '[]');
      return Array.isArray(data) ? data : [];
    } catch (e) {
      return [];
    }
  }

  function saveCart(cart) {
    localStorage.setItem(KEY, JSON.stringify(cart));
    renderCart();
    renderCheckout();
  }

  function cartTotal(cart) {
    return cart.reduce((sum, item) => sum + ((Number(item.price) || 0) * (Number(item.quantity) || 0)), 0);
  }

  function addToCart(product, quantity) {
    const id = Number(product.id);
    if (!id) return;
    const cart = readCart();
    const max = product.stock === null || product.stock === '' ? null : Number(product.stock);
    const found = cart.find((item) => Number(item.id) === id);
    const qty = Math.max(1, Number(quantity) || 1);
    if (found) {
      found.quantity += qty;
      if (max !== null) found.quantity = Math.min(found.quantity, max);
    } else {
      cart.push({
        id,
        product_id: id,
        name: product.name || '',
        presentation: product.presentation || '',
        price: Number(product.price) || 0,
        price_label: product.price_label || (Number(product.price) || 0).toFixed(2),
        stock: product.stock,
        image: product.image || '',
        url: product.url || '',
        phone: product.phone || '',
        quantity: max !== null ? Math.min(qty, max) : qty,
      });
    }
    saveCart(cart.filter((item) => item.quantity > 0));
    showToast(text.added);
  }

  function ensureCartUi() {
    if (document.getElementById('lpCartDrawer')) return;
    const root = document.createElement('div');
    root.innerHTML = `
      <button type="button" class="lp-cart-float" id="lpCartFloat" aria-label="${text.cart}">
        <span class="lp-cart-float-icon">Cart</span><span class="lp-cart-count" id="lpCartCount">0</span>
      </button>
      <div class="lp-cart-overlay" id="lpCartOverlay" aria-hidden="true">
        <aside class="lp-cart-drawer" id="lpCartDrawer">
          <div class="lp-cart-head">
            <div><span>${text.whatsappTitle}</span><h3>${text.cart}</h3></div>
            <button type="button" class="lp-cart-close" id="lpCartClose">x</button>
          </div>
          <div class="lp-cart-note" id="lpCartNote">${text.whatsappBody}</div>
          <div class="lp-cart-items" id="lpCartItems"></div>
          <div class="lp-cart-footer">
            <div class="lp-cart-total"><span>${text.total}</span><strong id="lpCartTotal">S/ 0.00</strong></div>
            <a class="lp-btn lp-btn-primary" href="/checkout${i18n.lang ? `?lang=${encodeURIComponent(i18n.lang)}` : ''}">${text.checkout}</a>
            <button type="button" class="checkout-nav-btn" id="lpCartContinue">${text.continueShopping}</button>
          </div>
        </aside>
      </div>
      <div class="lp-toast" id="lpToast" aria-live="polite"></div>`;
    document.body.appendChild(root);
    document.getElementById('lpCartFloat')?.addEventListener('click', () => openCart(false));
    document.getElementById('lpCartOverlay')?.addEventListener('click', (event) => {
      if (event.target.id === 'lpCartOverlay') closeCart();
    });
    document.getElementById('lpCartClose')?.addEventListener('click', closeCart);
    document.getElementById('lpCartContinue')?.addEventListener('click', closeCart);
  }

  function openCart(showNote) {
    ensureCartUi();
    document.getElementById('lpCartNote')?.classList.toggle('is-visible', !!showNote);
    document.getElementById('lpCartOverlay')?.classList.add('is-open');
    document.getElementById('lpCartOverlay')?.setAttribute('aria-hidden', 'false');
  }

  function closeCart() {
    document.getElementById('lpCartOverlay')?.classList.remove('is-open');
    document.getElementById('lpCartOverlay')?.setAttribute('aria-hidden', 'true');
  }

  function changeQty(id, delta) {
    const cart = readCart().map((item) => {
      if (Number(item.id) !== Number(id)) return item;
      const max = item.stock === null || item.stock === '' ? null : Number(item.stock);
      const next = Math.max(0, Number(item.quantity) + delta);
      item.quantity = max !== null ? Math.min(next, max) : next;
      return item;
    }).filter((item) => item.quantity > 0);
    saveCart(cart);
  }

  function renderCart() {
    ensureCartUi();
    const cart = readCart();
    const count = cart.reduce((sum, item) => sum + (Number(item.quantity) || 0), 0);
    const countEl = document.getElementById('lpCartCount');
    if (countEl) countEl.textContent = String(count);
    document.body.classList.toggle('has-cart-items', count > 0);
    const list = document.getElementById('lpCartItems');
    if (!list) return;
    if (!cart.length) {
      list.innerHTML = `<div class="lp-cart-empty">${text.empty}</div>`;
    } else {
      list.innerHTML = cart.map((item) => `
        <div class="lp-cart-item">
          <div class="lp-cart-thumb">${item.image ? `<img src="${esc(item.image)}" alt="">` : 'CO'}</div>
          <div class="lp-cart-info">
            <strong>${esc(item.name)}</strong>
            <span>${esc(item.presentation || '')}</span>
            <small>${money(item.price)}</small>
          </div>
          <div class="lp-cart-qty">
            <button type="button" data-cart-dec="${item.id}">-</button>
            <span>${item.quantity}</span>
            <button type="button" data-cart-inc="${item.id}">+</button>
          </div>
        </div>`).join('');
    }
    const totalEl = document.getElementById('lpCartTotal');
    if (totalEl) totalEl.textContent = money(cartTotal(cart));
    list.querySelectorAll('[data-cart-dec]').forEach((btn) => btn.addEventListener('click', () => changeQty(btn.dataset.cartDec, -1)));
    list.querySelectorAll('[data-cart-inc]').forEach((btn) => btn.addEventListener('click', () => changeQty(btn.dataset.cartInc, 1)));
  }

  function showToast(message) {
    const toast = document.getElementById('lpToast');
    if (!toast) return;
    toast.textContent = message;
    toast.classList.add('is-visible');
    setTimeout(() => toast.classList.remove('is-visible'), 2200);
  }

  function selectedProduct(button) {
    try {
      return JSON.parse(button.getAttribute('data-product') || '{}');
    } catch (e) {
      return {};
    }
  }

  function currentQuantity() {
    const input = document.getElementById('pdQuantity');
    if (!input) return 1;
    let qty = parseInt(input.value, 10) || 1;
    if (input.max) qty = Math.min(qty, parseInt(input.max, 10));
    return Math.max(1, qty);
  }

  document.getElementById('pdAddCartBtn')?.addEventListener('click', function () {
    addToCart(selectedProduct(this), currentQuantity());
    openCart(false);
  });

  document.getElementById('pdWhatsappAssistBtn')?.addEventListener('click', function () {
    const product = selectedProduct(this);
    if (!(product.stock !== null && Number(product.stock) === 0)) {
      addToCart(product, currentQuantity());
    }
    openCart(true);
  });
  document.querySelectorAll('.js-whatsapp-assist').forEach((button) => {
    button.addEventListener('click', function () {
      const product = selectedProduct(this);
      if (!(product.stock !== null && Number(product.stock) === 0)) {
        addToCart(product, currentQuantity());
      }
      openCart(true);
    });
  });

  function renderCheckout() {
    const hidden = document.getElementById('checkoutItems');
    if (!hidden) return;
    const cart = readCart();
    hidden.value = JSON.stringify(cart.map((item) => ({ product_id: item.id, quantity: item.quantity })));
    const list = document.getElementById('checkoutCartList');
    const summary = document.getElementById('checkoutSummaryItems');
    const empty = document.getElementById('checkoutEmpty');
    const totalEl = document.getElementById('checkoutTotal');
    if (totalEl) totalEl.textContent = money(cartTotal(cart));
    if (empty) empty.style.display = cart.length ? 'none' : 'grid';
    if (list) {
      list.innerHTML = cart.map((item) => `
        <div class="checkout-cart-item">
          <div>${item.image ? `<img src="${esc(item.image)}" alt="">` : ''}</div>
          <section><strong>${esc(item.name)}</strong><span>${esc(item.presentation || '')}</span><small>${money(item.price)} x ${Number(item.quantity) || 0}</small></section>
          <b>${money((Number(item.price) || 0) * (Number(item.quantity) || 0))}</b>
        </div>`).join('');
    }
    if (summary) {
      summary.innerHTML = cart.length ? cart.map((item) => `<div><span>${esc(item.name)} x ${Number(item.quantity) || 0}</span><strong>${money((Number(item.price) || 0) * item.quantity)}</strong></div>`).join('') : `<p>${text.empty}</p>`;
    }
  }

  function setupCheckoutSteps() {
    const form = document.getElementById('checkoutForm');
    if (!form) return;
    let step = 1;
    const maxStep = 4;
    const next = document.getElementById('checkoutNext');
    const prev = document.getElementById('checkoutPrev');
    const confirm = document.getElementById('checkoutConfirm');
    const modal = document.getElementById('checkoutModal');

    function paint() {
      document.querySelectorAll('[data-checkout-panel]').forEach((panel) => panel.classList.toggle('is-current', Number(panel.dataset.checkoutPanel) === step));
      document.querySelectorAll('.checkout-step').forEach((item, index) => item.classList.toggle('is-active', index < step));
      if (prev) prev.disabled = step === 1;
      next?.classList.toggle('is-hidden', step === maxStep);
      confirm?.classList.toggle('is-hidden', step !== maxStep);
    }

    function validCurrent() {
      if (!readCart().length) {
        openCart(false);
        return false;
      }
      const panel = document.querySelector(`[data-checkout-panel="${step}"]`);
      const fields = panel ? Array.from(panel.querySelectorAll('input, select, textarea')) : [];
      return fields.every((field) => field.reportValidity());
    }

    next?.addEventListener('click', () => {
      if (!validCurrent()) return;
      step = Math.min(maxStep, step + 1);
      paint();
      window.scrollTo({ top: form.offsetTop - 120, behavior: 'smooth' });
    });
    prev?.addEventListener('click', () => {
      step = Math.max(1, step - 1);
      paint();
    });
    confirm?.addEventListener('click', () => {
      if (!validCurrent()) return;
      const data = new FormData(form);
      const cart = readCart();
      const review = document.getElementById('checkoutReview');
      if (review) {
        review.innerHTML = `
          <div><span>Cliente</span><strong>${esc(data.get('customer_name') || '')}</strong></div>
          <div><span>Documento</span><strong>${esc(data.get('document_type'))} ${esc(data.get('document_number'))}</strong></div>
          <div><span>WhatsApp</span><strong>${esc(data.get('whatsapp') || '')}</strong></div>
          <div><span>Direccion</span><strong>${esc(data.get('address') || '')}${data.get('address_reference') ? ' (' + esc(data.get('address_reference')) + ')' : ''}</strong></div>
          <div><span>Ubicacion</span><strong>${esc(data.get('region') || '')}, ${esc(data.get('province') || '')}, ${esc(data.get('district') || '')}</strong></div>
          <div><span>Pago</span><strong>${esc(data.get('payment_method') || '')} / ${esc(data.get('payment_operation_number') || '')}</strong></div>
          <div><span>${text.total}</span><strong>${money(cartTotal(cart))}</strong></div>
          <div class="span-full"><span>Productos</span><strong>${cart.map((item) => `${esc(item.name)} x ${Number(item.quantity) || 0}`).join('<br>')}</strong></div>`;
      }
      modal?.classList.add('is-open');
      modal?.setAttribute('aria-hidden', 'false');
    });
    document.getElementById('checkoutModalClose')?.addEventListener('click', () => modal?.classList.remove('is-open'));
    document.getElementById('checkoutModalBack')?.addEventListener('click', () => modal?.classList.remove('is-open'));
    form.addEventListener('submit', () => {
      document.getElementById('checkoutSubmit')?.setAttribute('disabled', 'disabled');
    });
    paint();
  }

  function setupUbigeoSelects() {
    const root = document.querySelector('[data-ubigeo-root]');
    if (!root) return null;

    const region = document.getElementById('checkoutRegion');
    const province = document.getElementById('checkoutProvince');
    const district = document.getElementById('checkoutDistrict');
    if (!(region instanceof HTMLSelectElement) || !(province instanceof HTMLSelectElement) || !(district instanceof HTMLSelectElement)) return null;

    const normalize = (value) => String(value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
    const placeholder = (select, textValue) => {
      select.innerHTML = `<option value="">${textValue}</option>`;
      select.disabled = true;
    };
    const selectedCode = (select) => select.selectedOptions[0]?.dataset.code || '';
    const selectByName = (select, name) => {
      const target = normalize(name);
      if (!target) return false;
      const option = Array.from(select.options).find((item) => normalize(item.value) === target || normalize(item.textContent) === target);
      if (!option) return false;
      select.value = option.value;
      return true;
    };

    function fill(select, items, emptyText, oldValue) {
      select.innerHTML = `<option value="">${emptyText}</option>` + items.map((item) => (
        `<option value="${esc(item.name)}" data-code="${esc(item.code)}">${esc(item.name)}</option>`
      )).join('');
      select.disabled = false;
      if (oldValue) selectByName(select, oldValue);
    }

    function loadProvinces(oldProvince, oldDistrict) {
      const code = selectedCode(region);
      placeholder(province, isEn ? 'Select province' : 'Selecciona provincia');
      placeholder(district, isEn ? 'Select district' : 'Selecciona distrito');
      if (!code) return Promise.resolve(false);
      return fetch(`${root.dataset.provincesUrl}?department_code=${encodeURIComponent(code)}`)
        .then((response) => response.json())
        .then((payload) => {
          fill(province, payload.items || [], isEn ? 'Select province' : 'Selecciona provincia', oldProvince);
          return oldDistrict ? loadDistricts(oldDistrict) : true;
        })
        .catch(() => false);
    }

    function loadDistricts(oldDistrict) {
      const code = selectedCode(province);
      placeholder(district, isEn ? 'Select district' : 'Selecciona distrito');
      if (!code) return Promise.resolve(false);
      return fetch(`${root.dataset.districtsUrl}?province_code=${encodeURIComponent(code)}`)
        .then((response) => response.json())
        .then((payload) => {
          fill(district, payload.items || [], isEn ? 'Select district' : 'Selecciona distrito', oldDistrict);
          return true;
        })
        .catch(() => false);
    }

    region.addEventListener('change', () => loadProvinces('', ''));
    province.addEventListener('change', () => loadDistricts(''));

    if (region.value || region.dataset.oldValue) {
      if (!region.value && region.dataset.oldValue) selectByName(region, region.dataset.oldValue);
      loadProvinces(province.dataset.oldValue || '', district.dataset.oldValue || '');
    }

    return {
      apply(data) {
        const selectedRegion = selectByName(region, data.region || '');
        if (!selectedRegion) return;
        loadProvinces(data.province || '', data.district || '');
      },
    };
  }

  function setupIdentityLookup() {
    const form = document.getElementById('checkoutForm');
    const button = document.getElementById('identityLookupBtn');
    if (!form || !button) return;

    const type = document.getElementById('documentType');
    const number = document.getElementById('documentNumber');
    const name = document.getElementById('customerName');
    const status = document.getElementById('identityLookupStatus');
    const region = document.getElementById('checkoutRegion');
    const province = document.getElementById('checkoutProvince');
    const district = document.getElementById('checkoutDistrict');
    const address = document.getElementById('checkoutAddress');
    const ubigeo = setupUbigeoSelects();

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
        setStatus(text.lookupMissing, 'error');
        number?.focus();
        return;
      }

      const csrf = form.querySelector('input[name="_csrf"]')?.value || '';
      const body = new FormData();
      body.append('_csrf', csrf);
      body.append('document_type', docType);
      body.append('document_number', docNumber);

      button.disabled = true;
      setStatus(text.lookupLoading, 'loading');
      fetch(form.dataset.identityUrl || '/identity/lookup', {
        method: 'POST',
        body,
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
      })
        .then((response) => response.json().then((payload) => ({ response, payload })))
        .then(({ response, payload }) => {
          if (!response.ok || !payload.ok) {
            throw new Error(payload.error || text.lookupError);
          }
          const data = payload.data || {};
          if (data.customer_name && name) name.value = data.customer_name;
          if (data.region || data.province || data.district) {
            if (ubigeo) {
              ubigeo.apply(data);
            } else {
              if (data.region && region) region.value = data.region;
              if (data.province && province) province.value = data.province;
              if (data.district && district) district.value = data.district;
            }
          }
          if (data.address && address && !address.value.trim()) address.value = data.address;
          setStatus(text.lookupSuccess, 'success');
        })
        .catch((error) => setStatus(error.message || text.lookupError, 'error'))
        .finally(() => {
          button.disabled = false;
        });
    });

    type?.addEventListener('change', () => setStatus(text.lookupIdle, ''));
  }

  if (document.querySelector('[data-clear-cart]')) {
    localStorage.removeItem(KEY);
  }
  ensureCartUi();
  renderCart();
  renderCheckout();
  setupCheckoutSteps();
  setupIdentityLookup();
})();
