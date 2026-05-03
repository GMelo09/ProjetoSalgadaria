<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../classes/pedido_class.php';
sessionStart();
requireLogin('pages/login.php?erro=login_obrigatorio');

$pedidoObj = new Pedido();
$pedidos = $pedidoObj->ListarPorUsuario((int) ($_SESSION['usuario_id'] ?? 0));
$pedidoEmDestaque = filter_input(INPUT_GET, 'pedido', FILTER_VALIDATE_INT) ?: 0;

$statusLabels = [
  'pendente'   => 'Pendente',
  'confirmado' => 'Confirmado',
  'producao'   => 'Em Produção',
  'entregue'   => 'Entregue',
  'cancelado'  => 'Cancelado',
];
$statusClasses = [
  'pendente'   => 'status-pendente',
  'confirmado' => 'status-confirmado',
  'producao'   => 'status-em_preparo',
  'entregue'   => 'status-entregue',
  'cancelado'  => 'status-cancelado',
];
$paymentLabels = [
  'pix'      => 'PIX',
  'dinheiro' => 'Dinheiro',
  'cartao'   => 'Cartão',
];
$progressPercent = [
  'pendente'   => 22,
  'confirmado' => 52,
  'producao'   => 78,
  'entregue'   => 100,
  'cancelado'  => 100,
];
$progressTexts = [
  'pendente'   => 'Pedido recebido e aguardando confirmação.',
  'confirmado' => 'Pedido confirmado e em organização.',
  'producao'   => 'Sua encomenda está em produção.',
  'entregue'   => 'Pedido concluído com sucesso.',
  'cancelado'  => 'Pedido cancelado.',
];

$pedidosComItens = [];
$totalGasto = 0.0;
$proximaEntrega = null;

