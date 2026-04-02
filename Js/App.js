/* ============================================================
   APP.JS — LÓGICA GLOBAL DO FRONT-END
   Organização:
   1. Cart               — gerenciamento do carrinho (todos os arquivos)
   2. Utilitários        — fmtBRL, changeQty, addToCart (salgados, doces)
   3. PackageModal       — modal de montagem de pacotes (pacotes.php)
   4. Aliases globais    — openPackageModal, closeModal (pacotes.php)
   5. DOMContentLoaded   — inicialização (todos os arquivos)
============================================================ */


/* ============================================================
   1. CART — Gerenciamento do carrinho no localStorage
   Usado em: salgados.php, doces.php, pacotes.php,
             carrinho.php, checkout.php, pedido_confirmado.php, login.php
============================================================ */
const Cart = {
  /* Chave de acesso ao localStorage */
  key: 'ds_cart',

  /* Lê e retorna o array de itens; retorna [] se vazio ou inválido
     Usado em: carrinho.php → renderCart()
               checkout.php → montar resumo do pedido
               pacotes.php  → PackageModal.addToCart() */
  get() {
    try { return JSON.parse(localStorage.getItem(this.key)) || []; }
    catch { return []; }
  },

  /* Persiste o array no localStorage e sincroniza o badge do navbar
     Chamado internamente por: add(), remove(), setQty(), clear() */
  save(items) {
    localStorage.setItem(this.key, JSON.stringify(items));
    this.updateBadge();
  },

  /* Insere um produto; se já existir (mesmo id + tipo_pacote), soma a quantidade
     Usado em: salgados.php → addToCart()
               doces.php    → addToCart()
               pacotes.php  → PackageModal.addToCart() */
  add(product) {
    const items = this.get();
    const i = items.findIndex(item => item.id === product.id && item.tipo_pacote === product.tipo_pacote);
    if (i >= 0) items[i].quantidade += product.quantidade;
    else items.push(product);
    this.save(items);
  },

  /* Remove um item pelo índice
     Usado em: carrinho.php → cartRemove(idx) */
  remove(index) {
    const items = this.get();
    items.splice(index, 1);
    this.save(items);
  },

  /* Altera a quantidade de um item; remove-o automaticamente se qty < 1
     Usado em: carrinho.php → cartChangeQty(idx, delta) */
  setQty(index, qty) {
    const items = this.get();
    if (qty < 1) items.splice(index, 1);
    else items[index].quantidade = qty;
    this.save(items);
  },

  /* Retorna o valor total do carrinho (preço × quantidade de cada item)
     Usado em: carrinho.php → #summaryTotal
               checkout.php → #checkoutTotal */
  total() {
    return this.get().reduce((sum, item) => sum + item.preco * item.quantidade, 0);
  },

  /* Retorna o total de unidades no carrinho (soma de todas as quantidades)
     Usado em: Cart.updateBadge() */
  count() {
    return this.get().reduce((sum, item) => sum + item.quantidade, 0);
  },

  /* Esvazia o carrinho e atualiza o badge
     Usado em: pedido_confirmado.php → Cart.clear() após pedido confirmado
               carrinho.php          → clearCart() (botão "Limpar carrinho") */
  clear() {
    localStorage.removeItem(this.key);
    this.updateBadge();
  },

  /* Atualiza o badge numérico no ícone do carrinho no navbar;
     oculta o badge quando o carrinho está vazio
     Chamado em: save(), clear() e no DOMContentLoaded */
  updateBadge() {
    const badge = document.getElementById('cartBadge');
    if (!badge) return;
    const qty = this.count();
    badge.textContent = qty;
    badge.style.display = qty > 0 ? 'flex' : 'none';
  }
};


/* ============================================================
   2. UTILITÁRIOS — funções globais de formatação e quantidade
   Usado em: salgados.php, doces.php, carrinho.php, checkout.php
============================================================ */

/* Formata um número como moeda brasileira — ex: fmtBRL(8.5) → "R$ 8,50"
   Usado em: carrinho.php, checkout.php, pacotes.php (PackageModal.updateUI) */
function fmtBRL(valor) {
  return 'R$ ' + Number(valor).toFixed(2).replace('.', ',');
}

/* Incrementa ou decrementa um <input> de quantidade; impede valor abaixo de 1
   Usado em: salgados.php → botões −/+ de cada card de produto
             doces.php    → botões −/+ de cada card de produto */
