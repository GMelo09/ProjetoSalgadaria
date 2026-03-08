<?php
require_once __DIR__ . '/../includes/auth.php';
sessionStart();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Finalizar Pedido | Doce &amp; Salgado</title>
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

<!-- ═══════════════ PROGRESS ═══════════════ -->
<div class="page-header" style="background:linear-gradient(135deg,var(--rose-dark),var(--rose));">
  <div class="container">
    <div class="breadcrumb">
      <a href="../index.php">Início</a>
      <span>/</span>
      <a href="carrinho.php">Carrinho</a>
      <span>/</span>
      <span>Finalizar Pedido</span>
    </div>
    <h1>✅ Finalizar Pedido</h1>
    <p>Você está quase lá! Preencha os dados de entrega.</p>
  </div>
</div>

<!-- Progress bar -->
<div style="background:var(--white);border-bottom:1px solid var(--cream-dark);padding:.75rem 0;">
  <div class="container">
    <div style="display:flex;align-items:center;gap:.5rem;font-size:.82rem;font-weight:600;">
      <span style="color:var(--muted);">🛒 Carrinho</span>
      <span style="color:var(--muted);">→</span>
      <span style="color:var(--rose);">📋 Dados</span>
      <span style="color:var(--muted);">→</span>
      <span style="color:var(--muted);">✅ Confirmação</span>
    </div>
  </div>
</div>

