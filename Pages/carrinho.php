<?php
require_once __DIR__ . '/../includes/auth.php';
sessionStart();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Carrinho | Doce &amp; Salgado</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../css/style.css" rel="stylesheet">
</head>
<body>

<!-- ═══════════════ NAVBAR ═══════════════ -->
<header>
  <nav class="navbar-main">
    <div class="container">
      <a href="../index.php" class="nav-brand">
        <i class="bi bi-cake2"></i> Doce<span>&amp;</span>Salgado
      </a>
      <ul class="nav-links" id="navLinks">
        <li><a href="../index.php"><i class="bi bi-house"></i> Início</a></li>
        <li><a href="salgados.php"><i class="bi bi-egg-fried"></i> Salgados</a></li>
        <li><a href="doces.php"><i class="bi bi-cup-hot"></i> Doces</a></li>
        <li><a href="pacotes.php"><i class="bi bi-box-seam"></i> Pacotes</a></li>
        <li>
          <a href="carrinho.php" class="nav-cart-btn active">
            <i class="bi bi-cart3"></i> Carrinho
            <span class="cart-badge" id="cartBadge" style="display:none;">0</span>
          </a>
        </li>
        <?php if (!empty($_SESSION['usuario_nome'])): ?>
        <li class="dropdown">
          <a href="#" data-bs-toggle="dropdown" role="button">
            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['usuario_nome']) ?>
            <i class="bi bi-chevron-down" style="font-size:.65rem;"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <?php if (!empty($_SESSION['eh_admin'])): ?>
            <li><a class="dropdown-item" href="../admin/dashboard.php"><i class="bi bi-speedometer2"></i> Painel Admin</a></li>
            <li><hr class="dropdown-divider"></li>
            <?php endif; ?>
            <li><a class="dropdown-item" href="../actions/sair.php"><i class="bi bi-box-arrow-right"></i> Sair</a></li>
          </ul>
        </li>
        <?php else: ?>
        <li><a href="login.php"><i class="bi bi-person"></i> Entrar</a></li>
        <?php endif; ?>
      </ul>
      <button class="navbar-toggler-main" id="navToggler" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </nav>
</header>

<!-- ═══════════════ PAGE HEADER ═══════════════ -->
<div class="page-header">
  <div class="container">
    <div class="breadcrumb">
      <a href="../index.php">Início</a>
      <span>/</span>
      <span>Carrinho</span>
    </div>
    <h1>🛒 Meu Carrinho</h1>
    <p>Revise seus itens antes de finalizar o pedido</p>
  </div>
</div>

<!-- ═══════════════ CONTENT ═══════════════ -->
<section class="section">
  <div class="container">

    <!-- Empty state -->
    <div id="cartEmpty" style="display:none;">
      <div class="cart-empty">
        <div class="cart-empty-icon">🛒</div>
        <h3 style="font-family:var(--font-serif);margin-bottom:.5rem;">Seu carrinho está vazio</h3>
        <p class="text-muted mb-4">Adicione deliciosos salgados e doces para começar!</p>
        <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
          <a href="salgados.php" class="btn btn-primary btn-lg"><i class="bi bi-egg-fried"></i> Ver Salgados</a>
          <a href="doces.php" class="btn btn-outline btn-lg"><i class="bi bi-cup-hot"></i> Ver Doces</a>
        </div>
      </div>
    </div>

    <!-- Cart with items -->
    <div id="cartContent" style="display:none;">
      <div id="cartLayout">
        <div class="cart-table-wrap">
          <table class="cart-table">
            <thead>
              <tr>
                <th>Produto</th>
                <th>Preço Unit.</th>
                <th>Quantidade</th>
                <th>Subtotal</th>
                <th></th>
              </tr>
            </thead>
            <tbody id="cartBody"></tbody>
          </table>
        </div>
        <div class="cart-summary">
          <h4><i class="bi bi-receipt" style="color:var(--rose);"></i> Resumo do Pedido</h4>
          <div class="summary-row">
            <span>Itens no carrinho</span>
            <span id="summaryCount">0</span>
          </div>
          <div class="summary-row">
            <span>Sabores distintos</span>
            <span id="summaryTypes">0</span>
          </div>
          <div class="summary-row total">
            <span>Total</span>
            <span class="amount" id="summaryTotal">R$ 0,00</span>
          </div>
          <div style="margin-top:1.5rem;display:flex;flex-direction:column;gap:.75rem;">
            <a href="checkout.php" class="btn btn-primary btn-full btn-lg">
              <i class="bi bi-bag-check"></i> Finalizar Pedido
            </a>
            <a href="../index.php" class="btn btn-outline btn-full">
              <i class="bi bi-arrow-left"></i> Continuar Comprando
            </a>
            <button class="btn btn-full"
                    style="background:none;border:none;color:var(--muted);font-size:.82rem;padding:.5rem;"
                    onclick="clearCart()">
              <i class="bi bi-trash"></i> Limpar carrinho
            </button>
          </div>
        </div>
      </div>
    </div>

  </div>
</section>

