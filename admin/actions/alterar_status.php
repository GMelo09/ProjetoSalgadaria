<?php
/* =============================================================
 *  admin/actions/alterar_status.php — Altera status do pedido
 * ============================================================= */
require_once __DIR__ . '/../../includes/auth.php';
sessionStart();
requireAdmin();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../classes/Pedido.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método inválido.']);
    exit;
}

$pedidoId  = filter_input(INPUT_POST, 'pedido_id', FILTER_VALIDATE_INT);
$novoStatus = trim($_POST['status'] ?? '');

if (!$pedidoId || !$novoStatus) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados inválidos.']);
    exit;
}

try {
    $pedidoObj = new Pedido();
    $ok = $pedidoObj->alterarStatusPedido($pedidoId, $novoStatus);
    echo json_encode(['sucesso' => $ok, 'mensagem' => $ok ? 'Status atualizado.' : 'Status inválido.']);
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro interno.']);
}
