<?php
/* =============================================================
 *  admin/actions/produto_excluir.php — Desativa produto (soft-delete)
 * ============================================================= */
require_once __DIR__ . '/../../includes/auth.php';
sessionStart();
requireAdmin();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Produto.php';

header('Content-Type: application/json');

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido.']);
    exit;
}

try {
    $produtoObj = new Produto();
    $ok = $produtoObj->excluirProduto($id);
    echo json_encode(['sucesso' => $ok]);
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro interno.']);
}