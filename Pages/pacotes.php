<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/produto_class.php';
sessionStart();

$produtoObj = new Produto();
$salgados   = $produtoObj->ListarPorCategoria(1);
$doces      = $produtoObj->ListarPorCategoria(2);

$pacotes = [
  ['qtd' => 50,  'sabores' => 3,  'popular' => false, 'desc' => 'Ideal para reuniões e aniversários pequenos'],
  ['qtd' => 100, 'sabores' => 5,  'popular' => true,  'desc' => 'O mais pedido! Perfeito para festas médias'],
  ['qtd' => 200, 'sabores' => 7,  'popular' => false, 'desc' => 'Para festas maiores com variedade de sabores'],
  ['qtd' => 300, 'sabores' => 10, 'popular' => false, 'desc' => 'O kit completo para grandes comemorações'],
];
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
<style>
/* ── Barra de incremento ── */
.pkg-step-bar {
  display: flex;
  align-items: center;
  gap: .4rem;
  padding: .55rem 1rem;
  border-bottom: 1px solid #f0e8e8;
  background: #fff9fb;
  flex-wrap: wrap;
  flex-shrink: 0;
}
.pkg-step-label {
  font-size: .72rem;
  color: #aaa;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: .05em;
  margin-right: .15rem;
}
.step-btn {
  background: #f8f0f4;
  border: 1.5px solid #e8d0da;
  border-radius: 20px;
  padding: .22rem .65rem;
  font-size: .78rem;
  font-weight: 700;
  color: var(--rose, #c2185b);
  cursor: pointer;
  transition: all .15s;
  display: inline-flex;
  align-items: center;
  gap: .25rem;
}
.step-btn:hover, .step-btn.active {
  background: var(--rose, #c2185b);
  color: white;
  border-color: var(--rose, #c2185b);
}
.step-btn-action { background:#fff3e0; color:#e65100; border-color:#ffcc80; }
.step-btn-action:hover { background:#e65100; color:white; border-color:#e65100; }
.step-btn-reset  { background:#f5f5f5; color:#888; border-color:#ddd; }
.step-btn-reset:hover  { background:#888; color:white; border-color:#888; }
.pkg-step-div { width:1px; height:20px; background:#eee; margin: 0 .15rem; }

/* ── flavor-item com qty ── */
.flavor-item {
  display: flex;
  align-items: center;
  gap: .65rem;
  padding: .5rem .4rem;
  border-radius: 8px;
  border-bottom: 1px solid #fdf0f5;
  transition: background .15s;
}
.flavor-item:hover { background: #fff5f8; }
.flavor-item:last-child { border-bottom: none; }
.flavor-name  { flex: 1; font-size: .88rem; color: #444; font-weight: 500; }
.flavor-price { font-size: .78rem; color: #bbb; min-width: 58px; text-align: right; }

/* ── Controle de quantidade ── */
.qty-control {
  display: flex;
  align-items: center;
  background: #f8f0f4;
  border: 1.5px solid #e8d0da;
  border-radius: 25px;
  overflow: hidden;
  transition: border-color .2s;
  flex-shrink: 0;
}
.qty-control.has-qty { background: #fce4ec; border-color: var(--rose, #c2185b); }
.qty-btn {
  background: none;
  border: none;
  width: 28px; height: 28px;
  display: flex; align-items: center; justify-content: center;
  cursor: pointer;
  color: var(--rose, #c2185b);
  font-size: 1rem; font-weight: 700;
  transition: background .15s, color .15s;
}
.qty-btn:hover { background: var(--rose, #c2185b); color: white; }
.qty-btn:disabled { color: #ccc !important; cursor: not-allowed; }
.qty-btn:disabled:hover { background: none; }
.qty-input {
  width: 38px; height: 28px;
  border: none; background: none;
  text-align: center;
  font-size: .85rem; font-weight: 700;
  color: var(--rose, #c2185b);
  outline: none;
  -moz-appearance: textfield;
}
.qty-input::-webkit-outer-spin-button,
.qty-input::-webkit-inner-spin-button { -webkit-appearance: none; }

/* ── Barra de resumo ── */
.pkg-summary-bar {
  background: linear-gradient(135deg, #880e4f, #c2185b);
  padding: .7rem 1.2rem;
  flex-shrink: 0;
}
.pkg-summary-stats { display: flex; gap: 1.5rem; flex-wrap: wrap; }
.pkg-stat { font-size: .75rem; color: rgba(255,255,255,.75); }
.pkg-stat strong { display: block; font-size: .95rem; color: white; margin-top: 1px; }
.pkg-progress-wrap {
  background: rgba(255,255,255,.25);
  border-radius: 4px; height: 4px; width: 100px;
  margin-top: 4px; overflow: hidden;
}
.pkg-progress-fill { height: 100%; background: white; border-radius: 4px; transition: width .3s; }

/* ── Animação de erro ── */
.pkg-shake { animation: pkgShake .3s ease; }
@keyframes pkgShake {
  0%,100%{transform:translateX(0)} 25%{transform:translateX(-4px)} 75%{transform:translateX(4px)}
}
</style>
</head>
<body>

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
              <li>
                <form action="../actions/logout.php" method="POST" style="margin:0;">
                  <?= csrfField() ?>
                  <button type="submit" class="dropdown-item" style="background:none;border:none;width:100%;text-align:left;">
                    <i class="bi bi-box-arrow-right"></i> Sair
                  </button>
                </form>
              </li>
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

<div class="page-header" style="background:linear-gradient(135deg,var(--choco) 0%,var(--choco-light) 60%,#A1887F 100%);">
  <div class="container">
    <div class="breadcrumb">
      <a href="../index.php">Início</a><span>/</span><span>Pacotes</span>
    </div>
    <h1>📦 Pacotes Especiais</h1>
    <p>Monte o pacote ideal e escolha os seus sabores preferidos</p>
  </div>
</div>

<section class="section">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow">Escolha o tamanho</div>
      <h2>Selecione um Pacote</h2>
      <p>Clique no pacote desejado para escolher os sabores.</p>
      <div class="section-divider"></div>
    </div>
    <div class="grid-4">
      <?php foreach ($pacotes as $pk): ?>
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

<div class="modal-overlay" id="packageModal">
  <div class="modal-box">

    <!-- Cabeçalho -->
    <div class="modal-header">
      <h5><i class="bi bi-box-seam"></i> Montar Pacote de <span id="modalQtd">0</span> unidades</h5>
      <button class="modal-close" onclick="closeModal()">✕</button>
    </div>

    <!-- Info bar -->
    <div class="info-bar">
      <i class="bi bi-info-circle"></i>
      Selecione até <strong><span id="modalMaxSabores">0</span> sabores</strong>.
      &nbsp;Selecionados: <strong id="modalContador" style="color:var(--rose);">0</strong>
      &nbsp;|&nbsp; Unidades distribuídas: <strong id="totalUnidades">0</strong> / <strong id="totalMax">0</strong>
    </div>

    <!-- Botões de incremento -->
    <div class="pkg-step-bar">
      <span class="pkg-step-label">Incremento:</span>
      <button class="step-btn active" data-step="1"
        onclick="PackageModal.setStep(1); document.querySelectorAll('.step-btn[data-step]').forEach(b=>b.classList.remove('active')); this.classList.add('active');">+1</button>
      <button class="step-btn" data-step="5"
        onclick="PackageModal.setStep(5); document.querySelectorAll('.step-btn[data-step]').forEach(b=>b.classList.remove('active')); this.classList.add('active');">+5</button>
      <button class="step-btn" data-step="10"
        onclick="PackageModal.setStep(10); document.querySelectorAll('.step-btn[data-step]').forEach(b=>b.classList.remove('active')); this.classList.add('active');">+10</button>
      <button class="step-btn" data-step="25"
        onclick="PackageModal.setStep(25); document.querySelectorAll('.step-btn[data-step]').forEach(b=>b.classList.remove('active')); this.classList.add('active');">+25</button>
      <div class="pkg-step-div"></div>
      <button class="step-btn step-btn-action"
        onclick="PackageModal.distribuirIgual()">
        <i class="bi bi-distribute-horizontal"></i> Distribuir igual
      </button>
      <button class="step-btn step-btn-reset"
        onclick="PackageModal.zerarQtds()">
        <i class="bi bi-arrow-counterclockwise"></i> Zerar
      </button>
    </div>

    <!-- Lista de sabores -->
    <div class="modal-body">
      <div class="flavor-section">
        <h6><i class="bi bi-egg-fried"></i> Salgados</h6>
        <?php foreach ($salgados as $s): ?>
          <div class="flavor-item flavor-row"
               data-id="<?= $s['id'] ?>"
               data-nome="<?= htmlspecialchars($s['nome']) ?>"
               data-preco="<?= $s['preco'] ?>">
            <span class="flavor-name"><?= htmlspecialchars($s['nome']) ?></span>
            <span class="flavor-price">R$ <?= number_format($s['preco'], 2, ',', '.') ?></span>
            <div class="qty-control" id="qc-<?= $s['id'] ?>">
              <button class="qty-btn btn-minus"
                onclick="PackageModal.changeQty(<?= $s['id'] ?>, -PackageModal.currentStep)">−</button>
              <input type="number" class="qty-input" id="qi-<?= $s['id'] ?>" value="0" min="0"
                oninput="PackageModal.onManualInput(<?= $s['id'] ?>, this.value)"
                onblur="PackageModal.onBlur(<?= $s['id'] ?>, this.value)">
              <button class="qty-btn btn-plus" id="plus-<?= $s['id'] ?>"
                onclick="PackageModal.changeQty(<?= $s['id'] ?>, PackageModal.currentStep)">+</button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <div class="flavor-section">
        <h6><i class="bi bi-cup-hot"></i> Doces</h6>
        <?php foreach ($doces as $d): ?>
          <div class="flavor-item flavor-row"
               data-id="<?= $d['id'] ?>"
               data-nome="<?= htmlspecialchars($d['nome']) ?>"
               data-preco="<?= $d['preco'] ?>">
            <span class="flavor-name"><?= htmlspecialchars($d['nome']) ?></span>
            <span class="flavor-price">R$ <?= number_format($d['preco'], 2, ',', '.') ?></span>
            <div class="qty-control" id="qc-<?= $d['id'] ?>">
              <button class="qty-btn btn-minus"
                onclick="PackageModal.changeQty(<?= $d['id'] ?>, -PackageModal.currentStep)">−</button>
              <input type="number" class="qty-input" id="qi-<?= $d['id'] ?>" value="0" min="0"
                oninput="PackageModal.onManualInput(<?= $d['id'] ?>, this.value)"
                onblur="PackageModal.onBlur(<?= $d['id'] ?>, this.value)">
              <button class="qty-btn btn-plus" id="plus-<?= $d['id'] ?>"
                onclick="PackageModal.changeQty(<?= $d['id'] ?>, PackageModal.currentStep)">+</button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Barra de resumo -->
    <div class="pkg-summary-bar">
      <div class="pkg-summary-stats">
        <div class="pkg-stat">
          <span>Sabores</span>
          <strong id="sbSabores">0 / 0</strong>
        </div>
        <div class="pkg-stat">
          <span>Unidades</span>
          <strong id="sbUnidades">0 / 0</strong>
          <div class="pkg-progress-wrap"><div class="pkg-progress-fill" id="pkgProgressBar" style="width:0%"></div></div>
        </div>
        <div class="pkg-stat">
          <span>Preço médio/un</span>
          <strong id="sbPreco">R$ 0,00</strong>
        </div>
      </div>
    </div>

    <!-- Rodapé -->
    <div class="modal-footer">
      <button class="btn btn-choco" onclick="closeModal()">Cancelar</button>
      <button class="btn btn-primary" id="btnAddPackage" onclick="PackageModal.addToCart()" disabled>
        <i class="bi bi-cart-plus"></i> Adicionar ao Carrinho
      </button>
    </div>

  </div>
</div>

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
</body>
</html>