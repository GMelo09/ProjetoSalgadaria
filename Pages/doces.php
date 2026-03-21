<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/produto_class.php';
sessionStart();

$produtoObj = new Produto();
$produtos   = $produtoObj->ListarPorCategoria(2);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Doces | Doce &amp; Salgado</title>
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
        <li><a href="doces.php" class="active"><i class="bi bi-cup-hot"></i> Doces</a></li>
        <li><a href="pacotes.php"><i class="bi bi-box-seam"></i> Pacotes</a></li>
        <li>
          <a href="carrinho.php" class="nav-cart-btn">
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
            <li><a class="dropdown-item" href="login.php?sair=1"><i class="bi bi-box-arrow-right"></i> Sair</a></li>
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
<div class="page-header" style="background:linear-gradient(135deg,var(--rose-dark) 0%,var(--rose) 60%,#AD1457 100%);">
  <div class="container">
    <div class="breadcrumb">
      <a href="../index.php">Início</a>
      <span>/</span>
      <span>Doces</span>
    </div>
    <h1>🍫 Doces</h1>
    <p><?= count($produtos) ?> opções de doces artesanais irresistíveis</p>
  </div>
</div>

<!-- ═══════════════ FILTER BAR ═══════════════ -->
<div class="filter-bar">
  <div class="container">
    <span class="filter-label">Filtrar:</span>
    <button class="filter-chip active" data-filter="all">Todos</button>
    <button class="filter-chip" data-filter="Clássico">Clássico</button>
    <button class="filter-chip" data-filter="Premium">Premium</button>
    <button class="filter-chip" data-filter="Especial">Especial</button>
    <button class="filter-chip" data-filter="Fruta">Fruta</button>
  </div>
</div>

<!-- ═══════════════ PRODUCTS GRID ═══════════════ -->
<section class="section">
  <div class="container">
    <div class="grid-4" id="productsGrid">
      <?php foreach ($produtos as $p): ?>
      <div class="product-card" data-tag="<?= htmlspecialchars($p['tag']) ?>">
        <div class="product-card-image">
          <div class="product-card-placeholder" style="background:linear-gradient(135deg,#FCE4EC,#F8BBD0);">
            <?= $p['emoji'] ?>
          </div>
          <span class="product-badge badge-doce"><?= htmlspecialchars($p['tag']) ?></span>
        </div>
        <div class="product-card-body">
          <div class="product-name"><?= htmlspecialchars($p['nome']) ?></div>
          <div class="product-desc"><?= htmlspecialchars($p['descricao']) ?></div>
          <div class="product-price">
            R$ <?= number_format($p['preco'],2,',','.') ?>
            <span style="font-size:.75rem;color:var(--muted);font-family:var(--font-sans);font-weight:400;"> / un</span>
          </div>
          <div class="product-footer">
            <div class="qty-control">
              <button onclick="changeQty('qd<?= $p['id'] ?>',-1)">−</button>
              <input type="number" id="qd<?= $p['id'] ?>" value="1" min="1" max="999" readonly>
              <button onclick="changeQty('qd<?= $p['id'] ?>',1)">+</button>
            </div>
            <button class="btn btn-primary btn-add-cart"
                    onclick="addToCart(<?= $p['id'] ?>,'<?= addslashes($p['nome']) ?>',<?= $p['preco'] ?>,'qd<?= $p['id'] ?>')">
              <i class="bi bi-cart-plus"></i> Adicionar
            </button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════ CTA ═══════════════ -->
<section class="section-sm" style="background:linear-gradient(135deg,var(--rose-pale),var(--white));text-align:center;">
  <div class="container">
    <h3 style="font-family:var(--font-serif);margin-bottom:.5rem;">Monte seu pacote de doces!</h3>
    <p class="text-muted mb-3">Combine vários sabores e economize nas encomendas maiores.</p>
    <a href="pacotes.php" class="btn btn-primary btn-lg"><i class="bi bi-box-seam"></i> Ver Pacotes</a>
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
document.querySelectorAll('.filter-chip').forEach(chip => {
  chip.addEventListener('click', function() {
    document.querySelectorAll('.filter-chip').forEach(c => c.classList.remove('active'));
    this.classList.add('active');
    const filter = this.dataset.filter;
    document.querySelectorAll('.product-card').forEach(card => {
      card.style.display = (filter === 'all' || card.dataset.tag === filter) ? '' : 'none';
    });
  });
});
</script>
</body>
</html>