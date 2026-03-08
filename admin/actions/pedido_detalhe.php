<?php
/* =============================================================
 *  admin/actions/pedido_detalhe.php — Retorna HTML do pedido (AJAX)
 * ============================================================= */
require_once __DIR__ . '/../../includes/auth.php';
sessionStart();
requireAdmin();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Pedido.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { echo '<p class="text-danger">ID inválido.</p>'; exit; }

$pedidoObj    = new Pedido();
$pedido       = $pedidoObj->buscarPedido($id);
$statusLabels = Pedido::STATUS;
$statusBadge  = [
    'pendente'     => 'badge-warning',
    'confirmado'   => 'badge-info',
    'em_preparo'   => 'badge-primary',
    'saiu_entrega' => 'badge-secondary',
    'entregue'     => 'badge-success',
    'cancelado'    => 'badge-danger',
];

if (!$pedido) { echo '<p class="text-danger">Pedido não encontrado.</p>'; exit; }
?>
<div class="pedido-detalhe">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h5 style="margin:0;">Pedido #<?= (int)$pedido['id'] ?></h5>
    <span class="badge-status <?= $statusBadge[$pedido['status']] ?? 'badge-secondary' ?>">
      <?= htmlspecialchars($statusLabels[$pedido['status']] ?? $pedido['status']) ?>
    </span>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-sm-6">
      <label class="form-label" style="font-size:.75rem;color:var(--muted);text-transform:uppercase;">Cliente</label>
      <p style="margin:0;font-weight:600;"><?= htmlspecialchars($pedido['nome']) ?></p>
    </div>
    <div class="col-sm-6">
      <label class="form-label" style="font-size:.75rem;color:var(--muted);text-transform:uppercase;">Telefone</label>
      <p style="margin:0;"><?= htmlspecialchars($pedido['telefone']) ?></p>
    </div>
    <div class="col-sm-12">
      <label class="form-label" style="font-size:.75rem;color:var(--muted);text-transform:uppercase;">Endereço</label>
      <p style="margin:0;"><?= htmlspecialchars($pedido['endereco']) ?></p>
    </div>
    <div class="col-sm-4">
      <label class="form-label" style="font-size:.75rem;color:var(--muted);text-transform:uppercase;">Entrega</label>
      <p style="margin:0;"><?= date('d/m/Y', strtotime($pedido['data_entrega'])) ?></p>
    </div>
    <div class="col-sm-4">
      <label class="form-label" style="font-size:.75rem;color:var(--muted);text-transform:uppercase;">Pagamento</label>
      <p style="margin:0;"><?= ucfirst(htmlspecialchars($pedido['forma_pagamento'])) ?></p>
    </div>
    <div class="col-sm-4">
      <label class="form-label" style="font-size:.75rem;color:var(--muted);text-transform:uppercase;">Criado em</label>
      <p style="margin:0;"><?= date('d/m/Y H:i', strtotime($pedido['criado_em'])) ?></p>
    </div>
    <?php if ($pedido['obs']): ?>
    <div class="col-sm-12">
      <label class="form-label" style="font-size:.75rem;color:var(--muted);text-transform:uppercase;">Observação</label>
      <p style="margin:0;"><?= htmlspecialchars($pedido['obs']) ?></p>
    </div>
    <?php endif; ?>
  </div>

  <hr>
  <h6>Itens do Pedido</h6>
  <table class="admin-table" style="margin-bottom:1rem;">
    <thead>
      <tr><th>Produto</th><th>Qtd</th><th>Preço Unit.</th><th>Subtotal</th></tr>
    </thead>
    <tbody>
      <?php foreach ($pedido['itens'] as $item): ?>
      <tr>
        <td><?= htmlspecialchars($item['nome']) ?></td>
        <td><?= (int)$item['quantidade'] ?></td>
        <td>R$ <?= number_format((float)$item['preco_unit'], 2, ',', '.') ?></td>
        <td>R$ <?= number_format((float)$item['subtotal'], 2, ',', '.') ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
    <tfoot>
      <tr>
        <td colspan="3" style="text-align:right;font-weight:700;">Total</td>
        <td style="font-weight:700;color:var(--rose);">
          R$ <?= number_format((float)$pedido['total'], 2, ',', '.') ?>
        </td>
      </tr>
    </tfoot>
  </table>
</div>