<!-- ═══════════════ FORM + SUMMARY ═══════════════ -->
<section class="section">
  <div class="container">
    <div id="checkoutLayout">

      <!-- Form -->
      <div class="checkout-form">
        <h4><i class="bi bi-person-lines-fill"></i> Dados para Entrega</h4>
        <form id="checkoutForm" action="../actions/pedido_cadastrar.php" method="POST">
          <input type="hidden" id="itensInput" name="itens_carrinho">

          <?php if (!empty($_SESSION['usuario_id'])): ?>
          <input type="hidden" name="usuario_id" value="<?= (int) $_SESSION['usuario_id'] ?>">
          <?php endif; ?>

          <div class="form-group">
            <label class="form-label">Nome Completo <span class="required">*</span></label>
            <input type="text" class="form-control" name="nome" required maxlength="100"
                   placeholder="Seu nome completo"
                   value="<?= htmlspecialchars($_SESSION['usuario_nome'] ?? '') ?>">
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Telefone / WhatsApp <span class="required">*</span></label>
              <input type="tel" class="form-control" name="telefone" id="telInput" required
                     maxlength="20" placeholder="(11) 99999-9999">
            </div>
            <div class="form-group">
              <label class="form-label">E-mail (opcional)</label>
              <input type="email" class="form-control" name="email" maxlength="150" placeholder="seu@email.com">
            </div>
          </div>

          <div class="form-group">
            <label class="form-label">Endereço Completo <span class="required">*</span></label>
            <textarea class="form-control" name="endereco" rows="3" required maxlength="500"
                      placeholder="Rua, número, bairro, complemento, cidade/UF"></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Data de Entrega <span class="required">*</span></label>
              <input type="date" class="form-control" name="data_entrega" required
                     min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Observações</label>
              <input type="text" class="form-control" name="obs" maxlength="300"
                     placeholder="Alguma instrução especial?">
            </div>
          </div>

          <hr class="divider">
          <h4 style="font-size:1.1rem;margin-bottom:1rem;">
            <i class="bi bi-credit-card"></i> Forma de Pagamento <span class="required">*</span>
          </h4>
          <div class="payment-options">
            <label class="payment-option" for="payPix">
              <input type="radio" name="forma_pagamento" id="payPix" value="pix" required>
              <div class="payment-icon">💳</div>
              <div class="payment-label">PIX</div>
            </label>
            <label class="payment-option" for="payDinheiro">
              <input type="radio" name="forma_pagamento" id="payDinheiro" value="dinheiro">
              <div class="payment-icon">💵</div>
              <div class="payment-label">Dinheiro</div>
            </label>
            <label class="payment-option" for="payCartao">
              <input type="radio" name="forma_pagamento" id="payCartao" value="cartao">
              <div class="payment-icon">💳</div>
              <div class="payment-label">Cartão</div>
            </label>
          </div>

          <div id="pixInfo" style="display:none;margin-top:1rem;" class="info-bar">
            <i class="bi bi-qr-code"></i>
            Chave PIX: <strong>(11) 99999-9999</strong> — enviaremos o comprovante por WhatsApp
          </div>

          <button type="submit" class="btn btn-primary btn-full btn-lg" style="margin-top:2rem;">
            <i class="bi bi-bag-check"></i> Confirmar Pedido
          </button>
        </form>
      </div>

      <!-- Summary -->
      <div class="cart-summary" id="orderSummary">
        <h4><i class="bi bi-receipt" style="color:var(--rose);"></i> Resumo do Pedido</h4>
        <div id="checkoutItems">
          <p class="text-muted" style="font-size:.85rem;">Carregando itens...</p>
        </div>
        <div class="summary-row total" style="margin-top:1rem;">
          <span>Total</span>
          <span class="amount" id="checkoutTotal">R$ 0,00</span>
        </div>
        <div style="margin-top:1rem;padding:.75rem;background:var(--cream);border-radius:var(--radius-sm);">
          <p style="font-size:.78rem;color:var(--muted);margin:0;line-height:1.8;">
            <i class="bi bi-shield-check" style="color:var(--success);"></i>
            Pedido confirmado por WhatsApp em até 30 minutos.<br>
            <i class="bi bi-truck" style="color:var(--choco-light);"></i>
            Entrega realizada no dia e hora combinados.
          </p>
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
document.addEventListener('DOMContentLoaded', () => {
  const items = Cart.get();
  if (items.length === 0) { window.location.href = 'carrinho.php'; return; }

  // Render summary
  let html = '';
  items.forEach(item => {
    const sub = item.preco * item.quantidade;
    html += `<div class="summary-row">
      <span style="font-size:.85rem;">${item.nome} <small style="color:var(--muted);">×${item.quantidade}</small></span>
      <span style="font-size:.85rem;font-weight:600;">${fmtBRL(sub)}</span>
    </div>`;
  });
  document.getElementById('checkoutItems').innerHTML = html;
  document.getElementById('checkoutTotal').textContent = fmtBRL(Cart.total());

  // Attach cart to form
  document.getElementById('checkoutForm').addEventListener('submit', function() {
    document.getElementById('itensInput').value = JSON.stringify(items);
  });

  // Phone mask
  document.getElementById('telInput').addEventListener('input', function() {
    let v = this.value.replace(/\D/g,'');
    if (v.length > 11) v = v.slice(0,11);
    if (v.length > 6)      v = `(${v.slice(0,2)}) ${v.slice(2,7)}-${v.slice(7)}`;
    else if (v.length > 2) v = `(${v.slice(0,2)}) ${v.slice(2)}`;
    else if (v.length > 0) v = `(${v}`;
    this.value = v;
  });

  // PIX info
  document.querySelectorAll('input[name="forma_pagamento"]').forEach(r => {
    r.addEventListener('change', () => {
      document.getElementById('pixInfo').style.display =
        document.querySelector('input[name="forma_pagamento"]:checked')?.value === 'pix' ? 'flex' : 'none';
    });
  });
});
</script>

<style>
#checkoutLayout {
  display: grid;
  grid-template-columns: 1fr 360px;
  gap: 2rem;
  align-items: start;
}
#orderSummary { position: sticky; top: calc(var(--nav-h) + 1rem); }
@media (max-width: 960px) {
  #checkoutLayout { grid-template-columns: 1fr; }
  #orderSummary { position: static; }
}
</style>
</body>
</html>