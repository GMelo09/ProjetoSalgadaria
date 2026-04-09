<?php


require_once __DIR__ . '/../includes/auth.php';
sessionStart();
requireAdmin();

require_once __DIR__ . '/../classes/pedido_class.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    echo '<p class="text-danger"><i class="bi bi-x-circle"></i> ID de pedido inválido.</p>';
    exit;
}

$pedidoObj = new Pedido();
$pedido    = $pedidoObj->BuscarPorId($id);
$itens     = $pedidoObj->ListarItens($id);

if (!$pedido) {
    echo '<p class="text-danger"><i class="bi bi-x-circle"></i> Pedido não encontrado.</p>';
    exit;
}

$statusLabels = [
    'pendente'   => 'Pendente',
    'confirmado' => 'Confirmado',
    'producao'   => 'Em Produção',
    'entregue'   => 'Entregue',
    'cancelado'  => 'Cancelado',
];
$statusBadge = [
    'pendente'   => 'badge-warning',
    'confirmado' => 'badge-info',
    'producao'   => 'badge-primary',
    'entregue'   => 'badge-success',
    'cancelado'  => 'badge-danger',   
];
$iconesPag = ['pix' => 'bi-qr-code', 'dinheiro' => 'bi-cash', 'cartao' => 'bi-credit-card'];
?>
<div style="display:flex;flex-direction:column;gap:1.25rem;">

  <!-- Cabeçalho -->
  <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:.75rem;">
    <div>
      <h5 style="margin:0;font-weight:700;color:var(--rose);">#<?= (int)$pedido['id'] ?></h5>
      <small style="color:var(--muted);">
        Criado em <?= date('d/m/Y H:i', strtotime($pedido['criado_em'])) ?>
      </small>
    </div>
    <span class="badge-status <?= $statusBadge[$pedido['status']] ?? 'badge-secondary' ?>" style="font-size:.85rem;padding:.35rem .85rem;">
      <?= htmlspecialchars($statusLabels[$pedido['status']] ?? $pedido['status']) ?>
    </span>
  </div>

  <!-- Dados do cliente -->
  <div style="background:var(--cream);border-radius:var(--radius);padding:1rem 1.25rem;">
    <h6 style="font-size:.8rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:.75rem;">
      <i class="bi bi-person"></i> Dados do Cliente
    </h6>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:.5rem .75rem;font-size:.88rem;">
      <div><strong>Nome:</strong> <?= htmlspecialchars($pedido['nome_cliente']) ?></div>
      <div><strong>Telefone:</strong> <?= htmlspecialchars($pedido['telefone']) ?></div>
      <div style="grid-column:1/-1;"><strong>Endereço:</strong> <?= htmlspecialchars($pedido['endereco']) ?></div>
      <?php if (!empty($pedido['observacao'])): ?>
        <div style="grid-column:1/-1;"><strong>Obs:</strong> <?= htmlspecialchars($pedido['observacao']) ?></div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Entrega e Pagamento -->
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
    <div style="background:var(--cream);border-radius:var(--radius);padding:1rem 1.25rem;font-size:.88rem;">
      <div style="font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:.4rem;">
        <i class="bi bi-calendar2-event"></i> Entrega
      </div>
      <strong><?= date('d/m/Y', strtotime($pedido['data_entrega'])) ?></strong>
    </div>
    <div style="background:var(--cream);border-radius:var(--radius);padding:1rem 1.25rem;font-size:.88rem;">
      <div style="font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:.4rem;">
        <i class="bi bi-credit-card"></i> Pagamento
      </div>
      <strong>
        <i class="bi <?= $iconesPag[$pedido['forma_pagamento']] ?? 'bi-credit-card' ?>"></i>
        <?= ucfirst(htmlspecialchars($pedido['forma_pagamento'])) ?>
      </strong>
    </div>
  </div>

  <!-- Itens do pedido -->
  <div>
    <h6 style="font-size:.8rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:.75rem;">
      <i class="bi bi-bag"></i> Itens do Pedido
    </h6>
    <?php if (!empty($itens)): ?>
      <table style="width:100%;border-collapse:collapse;font-size:.88rem;">
        <thead>
          <tr style="border-bottom:2px solid var(--cream-dark);">
            <th style="text-align:left;padding:.4rem .5rem;color:var(--muted);font-weight:600;">Produto</th>
            <th style="text-align:center;padding:.4rem .5rem;color:var(--muted);font-weight:600;">Qtd</th>
            <th style="text-align:right;padding:.4rem .5rem;color:var(--muted);font-weight:600;">Unit.</th>
            <th style="text-align:right;padding:.4rem .5rem;color:var(--muted);font-weight:600;">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($itens as $item): ?>
            <tr style="border-bottom:1px solid var(--cream);">
              <td style="padding:.5rem .5rem;">
                <?= htmlspecialchars($item['nome_produto']) ?>
              </td>
              <td style="text-align:center;padding:.5rem .5rem;"><?= (int)$item['quantidade'] ?></td>
              <td style="text-align:right;padding:.5rem .5rem;">R$ <?= number_format((float)$item['preco_unitario'], 2, ',', '.') ?></td>
              <td style="text-align:right;padding:.5rem .5rem;font-weight:600;">
                R$ <?= number_format((float)$item['preco_unitario'] * (int)$item['quantidade'], 2, ',', '.') ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="3" style="text-align:right;padding:.75rem .5rem;font-weight:700;font-size:1rem;">Total:</td>
            <td style="text-align:right;padding:.75rem .5rem;font-weight:700;font-size:1rem;color:var(--rose);">
              R$ <?= number_format((float)$pedido['total'], 2, ',', '.') ?>
            </td>
          </tr>
        </tfoot>
      </table>
    <?php else: ?>
      <p style="color:var(--muted);font-size:.88rem;">Nenhum item registrado para este pedido.</p>
    <?php endif; ?>
  </div>

</div>