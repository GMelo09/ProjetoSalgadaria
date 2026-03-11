/* Doce & Salgado - App.js */

const Cart = {
  key: 'ds_cart',

  get() {
    try {
      return JSON.parse(localStorage.getItem(this.key)) || [];
    } catch {
      return [];
    }
  },

  save(items) {
    localStorage.setItem(this.key, JSON.stringify(items));
    this.updateBadge();
  },

  add(product) {
    const items = this.get();
    const exists = items.findIndex(item => 
      item.id === product.id && item.tipo_pacote === product.tipo_pacote
    );

    if (exists >= 0) {
      items[exists].quantidade += product.quantidade;
    } else {
      items.push(product);
    }

    this.save(items);
  },

  remove(index) {
    const items = this.get();
    items.splice(index, 1);
    this.save(items);
  },

  setQty(index, qty) {
    const items = this.get();
    if (qty < 1) {
      items.splice(index, 1);
    } else {
      items[index].quantidade = qty;
    }
    this.save(items);
  },

  total() {
    return this.get().reduce((total, item) => total + item.preco * item.quantidade, 0);
  },

  count() {
    return this.get().reduce((total, item) => total + item.quantidade, 0);
  },

  clear() {
    localStorage.removeItem(this.key);
    this.updateBadge();
  },

  updateBadge() {
    const badge = document.getElementById('cartBadge');
    if (!badge) return;

    const qtd = this.count();
    badge.textContent = qtd;
    badge.style.display = qtd > 0 ? 'flex' : 'none';
  }
};

// Formata preço
function fmtBRL(valor) {
  return 'R$ ' + Number(valor).toFixed(2).replace('.', ',');
}

// Aumenta/diminui quantidade
function changeQty(id, delta) {
  const input = document.getElementById(id);
  if (!input) return;

  let novo = parseInt(input.value) + delta;
  if (novo < 1) novo = 1;
  input.value = novo;
}

// Adicionar ao carrinho
function addToCart(id, nome, preco, inputId) {
  const input = document.getElementById(inputId);
  const quantidade = input ? parseInt(input.value) || 1 : 1;

  Cart.add({ id, nome, preco, quantidade, tipo_pacote: null });

  // volta o input pra 1
  if (input) input.value = 1;

  // toast bonitinho
  Swal.fire({
    toast: true,
    position: 'top-end',
    icon: 'success',
    title: `<strong>${nome}</strong>`,
    text: `${quantidade}x no carrinho!`,
    showConfirmButton: false,
    timer: 2500,
    timerProgressBar: true,
    customClass: { popup: 'swal-cart-toast' }
  });
}

// =============================================
// Inicialização quando a página carrega
// =============================================
document.addEventListener('DOMContentLoaded', () => {

  Cart.updateBadge();

  // Menu mobile
  const toggler = document.getElementById('navToggler');
  const navLinks = document.getElementById('navLinks');

  if (toggler && navLinks) {
    toggler.addEventListener('click', () => navLinks.classList.toggle('open'));

    // fecha menu ao clicar em qualquer link
    document.querySelectorAll('.nav-links a').forEach(link => {
      link.addEventListener('click', () => navLinks.classList.remove('open'));
    });
  }

  // Seleção de forma de pagamento
  document.querySelectorAll('.payment-option').forEach(option => {
    option.addEventListener('click', () => {
      document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
      option.classList.add('selected');

      const radio = option.querySelector('input[type="radio"]');
      if (radio) radio.checked = true;
    });
  });
});