<!-- ═══════════════ FOOTER ═══════════════ -->
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="footer-brand"><i class="bi bi-cake2"></i> Doce<span>&amp;</span>Salgado</div>
        <p>Salgados crocantes e doces irresistíveis, feitos com amor artesanal para tornar sua festa inesquecível.</p>
        <div style="display:flex;gap:.75rem;margin-top:1rem;">
          <a href="#" style="color:rgba(255,255,255,.5);font-size:1.2rem;"><i class="bi bi-instagram"></i></a>
          <a href="#" style="color:rgba(255,255,255,.5);font-size:1.2rem;"><i class="bi bi-whatsapp"></i></a>
          <a href="#" style="color:rgba(255,255,255,.5);font-size:1.2rem;"><i class="bi bi-facebook"></i></a>
        </div>
      </div>
      <div>
        <h6>Navegação</h6>
        <ul class="footer-links">
          <li><a href="../index.php">Início</a></li>
          <li><a href="salgados.php">Salgados</a></li>
          <li><a href="doces.php">Doces</a></li>
          <li><a href="pacotes.php">Pacotes</a></li>
          <li><a href="carrinho.php">Carrinho</a></li>
        </ul>
      </div>
      <div>
        <h6>Contato</h6>
        <ul class="footer-links">
          <li><a href="#"><i class="bi bi-telephone"></i> (11) 99999-9999</a></li>
          <li><a href="#"><i class="bi bi-envelope"></i> contato@docesalgado.com.br</a></li>
          <li><a href="#"><i class="bi bi-geo-alt"></i> São Paulo, SP</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <span>&copy; <?= date('Y') ?> Doce &amp; Salgado. Todos os direitos reservados.</span>
      <span>Feito com <i class="bi bi-heart-fill" style="color:#F8BBD0;"></i> para você</span>
    </div>
  </div>
</footer>

<div class="toast-container" id="toastContainer"></div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../js/app.js"></script>
<script>
document.addEventListener('DOMContentLoaded', renderCart);

function renderCart() {
  const items   = Cart.get();
  const empty   = document.getElementById('cartEmpty');
  const content = document.getElementById('cartContent');

  if (items.length === 0) {
    empty.style.display   = 'block';
    content.style.display = 'none';
    return;
  }
  empty.style.display   = 'none';
  content.style.display = 'block';

  let rows = '', totalQty = 0;
  items.forEach((item, idx) => {
    const sub = item.preco * item.quantidade;
    totalQty += item.quantidade;
    rows += `
      <tr>
        <td>
          <div class="cart-product-name">${item.nome}</div>
          ${item.tipo_pacote ? `<div class="cart-product-sub">Pacote de ${item.tipo_pacote} un</div>` : ''}
        </td>
        <td class="cart-price">${fmtBRL(item.preco)}</td>
        <td>
          <div class="qty-control" style="width:fit-content;">
            <button onclick="cartChangeQty(${idx},-1)">−</button>
            <input type="number" value="${item.quantidade}" readonly>
            <button onclick="cartChangeQty(${idx},1)">+</button>
          </div>
        </td>
        <td class="cart-subtotal">${fmtBRL(sub)}</td>
        <td>
          <button class="btn btn-danger btn-icon" onclick="cartRemove(${idx})" title="Remover">
            <i class="bi bi-trash"></i>
          </button>
        </td>
      </tr>`;
  });

  document.getElementById('cartBody').innerHTML      = rows;
  document.getElementById('summaryCount').textContent = totalQty;
  document.getElementById('summaryTypes').textContent = items.length;
  document.getElementById('summaryTotal').textContent = fmtBRL(Cart.total());
}

function cartChangeQty(idx, delta) {
  const items = Cart.get();
  Cart.setQty(idx, items[idx].quantidade + delta);
  renderCart();
}

function cartRemove(idx) {
  Cart.remove(idx);
  renderCart();
  Swal.fire({ toast:true, position:'top-end', icon:'warning', title:'Item removido do carrinho.', showConfirmButton:false, timer:2500, timerProgressBar:true });
}

function clearCart() {
  Swal.fire({
    title: 'Limpar carrinho?',
    text: 'Todos os itens serão removidos.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Sim, limpar',
    cancelButtonText: 'Cancelar',
    confirmButtonColor: '#e53935',
  }).then(result => {
    if (result.isConfirmed) {
      Cart.clear();
      renderCart();
      Swal.fire({ toast:true, position:'top-end', icon:'success', title:'Carrinho limpo!', showConfirmButton:false, timer:2000, timerProgressBar:true });
    }
  });
}
</script>

<style>
#cartLayout {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 2rem;
  align-items: start;
}
.cart-summary { position: sticky; top: calc(var(--nav-h) + 1rem); }
@media (max-width: 900px) {
  #cartLayout { grid-template-columns: 1fr; }
  .cart-summary { position: static; }
}
@media (max-width: 600px) {
  .cart-table thead th:nth-child(2),
  .cart-table tbody td:nth-child(2) { display: none; }
}
</style>
</body>
</html>