function changeQty(id, delta) {
  const input = document.getElementById(id);
  if (!input) return;
  const novo = parseInt(input.value) + delta;
  input.value = novo < 1 ? 1 : novo;
}

/* Adiciona um produto avulso ao carrinho; reseta o input para 1 e exibe toast
   Usado em: salgados.php → botão "Adicionar" de cada card
             doces.php    → botão "Adicionar" de cada card */
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


/* ============================================================
   3. PackageModal — Modal de montagem de pacotes
   Usado exclusivamente em: pacotes.php
============================================================ */
const PackageModal = {
  currentQtd:  0,   // total de unidades do pacote selecionado (ex: 100)
  currentMax:  0,   // máximo de sabores distintos permitidos (ex: 5)
  currentStep: 1,   // passo atual dos botões +/- (1, 5, 10 ou 25)
  quantities:  {},  // mapa { id_produto: quantidade } por sabor

  /* Abre o modal para o pacote especificado, reiniciando todo o estado visual
     Chamado em: pacotes.php → openPackageModal(qtd, max) nos cards de pacote */
  open(qtd, max) {
    this.currentQtd = qtd;
    this.currentMax = max;
    this.quantities  = {};

    document.getElementById('modalQtd').textContent        = qtd;
    document.getElementById('modalMaxSabores').textContent = max;
    document.getElementById('totalMax').textContent        = qtd;

    /* Zera todos os inputs e remove estado "ativo" */
    document.querySelectorAll('.flavor-row').forEach(row => {
      const id = parseInt(row.dataset.id);
      this.quantities[id] = 0;
      row.querySelector('.qty-input').value = 0;
      row.querySelector('.qty-control').classList.remove('has-qty');
      row.querySelector('.btn-plus').disabled = false;
    });

    /* Reseta botão de incremento para +1 */
    this.setStep(1);
    document.querySelectorAll('.step-btn[data-step]').forEach(b => b.classList.remove('active'));
    document.querySelector('.step-btn[data-step="1"]').classList.add('active');

    this.updateUI();
    document.getElementById('packageModal').classList.add('open');
    document.body.style.overflow = 'hidden';
  },

  /* Fecha o modal e restaura o scroll da página
     Chamado em: pacotes.php → botão "Cancelar" e botão "✕"
                 PackageModal.addToCart() → após adicionar com sucesso */
  close() {
    document.getElementById('packageModal').classList.remove('open');
    document.body.style.overflow = '';
  },

  /* Define o passo (incremento) dos botões +/-
     Chamado em: pacotes.php → botões +1 / +5 / +10 / +25 */
  setStep(n) {
    this.currentStep = n;
  },

  /* Aplica delta positivo ou negativo à quantidade de um sabor
     Delega validação para applyQty()
     Chamado em: pacotes.php → botões − e + de cada linha de sabor (.flavor-row) */
  changeQty(id, delta) {
    const current = this.quantities[id] || 0;
    const newVal  = Math.max(0, current + delta);
    this.applyQty(id, newVal);
  },

  /* Valida e aplica uma nova quantidade a um sabor específico
     Regras:
       1. Bloqueia novo sabor se o limite de sabores (currentMax) já foi atingido
       2. Bloqueia ou clipa se o total de unidades ultrapassaria currentQtd
     Chamado em: changeQty(), onManualInput(), onBlur() */
  applyQty(id, newVal) {
    const saboresAntes = this._countSabores();
    const eraZero      = !this.quantities[id] || this.quantities[id] === 0;
    const entraNovo    = newVal > 0 && eraZero;

    /* Regra 1 — limite de sabores distintos */
    if (entraNovo && saboresAntes >= this.currentMax) {
      this._shake('qc-' + id);
      document.getElementById('qi-' + id).value = 0;
      Swal.fire({ toast:true, position:'top-end', icon:'warning',
        title:`Máximo de ${this.currentMax} sabores!`,
        showConfirmButton:false, timer:2000, timerProgressBar:true });
      return;
    }

    /* Regra 2 — limite de unidades totais do pacote */
    const totalAtual = this._totalUnidades();
    const atual      = this.quantities[id] || 0;
    const diferenca  = newVal - atual;

    if (diferenca > 0 && totalAtual + diferenca > this.currentQtd) {
      const maximo = atual + (this.currentQtd - totalAtual);

      if (maximo <= atual) {
        /* Pacote completamente cheio — rejeita */
        this._shake('qc-' + id);
        document.getElementById('qi-' + id).value = atual;
        Swal.fire({ toast:true, position:'top-end', icon:'warning',
          title:`Limite de ${this.currentQtd} unidades atingido!`,
          showConfirmButton:false, timer:2000, timerProgressBar:true });
        return;
      }

      /* Clamp — aplica apenas o que ainda cabe */
      newVal = maximo;
    }

    this.quantities[id] = newVal;
    const input = document.getElementById('qi-' + id);
    if (input) input.value = newVal;
    this.updateUI();
  },

  /* Trata digitação manual no input (evento oninput); ignora valores inválidos
     Chamado em: pacotes.php → atributo oninput de cada .qty-input */
  onManualInput(id, val) {
    const n = parseInt(val);
    if (isNaN(n) || n < 0) return;
    this.applyQty(id, n);
  },

  /* Trata perda de foco no input; corrige valores inválidos para 0
     Chamado em: pacotes.php → atributo onblur de cada .qty-input */
  onBlur(id, val) {
    const n = parseInt(val);
    this.applyQty(id, isNaN(n) || n < 0 ? 0 : n);
  },

  /* Distribui igualmente as unidades entre os sabores já selecionados;
     o resto da divisão vai para o primeiro sabor
     Chamado em: pacotes.php → botão "Distribuir igual" */
  distribuirIgual() {
    const selecionados = Object.entries(this.quantities)
      .filter(([, v]) => v > 0).map(([k]) => parseInt(k));
    if (selecionados.length === 0) {
      Swal.fire({ toast:true, position:'top-end', icon:'info',
        title:'Selecione ao menos um sabor primeiro!',
        showConfirmButton:false, timer:2200, timerProgressBar:true });
      return;
    }
    const base  = Math.floor(this.currentQtd / selecionados.length);
    const resto = this.currentQtd % selecionados.length;
    selecionados.forEach((id, i) => {
      this.quantities[id] = base + (i === 0 ? resto : 0);
      const input = document.getElementById('qi-' + id);
      if (input) input.value = this.quantities[id];
    });
    this.updateUI();
  },

  /* Zera as quantidades de todos os sabores
     Chamado em: pacotes.php → botão "Zerar" */
  zerarQtds() {
    Object.keys(this.quantities).forEach(id => {
      this.quantities[id] = 0;
      const input = document.getElementById('qi-' + id);
      if (input) input.value = 0;
    });
    this.updateUI();
  },

  /* Atualiza toda a UI do modal:
       - Contadores de sabores e unidades na info-bar
       - Barra de progresso de unidades
       - Preço médio ponderado
       - Estado visual (has-qty) e disabled dos botões +
       - Habilitação do botão "Adicionar ao Carrinho"
     Chamado em: applyQty(), distribuirIgual(), zerarQtds(), open() */
  updateUI() {
    const totalUnidades = this._totalUnidades();
    const saboresSel    = this._countSabores();
    const pct           = Math.min(100, (totalUnidades / this.currentQtd) * 100);

    /* Info-bar */
    document.getElementById('modalContador').textContent = saboresSel;
    document.getElementById('totalUnidades').textContent = totalUnidades;

    /* Resumo inferior */
    document.getElementById('sbSabores').textContent  = `${saboresSel} / ${this.currentMax}`;
    document.getElementById('sbUnidades').textContent = `${totalUnidades} / ${this.currentQtd}`;
    const bar = document.getElementById('pkgProgressBar');
    if (bar) bar.style.width = pct + '%';

    /* Preço médio ponderado — Σ(preco × qty) / total de unidades */
    let precoMedio = 0;
    if (totalUnidades > 0) {
      let soma = 0;
      document.querySelectorAll('.flavor-row').forEach(row => {
        const id    = parseInt(row.dataset.id);
        const preco = parseFloat(row.dataset.preco);
        soma += preco * (this.quantities[id] || 0);
      });
      precoMedio = soma / totalUnidades;
    }
    const sbPreco = document.getElementById('sbPreco');
    if (sbPreco) sbPreco.textContent = fmtBRL(precoMedio);

    /* Estado visual de cada linha de sabor */
    const pacoteCheio = totalUnidades >= this.currentQtd;
    document.querySelectorAll('.flavor-row').forEach(row => {
      const id  = parseInt(row.dataset.id);
      const qty = this.quantities[id] || 0;

      /* Destaca o controle quando qty > 0 */
      const qc = document.getElementById('qc-' + id);
      if (qc) qty > 0 ? qc.classList.add('has-qty') : qc.classList.remove('has-qty');

      /* Desabilita + se: sabor novo e limite de sabores atingido, OU pacote cheio */
      const plusBtn = document.getElementById('plus-' + id);
      if (plusBtn) plusBtn.disabled = (qty === 0 && saboresSel >= this.currentMax) || pacoteCheio;
    });

    /* Botão "Adicionar ao Carrinho" — ativo somente quando há unidades distribuídas */
    const btn = document.getElementById('btnAddPackage');
    if (btn) btn.disabled = saboresSel === 0 || totalUnidades === 0;
  },

  /* Monta o item de pacote e adiciona ao Cart; fecha o modal e exibe toast
     Chamado em: pacotes.php → botão "Adicionar ao Carrinho" (#btnAddPackage) */
  addToCart() {
    const rows     = document.querySelectorAll('.flavor-row');
    const selected = [];
    rows.forEach(row => {
      const id  = parseInt(row.dataset.id);
      const qty = this.quantities[id] || 0;
      if (qty > 0) selected.push({ id, qty,
        nome:  row.dataset.nome,
        preco: parseFloat(row.dataset.preco) });
    });

    if (selected.length === 0) {
      Swal.fire({ toast:true, position:'top-end', icon:'warning',
        title:'Selecione ao menos um sabor!',
        showConfirmButton:false, timer:2500, timerProgressBar:true });
      return;
    }

    const totalUnidades = this._totalUnidades();
    const nomesLabel    = selected.map(s => `${s.qty}x ${s.nome}`).join(', ');
    const precoMedio    = selected.reduce((acc, s) => acc + s.preco * s.qty, 0) / totalUnidades;

    Cart.add({
      id:          'pacote-' + this.currentQtd + '-' + Date.now(),
      nome:        `Pacote ${this.currentQtd}un (${nomesLabel})`,
      preco:       precoMedio,
      quantidade:  this.currentQtd,
      tipo_pacote: this.currentQtd,
    });

    this.close();
    Swal.fire({ toast:true, position:'top-end', icon:'success',
      title:`📦 Pacote de ${this.currentQtd} unidades adicionado!`,
      showConfirmButton:false, timer:2500, timerProgressBar:true });
  },

  /* ── Auxiliares privados ── */

  /* Conta sabores distintos com quantidade > 0 */
  _countSabores() {
    return Object.values(this.quantities).filter(v => v > 0).length;
  },

  /* Soma o total de unidades distribuídas */
  _totalUnidades() {
    return Object.values(this.quantities).reduce((a, b) => a + b, 0);
  },

  /* Aplica animação de "shake" para indicar erro visual
     A classe CSS .pkg-shake está definida em pacotes.php */
  _shake(elId) {
    const el = document.getElementById(elId);
    if (!el) return;
    el.classList.add('pkg-shake');
    setTimeout(() => el.classList.remove('pkg-shake'), 350);
  },
};


