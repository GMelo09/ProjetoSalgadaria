<?php
// require_once __DIR__ . '/../includes/auth.php';
// sessionStart();
// require_once __DIR__ . '/../config/db.php';
// require_once __DIR__ . '/../classes/Produto.php';

// $produtoObj = new Produto();
// $salgados   = $produtoObj->listarProdutos(1);
// $doces      = $produtoObj->listarProdutos(2);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pacotes | Doce &amp; Salgado</title>
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
        <li><a href="pacotes.php" class="active"><i class="bi bi-box-seam"></i> Pacotes</a></li>
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
<div class="page-header" style="background:linear-gradient(135deg,var(--choco) 0%,var(--choco-light) 60%,#A1887F 100%);">
  <div class="container">
    <div class="breadcrumb">
      <a href="../index.php">Início</a>
      <span>/</span>
      <span>Pacotes</span>
    </div>
    <h1>📦 Pacotes Especiais</h1>
    <p>Monte o pacote ideal e escolha os seus sabores preferidos</p>
  </div>
</div>

<!-- ═══════════════ PACKAGES GRID ═══════════════ -->
<section class="section">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow">Escolha o tamanho</div>
      <h2>Selecione um Pacote</h2>
      <p>Clique no pacote desejado para escolher os sabores.</p>
      <div class="section-divider"></div>
    </div>
    <div class="grid-4">
      <?php
      $pacotes = [
        ['qtd'=>50, 'sabores'=>3, 'popular'=>false,'desc'=>'Ideal para reuniões e aniversários pequenos'],
        ['qtd'=>100,'sabores'=>5, 'popular'=>true, 'desc'=>'O mais pedido! Perfeito para festas médias'],
        ['qtd'=>200,'sabores'=>7, 'popular'=>false,'desc'=>'Para festas maiores com variedade de sabores'],
        ['qtd'=>300,'sabores'=>10,'popular'=>false,'desc'=>'O kit completo para grandes comemorações'],
      ];
      foreach ($pacotes as $pk): ?>
      <div class="package-card<?= $pk['popular'] ? ' popular' : '' ?>"
           onclick="openPackageModal(<?= $pk['qtd'] ?>,<?= $pk['sabores'] ?>)"
           style="cursor:pointer;">
        <?php if ($pk['popular']): ?>
          <div class="popular-badge">⭐ Mais Popular</div>
        <?php endif; ?>
        <div class="package-qty"><?= $pk['qtd'] ?></div>
        <div class="package-unit">unidades</div>
        <div class="package-flavors">Até <strong><?= $pk['sabores'] ?> sabores</strong></div>
        <p style="font-size:.82rem;color:var(--muted);margin:.75rem 0;"><?= $pk['desc'] ?></p>
        <button class="btn btn-primary btn-full">
          <i class="bi bi-bag-plus"></i> Montar Pacote
        </button>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════ HOW IT WORKS ═══════════════ -->
<section class="section-sm section-alt">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow">Como funciona</div>
      <h2>É simples assim</h2>
      <div class="section-divider"></div>
    </div>
    <div class="grid-3">
      <div style="text-align:center;padding:1.5rem;">
        <div style="font-size:2.5rem;margin-bottom:1rem;">📦</div>
        <h5 style="font-family:var(--font-serif);margin-bottom:.5rem;">1. Escolha o Pacote</h5>
        <p style="color:var(--muted);font-size:.88rem;">Selecione a quantidade que você precisa para sua festa</p>
      </div>
      <div style="text-align:center;padding:1.5rem;">
        <div style="font-size:2.5rem;margin-bottom:1rem;">🎭</div>
        <h5 style="font-family:var(--font-serif);margin-bottom:.5rem;">2. Monte os Sabores</h5>
        <p style="color:var(--muted);font-size:.88rem;">Escolha entre salgados e doces — você decide a mistura!</p>
      </div>
      <div style="text-align:center;padding:1.5rem;">
        <div style="font-size:2.5rem;margin-bottom:1rem;">🛒</div>
        <h5 style="font-family:var(--font-serif);margin-bottom:.5rem;">3. Finalize o Pedido</h5>
        <p style="color:var(--muted);font-size:.88rem;">Confirme data, endereço e forma de pagamento</p>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════ MODAL ═══════════════ -->
