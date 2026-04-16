<?php
require_once __DIR__ . '/../includes/auth.php';
sessionStart();

$pedido_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$pedido_id) {
    header('Location: ../index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pedido Confirmado | Doce &amp; Salgado</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <link href="../css/style.css" rel="stylesheet">
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
              <li><a class="dropdown-item" href="meus_pedidos.php"><i class="bi bi-bag-heart"></i> Meus Pedidos</a></li>
              <?php if (!empty($_SESSION['eh_admin'])): ?>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="../admin/dashboard.php"><i class="bi bi-speedometer2"></i> Painel Admin</a></li>
              <?php endif; ?>
              <li><hr class="dropdown-divider"></li>
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

<section class="section" style="text-align:center;padding:5rem 1rem;">
  <div class="container-sm">
    <div style="font-size:4rem;margin-bottom:1.5rem;">🎉</div>
    <h1 style="font-family:var(--font-serif);margin-bottom:1rem;">Pedido Recebido!</h1>
    <p style="color:var(--muted);font-size:1.05rem;margin-bottom:.5rem;">
      Seu pedido <strong style="color:var(--rose);">#<?= $pedido_id ?></strong> foi registrado com sucesso.
    </p>
    <p style="color:var(--muted);margin-bottom:2.5rem;">
      Em breve entraremos em contato pelo WhatsApp para confirmar os detalhes da entrega.
    </p>
    <div style="display:flex;gap:1rem;justify-content:center;flex-wrap:wrap;">
      <a href="meus_pedidos.php?pedido=<?= $pedido_id ?>#pedido-<?= $pedido_id ?>" class="btn btn-outline btn-lg">
        <i class="bi bi-bag-heart"></i> Acompanhar Pedido
      </a>
      <a href="../index.php" class="btn btn-primary btn-lg">
        <i class="bi bi-house"></i> Voltar ao Início
      </a>
      <a href="salgados.php" class="btn btn-outline btn-lg">
        <i class="bi bi-bag-plus"></i> Continuar Comprando
      </a>
    </div>
  </div>
</section>

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
<script>Cart.clear();</script>
</body>
</html>
