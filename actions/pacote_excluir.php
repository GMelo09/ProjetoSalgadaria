<?php
require_once __DIR__ . '/../includes/auth.php';
sessionStart();
requireAdminAjax();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método inválido.']);
    exit;
}

require_once __DIR__ . '/../classes/pacote_class.php';

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido.']);
    exit;
}

$pacote = new Pacote();
$ok     = $pacote->Excluir($id);

echo json_encode([
    'sucesso'  => $ok > 0,
    'mensagem' => $ok > 0 ? 'Pacote desativado.' : 'Pacote não encontrado.',
]);
exit;