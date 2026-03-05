/* ============================================================
   Doce & Salgado — app.js
   Cart system, toasts, nav toggle, utilities
   ============================================================ */

/* ── Cart ── */
const Cart = {
  KEY: 'ds_cart',

  get() {
    try { return JSON.parse(localStorage.getItem(this.KEY)) || []; }
    catch { return []; }
  },

  save(items) {
    localStorage.setItem(this.KEY, JSON.stringify(items));
    this._updateBadge();
  },

  add(product) {
    const items = this.get();
    const idx = items.findIndex(i =>
      i.id === product.id && i.tipo_pacote === product.tipo_pacote
    );
    if (idx >= 0) {
      items[idx].quantidade += product.quantidade;
    } else {
      items.push(product);
    }
    this.save(items);
  },

  remove(idx) {
    const items = this.get();
    items.splice(idx, 1);
    this.save(items);
  },

  setQty(idx, qty) {
    const items = this.get();
    if (qty <= 0) items.splice(idx, 1);
    else items[idx].quantidade = qty;
    this.save(items);
  },

  total() {
    return this.get().reduce((s, i) => s + i.preco * i.quantidade, 0);
  },

  count() {
    return this.get().reduce((s, i) => s + i.quantidade, 0);
  },

  clear() {
    localStorage.removeItem(this.KEY);
    this._updateBadge();
  },

  _updateBadge() {
    const el = document.getElementById('cartBadge');
    if (!el) return;
    const n = this.count();
    el.textContent = n;
    el.style.display = n > 0 ? 'flex' : 'none';
  }
};

/* ── Toast ── */
const Toast = {
  show(msg, type = 'default', duration = 3000) {
    let container = document.getElementById('toastContainer');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toastContainer';
      container.className = 'toast-container';
      document.body.appendChild(container);
    }
    const icons = { success: '✅', error: '❌', warning: '⚠️', default: '🛒' };
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `<span>${icons[type] || icons.default}</span> <span>${msg}</span>`;
    container.appendChild(toast);
    setTimeout(() => {
      toast.style.transition = 'all .3s ease';
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(120%)';
      setTimeout(() => toast.remove(), 300);
    }, duration);
  }
};

/* ── Qty control ── */
function changeQty(inputId, delta) {
  const input = document.getElementById(inputId);
  if (!input) return;
  let val = parseInt(input.value) + delta;
  if (val < 1) val = 1;
  input.value = val;
}

/* ── Add to cart ── */
function addToCart(id, nome, preco, inputId) {
  const qty = parseInt(document.getElementById(inputId).value) || 1;
  Cart.add({ id, nome, preco, quantidade: qty, tipo_pacote: null });
  document.getElementById(inputId).value = 1;
  Toast.show(`${nome} adicionado ao carrinho!`, 'success');
}

/* ── Format currency ── */
function fmtBRL(v) {
  return 'R$ ' + v.toFixed(2).replace('.', ',');
}

/* ── Nav toggle ── */
document.addEventListener('DOMContentLoaded', () => {
  Cart._updateBadge();

  const toggler = document.getElementById('navToggler');
  const navLinks = document.getElementById('navLinks');
  if (toggler && navLinks) {
    toggler.addEventListener('click', () => navLinks.classList.toggle('open'));
  }

  // Close mobile nav on link click
  document.querySelectorAll('.nav-links a').forEach(a => {
    a.addEventListener('click', () => navLinks && navLinks.classList.remove('open'));
  });

  // Payment option toggle
  document.querySelectorAll('.payment-option').forEach(opt => {
    opt.addEventListener('click', () => {
      document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
      opt.classList.add('selected');
      const input = opt.querySelector('input');
      if (input) input.checked = true;
    });
  });
});