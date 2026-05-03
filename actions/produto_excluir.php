<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
sessionStart();
requireAdminAjax();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método inválido.']);
    exit;
}

csrfValidar();

require_once __DIR__ . '/../classes/produto_class.php';

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido.']);
    exit;
}

$produto = new Produto();
$ok      = $produto->Excluir($id); // soft delete — seta ativo = 0

echo json_encode([
    'sucesso'  => $ok > 0,
    'mensagem' => $ok > 0 ? 'Produto desativado.' : 'Produto não encontrado.',
]);
exit;