<div class="modal-overlay" id="packageModal">
  <div class="modal-box">
    <div class="modal-header">
      <h5><i class="bi bi-box-seam"></i> Montar Pacote de <span id="modalQtd">0</span> unidades</h5>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>
    <div class="modal-body">
      <div class="info-bar">
        <i class="bi bi-info-circle"></i>
        Selecione até <strong><span id="modalMaxSabores">0</span> sabores</strong>.
        Selecionados: <strong id="modalContador" style="color:var(--rose);">0</strong>
      </div>
      <div class="flavor-section">
        <h6><i class="bi bi-egg-fried"></i> Salgados</h6>
        <?php foreach ($salgados as $s): ?>
        <div class="flavor-item">
          <input type="checkbox" class="flavor-check" id="fs<?= $s['id'] ?>"
                 value="<?= $s['id'] ?>" data-nome="<?= htmlspecialchars($s['nome']) ?>"
                 data-preco="<?= $s['preco'] ?>" onchange="updateFlavorCount()">
          <label for="fs<?= $s['id'] ?>"><?= htmlspecialchars($s['nome']) ?></label>
          <span style="font-size:.82rem;color:var(--muted);">R$ <?= number_format($s['preco'],2,',','.') ?></span>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="flavor-section">
        <h6><i class="bi bi-cup-hot"></i> Doces</h6>
        <?php foreach ($doces as $d): ?>
        <div class="flavor-item">
          <input type="checkbox" class="flavor-check" id="fd<?= $d['id'] ?>"
                 value="<?= $d['id'] ?>" data-nome="<?= htmlspecialchars($d['nome']) ?>"
                 data-preco="<?= $d['preco'] ?>" onchange="updateFlavorCount()">
          <label for="fd<?= $d['id'] ?>"><?= htmlspecialchars($d['nome']) ?></label>
          <span style="font-size:.82rem;color:var(--muted);">R$ <?= number_format($d['preco'],2,',','.') ?></span>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-choco" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-primary" onclick="addPackageToCart()">
        <i class="bi bi-cart-plus"></i> Adicionar ao Carrinho
      </button>
    </div>
  </div>
</div>

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
let currentQtd = 0, currentMax = 0;

function openPackageModal(qtd, max) {
  currentQtd = qtd; currentMax = max;
  document.getElementById('modalQtd').textContent = qtd;
  document.getElementById('modalMaxSabores').textContent = max;
  document.getElementById('modalContador').textContent = '0';
  document.querySelectorAll('.flavor-check').forEach(c => { c.checked = false; c.disabled = false; });
  document.getElementById('packageModal').classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeModal() {
  document.getElementById('packageModal').classList.remove('open');
  document.body.style.overflow = '';
}

document.getElementById('packageModal').addEventListener('click', function(e) {
  if (e.target === this) closeModal();
});

function updateFlavorCount() {
  const n = document.querySelectorAll('.flavor-check:checked').length;
  document.getElementById('modalContador').textContent = n;
  document.querySelectorAll('.flavor-check').forEach(c => {
    if (!c.checked) c.disabled = n >= currentMax;
  });
}

function addPackageToCart() {
  const checked = document.querySelectorAll('.flavor-check:checked');
  if (checked.length === 0) {
    Swal.fire({ toast:true, position:'top-end', icon:'warning', title:'Selecione ao menos um sabor!', showConfirmButton:false, timer:2500, timerProgressBar:true });
    return;
  }
  let nomes = [], precoTotal = 0;
  checked.forEach(c => { nomes.push(c.dataset.nome); precoTotal += parseFloat(c.dataset.preco); });
  const precoMedio = precoTotal / checked.length;
  const nome = `Pacote ${currentQtd}un (${nomes.join(', ')})`;
  Cart.add({ id: 'pacote-' + Date.now(), nome, preco: precoMedio, quantidade: currentQtd, tipo_pacote: currentQtd });
  closeModal();
  Swal.fire({ toast:true, position:'top-end', icon:'success', title:`📦 Pacote de ${currentQtd} unidades adicionado!`, showConfirmButton:false, timer:2500, timerProgressBar:true });
}
</script>
</body>
</html>