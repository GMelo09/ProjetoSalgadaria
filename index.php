<?php
require_once __DIR__ . '/includes/auth.php';
sessionStart();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/classes/Produto.php';

$produtoObj = new Produto();
$salgados   = array_slice($produtoObj->listarProdutos(1), 0, 4);
$doces      = array_slice($produtoObj->listarProdutos(2), 0, 4);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Doce & Salgado — Salgaderia e doceria artesanal. Salgados e doces fresquinhos para sua festa!">
  <title>Início | Doce &amp; Salgado</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">
</head>
<body>

<!-- ═══════════════ NAVBAR ═══════════════ -->
<header>
  <nav class="navbar-main">
    <div class="container">
      <a href="index.php" class="nav-brand">
        <i class="bi bi-cake2"></i> Doce<span>&amp;</span>Salgado
      </a>
      <ul class="nav-links" id="navLinks">
        <li><a href="index.php" class="active"><i class="bi bi-house"></i> Início</a></li>
        <li><a href="pages/salgados.php"><i class="bi bi-egg-fried"></i> Salgados</a></li>
        <li><a href="pages/doces.php"><i class="bi bi-cup-hot"></i> Doces</a></li>
        <li><a href="pages/pacotes.php"><i class="bi bi-box-seam"></i> Pacotes</a></li>
        <li>
          <a href="pages/carrinho.php" class="nav-cart-btn">
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
            <li><a class="dropdown-item" href="admin/dashboard.php"><i class="bi bi-speedometer2"></i> Painel Admin</a></li>
            <li><hr class="dropdown-divider"></li>
            <?php endif; ?>
            <li><a class="dropdown-item" href="actions/sair.php"><i class="bi bi-box-arrow-right"></i> Sair</a></li>
          </ul>
        </li>
        <?php else: ?>
        <li><a href="pages/login.php"><i class="bi bi-person"></i> Entrar</a></li>
        <?php endif; ?>
      </ul>
      <button class="navbar-toggler-main" id="navToggler" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </nav>
</header>

<!-- ═══════════════ HERO ═══════════════ -->
<section class="hero">
  <div class="hero-pattern"></div>
  <div class="container">
    <div class="hero-content">
      <div class="hero-eyebrow">Artesanal &amp; Fresquinho</div>
      <h1>Doce &amp;<br>Salgado</h1>
      <p>Salgados crocantes e doces irresistíveis para tornar<br>sua festa inesquecível. Encomende agora!</p>
      <div class="hero-actions">
        <a href="pages/salgados.php" class="btn btn-white btn-lg">
          <i class="bi bi-egg-fried"></i> Ver Salgados
        </a>
        <a href="pages/doces.php" class="btn btn-lg" style="border:2px solid rgba(255,255,255,.6);color:#fff;background:transparent;">
          <i class="bi bi-cup-hot"></i> Ver Doces
        </a>
      </div>
    </div>
  </div>
</section>

<!-- ═══════════════ STATS ═══════════════ -->
<div class="stats-strip">
  <div class="container">
    <div class="stats-grid">
      <div><div class="stat-num">15+</div><div class="stat-label">Sabores</div></div>
      <div><div class="stat-num">500+</div><div class="stat-label">Festas Atendidas</div></div>
      <div><div class="stat-num">4</div><div class="stat-label">Tamanhos de Pacote</div></div>
      <div><div class="stat-num">100%</div><div class="stat-label">Artesanal</div></div>
    </div>
  </div>
</div>

<!-- ═══════════════ SALGADOS DESTAQUE ═══════════════ -->
<section class="section" id="salgados">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow">Nossos Clássicos</div>
      <h2>🥟 Salgados em Destaque</h2>
      <p>Crocantes por fora, recheados por dentro. Escolha os favoritos da sua festa.</p>
      <div class="section-divider"></div>
    </div>
    <div class="grid-4">
      <?php foreach ($salgados as $p): ?>
      <div class="product-card">
        <div class="product-card-image">
          <div class="product-card-placeholder" style="background:linear-gradient(135deg,#EFEBE9,#D7CCC8);">
            <?= $p['emoji'] ?>
          </div>
          <span class="product-badge badge-salgado">Salgado</span>
        </div>
        <div class="product-card-body">
          <div class="product-name"><?= htmlspecialchars($p['nome']) ?></div>
          <div class="product-desc"><?= htmlspecialchars($p['descricao']) ?></div>
          <div class="product-price">R$ <?= number_format($p['preco'],2,',','.') ?></div>
          <div class="product-footer">
            <div class="qty-control">
              <button onclick="changeQty('qs<?= $p['id'] ?>',-1)">−</button>
              <input type="number" id="qs<?= $p['id'] ?>" value="1" min="1" max="999" readonly>
              <button onclick="changeQty('qs<?= $p['id'] ?>',1)">+</button>
            </div>
            <button class="btn btn-primary btn-add-cart"
                    onclick="addToCart(<?= $p['id'] ?>,'<?= addslashes($p['nome']) ?>',<?= $p['preco'] ?>,'qs<?= $p['id'] ?>')">
              <i class="bi bi-cart-plus"></i> Adicionar
            </button>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-4">
      <a href="pages/salgados.php" class="btn btn-outline">
        Ver todos os salgados <i class="bi bi-arrow-right"></i>
      </a>
    </div>
  </div>
