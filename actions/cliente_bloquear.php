<?php

require_once __DIR__ . '/../includes/auth.php';
sessionStart();
requireAdminAjax();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método inválido.']);
    exit;
}

require_once __DIR__ . '/../classes/usuario_class.php';

$id       = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$bloquear = filter_input(INPUT_POST, 'bloqueado', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido.']);
    exit;
}

// Admin não pode se bloquear
if ((int) $id === (int) $_SESSION['usuario_id']) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Você não pode bloquear sua própria conta.']);
    exit;
}

$usuario = new Usuario();
$ok = $usuario->AlterarBloqueio($id, (bool) $bloquear);

echo json_encode([
    'sucesso'  => $ok > 0,
    'mensagem' => $ok > 0 ? 'Operação realizada.' : 'Usuário não encontrado.',
]);
exit;