/* ============================================================
   4. ALIASES GLOBAIS — wrappers para chamadas via HTML inline
   Usado em: pacotes.php
     → onclick="openPackageModal(qtd, max)" nos cards de pacote
     → onclick="closeModal()" no botão ✕ e no botão Cancelar
============================================================ */

function openPackageModal(qtd, max) { PackageModal.open(qtd, max); }
function closeModal()               { PackageModal.close(); }


/* ============================================================
   5. DOMContentLoaded — inicialização ao carregar a página
   Executado em todos os arquivos que carregam este script
============================================================ */
document.addEventListener('DOMContentLoaded', () => {

  /* Badge do carrinho no navbar — todos os arquivos */
  Cart.updateBadge();

  /* Menu hambúrguer (mobile) — todos os arquivos */
  const toggler  = document.getElementById('navToggler');
  const navLinks = document.getElementById('navLinks');

  if (toggler && navLinks) {
    toggler.addEventListener('click', () => navLinks.classList.toggle('open'));
    document.querySelectorAll('.nav-links a').forEach(link => {
      link.addEventListener('click', () => navLinks.classList.remove('open'));
    });
  }

  /* Seleção visual de forma de pagamento — checkout.php
     Marca o card clicado e aciona o radio button interno */
  document.querySelectorAll('.payment-option').forEach(option => {
    option.addEventListener('click', () => {
      document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
      option.classList.add('selected');
      option.querySelector('input[type="radio"]')?.click();
    });
  });

});