</section>

<!-- ═══════════════ PACOTES ═══════════════ -->
<section class="section section-rose" id="pacotes">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow">Monte a Sua Festa</div>
      <h2>📦 Pacotes Especiais</h2>
      <p>Escolha o tamanho ideal, selecione seus sabores favoritos e receba tudo fresquinho.</p>
      <div class="section-divider"></div>
    </div>
    <div class="grid-4">
      <?php
      $pacotes = [
        ['qtd'=>50, 'sabores'=>3, 'popular'=>false,'desc'=>'Ideal para reuniões'],
        ['qtd'=>100,'sabores'=>5, 'popular'=>true, 'desc'=>'O mais pedido!'],
        ['qtd'=>200,'sabores'=>7, 'popular'=>false,'desc'=>'Para festas maiores'],
        ['qtd'=>300,'sabores'=>10,'popular'=>false,'desc'=>'Para grandes comemorações'],
      ];
      foreach ($pacotes as $pk): ?>
      <div class="package-card<?= $pk['popular'] ? ' popular' : '' ?>">
        <?php if ($pk['popular']): ?>
          <div class="popular-badge">⭐ Mais Popular</div>
        <?php endif; ?>
        <div class="package-qty"><?= $pk['qtd'] ?></div>
        <div class="package-unit">unidades</div>
        <div class="package-flavors">Até <strong><?= $pk['sabores'] ?> sabores</strong></div>
        <p style="font-size:.82rem;color:var(--muted);margin:.5rem 0;"><?= $pk['desc'] ?></p>
        <a href="pages/pacotes.php?qtd=<?= $pk['qtd'] ?>" class="btn btn-primary btn-full mt-2">
          <i class="bi bi-bag-plus"></i> Montar Pacote
        </a>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- ═══════════════ DOCES DESTAQUE ═══════════════ -->
<section class="section section-alt" id="doces">
  <div class="container">
    <div class="section-header">
      <div class="eyebrow">Irresistíveis</div>
      <h2>🍫 Doces em Destaque</h2>
      <p>Tradição e sabor em cada pedacinho. Doces que fazem a festa!</p>
      <div class="section-divider"></div>
    </div>
    <div class="grid-4">
      <?php foreach ($doces as $p): ?>
      <div class="product-card">
        <div class="product-card-image">
          <div class="product-card-placeholder" style="background:linear-gradient(135deg,#FCE4EC,#F8BBD0);">
            <?= $p['emoji'] ?>
          </div>
          <span class="product-badge badge-doce">Doce</span>
        </div>
        <div class="product-card-body">
          <div class="product-name"><?= htmlspecialchars($p['nome']) ?></div>
          <div class="product-desc"><?= htmlspecialchars($p['descricao']) ?></div>
          <div class="product-price">R$ <?= number_format($p['preco'],2,',','.') ?></div>
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
    <div class="text-center mt-4">
      <a href="pages/doces.php" class="btn btn-outline">
        Ver todos os doces <i class="bi bi-arrow-right"></i>
      </a>
    </div>
  </div>
</section>

<!-- ═══════════════ CTA ═══════════════ -->
<section class="section" style="background:linear-gradient(135deg,var(--rose-dark),var(--rose));text-align:center;">
  <div class="container-sm">
    <div style="font-size:3rem;margin-bottom:1rem;">🎉</div>
    <h2 style="color:#fff;font-size:2.2rem;margin-bottom:1rem;">Pronto para encomendar?</h2>
    <p style="opacity:.85;margin-bottom:2rem;">Faça seu pedido com antecedência e garanta salgados e doces fresquinhos para sua festa!</p>
    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
      <a href="pages/salgados.php" class="btn btn-white btn-lg"><i class="bi bi-egg-fried"></i> Ver Salgados</a>
      <a href="pages/doces.php" class="btn btn-lg" style="border:2px solid rgba(255,255,255,.5);color:#fff;background:transparent;">
        <i class="bi bi-cup-hot"></i> Ver Doces
      </a>
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
          <li><a href="index.php">Início</a></li>
          <li><a href="pages/salgados.php">Salgados</a></li>
          <li><a href="pages/doces.php">Doces</a></li>
          <li><a href="pages/pacotes.php">Pacotes</a></li>
          <li><a href="pages/carrinho.php">Carrinho</a></li>
        </ul>
      </div>
      <div>
        <h6>Contato</h6>
        <ul class="footer-links">
          <li><a href="#"><i class="bi bi-telephone"></i> (11) 99999-9999</a></li>
          <li><a href="#"><i class="bi bi-envelope"></i> contato@docesalgado.com.br</a></li>
          <li><a href="#"><i class="bi bi-geo-alt"></i> São Paulo, SP</a></li>
          <li style="color:rgba(255,255,255,.4);font-size:.82rem;margin-top:.5rem;">
            <i class="bi bi-clock"></i> Seg–Sáb: 8h–18h
          </li>
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
<script src="js/app.js"></script>
</body>
</html>