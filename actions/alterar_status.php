<?php

require_once __DIR__ . '/../includes/auth.php';
sessionStart();
requireAdminAjax();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método inválido.']);
    exit;
}

require_once __DIR__ . '/../classes/pedido_class.php';

$pedido_id = filter_input(INPUT_POST, 'pedido_id', FILTER_VALIDATE_INT);
$status    = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_SPECIAL_CHARS);

$statusValidos = ['pendente', 'confirmado', 'producao', 'entregue', 'cancelado'];

if (!$pedido_id || $pedido_id <= 0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID de pedido inválido.']);
    exit;
}

if (!in_array($status, $statusValidos, true)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Status inválido.']);
    exit;
}

$pedido = new Pedido();
$ok     = $pedido->AtualizarStatus($pedido_id, $status);

echo json_encode([
    'sucesso'  => $ok > 0,
    'mensagem' => $ok > 0 ? 'Status atualizado.' : 'Pedido não encontrado.',
]);
exit;