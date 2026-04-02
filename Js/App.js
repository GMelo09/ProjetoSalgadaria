const Cart = {
  key: 'ds_cart',

  get() {
    try { return JSON.parse(localStorage.getItem(this.key)) || []; }
    catch { return []; }
  },

  save(items) {
    localStorage.setItem(this.key, JSON.stringify(items));
    this.updateBadge();
  },

  add(product) {
    const items = this.get();
    const i = items.findIndex(item => item.id === product.id && item.tipo_pacote === product.tipo_pacote);
    if (i >= 0) items[i].quantidade += product.quantidade;
    else items.push(product);
    this.save(items);
  },

  remove(index) {
    const items = this.get();
    items.splice(index, 1);
    this.save(items);
  },

  setQty(index, qty) {
    const items = this.get();
    if (qty < 1) items.splice(index, 1);
    else items[index].quantidade = qty;
    this.save(items);
  },

  total() {
    return this.get().reduce((sum, item) => sum + item.preco * item.quantidade, 0);
  },

  count() {
    return this.get().reduce((sum, item) => sum + item.quantidade, 0);
  },

  clear() {
    localStorage.removeItem(this.key);
    this.updateBadge();
  },

  updateBadge() {
    const badge = document.getElementById('cartBadge');
    if (!badge) return;
    const qty = this.count();
    badge.textContent = qty;
    badge.style.display = qty > 0 ? 'flex' : 'none';
  }
};

function fmtBRL(valor) {
  return 'R$ ' + Number(valor).toFixed(2).replace('.', ',');
}

function changeQty(id, delta) {
  const input = document.getElementById(id);
  if (!input) return;
  const novo = parseInt(input.value) + delta;
  input.value = novo < 1 ? 1 : novo;
}

function addToCart(id, nome, preco, inputId) {
  const input = document.getElementById(inputId);
  const quantidade = input ? parseInt(input.value) || 1 : 1;

  Cart.add({ id, nome, preco, quantidade, tipo_pacote: null });
  if (input) input.value = 1;

  Swal.fire({
    toast: true,
    position: 'top-end',
    icon: 'success',
    title: `<strong>${nome}</strong>`,
    text: `${quantidade}x adicionado ao carrinho`,
    showConfirmButton: false,
    timer: 2500,
    timerProgressBar: true,
  });
}

document.addEventListener('DOMContentLoaded', () => {
  Cart.updateBadge();

  const toggler = document.getElementById('navToggler');
  const navLinks = document.getElementById('navLinks');

  if (toggler && navLinks) {
    toggler.addEventListener('click', () => navLinks.classList.toggle('open'));
    document.querySelectorAll('.nav-links a').forEach(link => {
      link.addEventListener('click', () => navLinks.classList.remove('open'));
    });
  }

  document.querySelectorAll('.payment-option').forEach(option => {
    option.addEventListener('click', () => {
      document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
      option.classList.add('selected');
      option.querySelector('input[type="radio"]')?.click();
    });
  });
});