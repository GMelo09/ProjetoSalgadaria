<?php
/* =============================================================
 *  admin/actions/cliente_bloquear.php — Bloqueia/desbloqueia cliente
 * ============================================================= */
require_once __DIR__ . '/../../includes/auth.php';
sessionStart();
requireAdmin();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Usuario.php';

header('Content-Type: application/json');

$id        = filter_input(INPUT_POST, 'id',        FILTER_VALIDATE_INT);
$bloqueado = filter_input(INPUT_POST, 'bloqueado', FILTER_VALIDATE_INT);

if ($id === false || $id === null) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'ID inválido.']);
    exit;
}

/* Impede que admin bloqueie a si mesmo */
if ($id === (int) $_SESSION['usuario_id']) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Você não pode bloquear sua própria conta.']);
    exit;
}

try {
    $usuarioObj = new Usuario();
    $ok = $usuarioObj->alterarBloqueio($id, (int) $bloqueado);
    echo json_encode(['sucesso' => $ok]);
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro interno.']);
}