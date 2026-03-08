<?php
// Active page helper
$currentPage = $currentPage ?? '';
function navClass($page, $current) {
    return $page === $current ? ' active' : '';
}
?>

<!-- ── Navbar ── -->
<header>
  <nav class="navbar-main">
    <div class="container">
      <!-- Brand -->
      <a href="<?= $base ?? '' ?>/index.php" class="nav-brand">
        <i class="bi bi-cake2"></i>
        Doce<span>&amp;</span>Salgado
      </a>

      <!-- Desktop links -->
      <ul class="nav-links" id="navLinks">
        <li><a href="<?= $base ?? '' ?>/index.php" class="<?= navClass('home', $currentPage) ?>">
          <i class="bi bi-house"></i> Início
        </a></li>
        <li><a href="<?= $base ?? '' ?>/pages/salgados.php" class="<?= navClass('salgados', $currentPage) ?>">
          <i class="bi bi-egg-fried"></i> Salgados
        </a></li>
        <li><a href="<?= $base ?? '' ?>/pages/doces.php" class="<?= navClass('doces', $currentPage) ?>">
          <i class="bi bi-cup-hot"></i> Doces
        </a></li>
        <li><a href="<?= $base ?? '' ?>/pages/pacotes.php" class="<?= navClass('pacotes', $currentPage) ?>">
          <i class="bi bi-box-seam"></i> Pacotes
        </a></li>
        <li>
          <a href="<?= $base ?? '' ?>/pages/carrinho.php" class="nav-cart-btn<?= navClass('carrinho', $currentPage) ?>">
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
              <li><a class="dropdown-item" href="<?= $base ?? '' ?>/admin/dashboard.php">
                <i class="bi bi-speedometer2"></i> Painel Admin
              </a></li>
              <li><hr class="dropdown-divider"></li>
              <?php endif; ?>
              <li><a class="dropdown-item" href="<?= $base ?? '' ?>/actions/sair.php">
                <i class="bi bi-box-arrow-right"></i> Sair
              </a></li>
            </ul>
          </li>
        <?php else: ?>
          <li><a href="<?= $base ?? '' ?>/pages/login.php" class="<?= navClass('login', $currentPage) ?>">
            <i class="bi bi-person"></i> Entrar
          </a></li>
        <?php endif; ?>
      </ul>

      <!-- Mobile toggle -->
      <button class="navbar-toggler-main" id="navToggler" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </nav>
</header>