foreach ($pedidos as $pedido) {
  $pedidoId = (int) ($pedido['id'] ?? 0);
  $itens = $pedidoObj->ListarItens($pedidoId);
  $subtotalItens = 0.0;

  foreach ($itens as $item) {
    $subtotalItens += (float) ($item['preco_unitario'] ?? 0) * (int) ($item['quantidade'] ?? 0);
  }

  $pedido['subtotal_itens'] = round($subtotalItens, 2);
  $pedidosComItens[] = [
    'pedido' => $pedido,
    'itens' => $itens,
  ];

  $totalGasto += (float) ($pedido['total'] ?? 0);

  $statusAtual = $pedido['status'] ?? 'pendente';
  if (!in_array($statusAtual, ['entregue', 'cancelado'], true)) {
    if (
      $proximaEntrega === null
      || strtotime(($pedido['data_entrega'] ?? '') . ' ' . ($pedido['horario_entrega'] ?? '00:00:00')) <
         strtotime(($proximaEntrega['data_entrega'] ?? '') . ' ' . ($proximaEntrega['horario_entrega'] ?? '00:00:00'))
    ) {
      $proximaEntrega = $pedido;
    }
  }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Meus Pedidos | Doce &amp; Salgado</title>
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
        <li class="dropdown">
          <a href="#" data-bs-toggle="dropdown" role="button">
            <i class="bi bi-person-circle"></i> <?= htmlspecialchars($_SESSION['usuario_nome'] ?? '') ?>
            <i class="bi bi-chevron-down" style="font-size:.65rem;"></i>
          </a>
          <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item active" href="meus_pedidos.php"><i class="bi bi-bag-heart"></i> Meus Pedidos</a></li>
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
      </ul>
      <button class="navbar-toggler-main" id="navToggler" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
    </div>
  </nav>
</header>

<div class="page-header" style="background:linear-gradient(135deg,var(--rose-dark),var(--rose),#ad1457);">
  <div class="container">
    <div class="breadcrumb">
      <a href="../index.php">Início</a><span>/</span><span>Meus Pedidos</span>
    </div>
    <h1>Meus Pedidos</h1>
    <p>Acompanhe status, entrega e todo o histórico das suas encomendas.</p>
  </div>
</div>

<section class="section">
  <div class="container">
    <div class="orders-overview">
      <div class="orders-overview-card">
        <span class="orders-overview-label">Pedidos realizados</span>
        <strong><?= count($pedidosComItens) ?></strong>
        <small>Histórico completo na sua conta</small>
      </div>
      <div class="orders-overview-card">
        <span class="orders-overview-label">Total investido</span>
        <strong>R$ <?= number_format($totalGasto, 2, ',', '.') ?></strong>
        <small>Soma dos pedidos já registrados</small>
      </div>
      <div class="orders-overview-card">
        <span class="orders-overview-label">Próxima entrega</span>
        <?php if ($proximaEntrega): ?>
          <strong><?= date('d/m/Y', strtotime($proximaEntrega['data_entrega'])) ?></strong>
          <small>
            <?= !empty($proximaEntrega['horario_entrega']) ? date('H:i', strtotime($proximaEntrega['horario_entrega'])) . ' • ' : '' ?>
            <?= htmlspecialchars($statusLabels[$proximaEntrega['status']] ?? ucfirst((string) $proximaEntrega['status'])) ?>
          </small>
        <?php else: ?>
          <strong>Nenhuma pendente</strong>
          <small>Seus pedidos concluídos aparecem logo abaixo</small>
        <?php endif; ?>
      </div>
    </div>

    <?php if (empty($pedidosComItens)): ?>
      <div class="orders-empty-state">
        <div class="orders-empty-icon"><i class="bi bi-bag-heart"></i></div>
        <h2>Você ainda não fez nenhum pedido</h2>
        <p>Quando sua primeira encomenda for criada, ela aparecerá aqui com status, entrega e detalhes.</p>
        <div class="cart-empty-actions">
          <a href="salgados.php" class="btn btn-primary btn-lg"><i class="bi bi-egg-fried"></i> Ver Salgados</a>
          <a href="pacotes.php" class="btn btn-outline btn-lg"><i class="bi bi-box-seam"></i> Montar Pacote</a>
        </div>
      </div>
    <?php else: ?>
      <div class="orders-list">
        <?php foreach ($pedidosComItens as $registro): ?>
          <?php
          $pedido = $registro['pedido'];
          $itens = $registro['itens'];
          $pedidoId = (int) ($pedido['id'] ?? 0);
          $status = $pedido['status'] ?? 'pendente';
          $taxaEntrega = (float) ($pedido['taxa_entrega'] ?? 0);
          $isHighlighted = $pedidoEmDestaque === $pedidoId;
          ?>
          <article class="order-card<?= $isHighlighted ? ' order-card-highlight' : '' ?>" id="pedido-<?= $pedidoId ?>">
            <div class="order-card-head">
              <div>
                <span class="order-eyebrow">Pedido #<?= $pedidoId ?></span>
                <h2>Entrega para <?= date('d/m/Y', strtotime($pedido['data_entrega'])) ?></h2>
                <p>
                  Criado em <?= date('d/m/Y \à\s H:i', strtotime($pedido['criado_em'])) ?>
                  <?php if (!empty($pedido['horario_entrega'])): ?>
                    • horário escolhido: <?= date('H:i', strtotime($pedido['horario_entrega'])) ?>
                  <?php endif; ?>
                </p>
              </div>
              <span class="badge-status <?= $statusClasses[$status] ?? 'status-confirmado' ?>">
                <?= htmlspecialchars($statusLabels[$status] ?? ucfirst((string) $status)) ?>
              </span>
            </div>

            <div class="order-progress">
              <div class="order-progress-track<?= $status === 'cancelado' ? ' cancelled' : '' ?>">
                <span style="width:<?= (int) ($progressPercent[$status] ?? 20) ?>%;"></span>
              </div>
              <small><?= htmlspecialchars($progressTexts[$status] ?? 'Acompanhe a evolução do seu pedido.') ?></small>
            </div>

            <div class="order-meta-grid">
              <div class="order-meta-card">
                <span class="order-meta-label">Entrega</span>
                <strong>
                  <?= date('d/m/Y', strtotime($pedido['data_entrega'])) ?>
                  <?php if (!empty($pedido['horario_entrega'])): ?>
                    às <?= date('H:i', strtotime($pedido['horario_entrega'])) ?>
                  <?php endif; ?>
                </strong>
              </div>
              <div class="order-meta-card">
                <span class="order-meta-label">Pagamento</span>
                <strong><?= htmlspecialchars($paymentLabels[$pedido['forma_pagamento']] ?? ucfirst((string) ($pedido['forma_pagamento'] ?? ''))) ?></strong>
              </div>
              <div class="order-meta-card">
                <span class="order-meta-label">Área / CEP</span>
                <strong>
                  <?= htmlspecialchars($pedido['area_entrega'] ?? 'Entrega padrão') ?>
                  <?php if (!empty($pedido['cep_entrega'])): ?>
                    • <?= htmlspecialchars($pedido['cep_entrega']) ?>
                  <?php endif; ?>
                </strong>
              </div>
              <div class="order-meta-card">
                <span class="order-meta-label">Total</span>
                <strong>R$ <?= number_format((float) ($pedido['total'] ?? 0), 2, ',', '.') ?></strong>
              </div>
            </div>

            <div class="order-address">
              <i class="bi bi-geo-alt-fill"></i>
              <div>
                <strong>Endereço de entrega</strong>
                <span><?= htmlspecialchars($pedido['endereco'] ?? '') ?></span>
              </div>
            </div>

            <?php if (!empty($pedido['observacao'])): ?>
              <div class="order-note">
                <i class="bi bi-chat-left-text"></i>
                <span><?= htmlspecialchars($pedido['observacao']) ?></span>
              </div>
            <?php endif; ?>

            <div class="order-items-box">
              <div class="order-items-title">
                <span>Itens do pedido</span>
                <small><?= count($itens) ?> item(ns)</small>
              </div>

              <?php foreach ($itens as $item): ?>
                <div class="order-item-row">
                  <span><?= (int) ($item['quantidade'] ?? 0) ?>x <?= htmlspecialchars($item['nome_produto'] ?? '') ?></span>
                  <strong>R$ <?= number_format((float) ($item['preco_unitario'] ?? 0) * (int) ($item['quantidade'] ?? 0), 2, ',', '.') ?></strong>
                </div>
              <?php endforeach; ?>

              <div class="order-total-breakdown">
                <div class="order-item-row">
                  <span>Subtotal dos itens</span>
                  <strong>R$ <?= number_format((float) ($pedido['subtotal_itens'] ?? 0), 2, ',', '.') ?></strong>
                </div>
                <div class="order-item-row">
                  <span>Taxa de entrega</span>
                  <strong>R$ <?= number_format($taxaEntrega, 2, ',', '.') ?></strong>
                </div>
                <div class="order-item-row is-total">
                  <span>Total final</span>
                  <strong>R$ <?= number_format((float) ($pedido['total'] ?? 0), 2, ',', '.') ?></strong>
                </div>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <div class="footer-brand"><i class="bi bi-cake2"></i> Doce<span>&amp;</span>Salgado</div>
        <p>Salgados crocantes e doces irresistíveis, feitos com amor artesanal para tornar sua festa inesquecível.</p>
        <div class="footer-social">
          <a href="#"><i class="bi bi-instagram"></i></a>
          <a href="#"><i class="bi bi-whatsapp"></i></a>
          <a href="#"><i class="bi bi-facebook"></i></a>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="../js/app.js"></script>
</body>
</html>
