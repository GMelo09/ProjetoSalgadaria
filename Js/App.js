/* ============================================================
   Doce & Salgado — app.js
   Carrinho, toasts, navegação e utilitários
   ============================================================ */

/* ── Carrinho ── */
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
    const idx   = items.findIndex(i =>
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
    else          items[idx].quantidade = qty;
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
    el.textContent    = n;
    el.style.display  = n > 0 ? 'flex' : 'none';
  }
};

/* ── Formata moeda BRL ── */
function fmtBRL(v) {
  return 'R$ ' + Number(v).toFixed(2).replace('.', ',');
}

/* ── Controle de quantidade ── */
function changeQty(inputId, delta) {
  const input = document.getElementById(inputId);
  if (!input) return;
  let val = parseInt(input.value) + delta;
  if (val < 1) val = 1;
  input.value = val;
}

/* ── Adicionar ao carrinho ── */
function addToCart(id, nome, preco, inputId) {
  const qty = parseInt(document.getElementById(inputId)?.value) || 1;
  Cart.add({ id, nome, preco, quantidade: qty, tipo_pacote: null });
  document.getElementById(inputId).value = 1;

  Swal.fire({
    toast: true,
    position: 'top-end',
    icon: 'success',
    title: `<strong>${nome}</strong>`,
    text: `${qty}x adicionado ao carrinho!`,
    showConfirmButton: false,
    timer: 2500,
    timerProgressBar: true,
    showClass: { popup: 'swal2-show' },
    hideClass: { popup: 'swal2-hide' },
    customClass: { popup: 'swal-cart-toast' }
  });
}

/* ── Inicialização ── */
document.addEventListener('DOMContentLoaded', () => {
  Cart._updateBadge();

  /* Mobile nav toggle */
  const toggler  = document.getElementById('navToggler');
  const navLinks = document.getElementById('navLinks');
  if (toggler && navLinks) {
    toggler.addEventListener('click', () => navLinks.classList.toggle('open'));
  }

  /* Fecha menu mobile ao clicar em link */
  document.querySelectorAll('.nav-links a').forEach(a => {
    a.addEventListener('click', () => navLinks?.classList.remove('open'));
  });

  /* Toggle visual de pagamento */
  document.querySelectorAll('.payment-option').forEach(opt => {
    opt.addEventListener('click', () => {
      document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
      opt.classList.add('selected');
      const input = opt.querySelector('input');
      if (input) input.checked = true;
    });
  });
});