<?php
/**
 * actions/usuario_excluir.php
 *
 * Correções aplicadas:
 *  1. Validação CSRF adicionada
 *  2. Verificação de autenticação e autorização (somente admin)
 *  3. exit adicionado no redirect de ID inválido (estava faltando)
 *  4. Proteção: admin não pode excluir a si mesmo
 */

require_once __DIR__ . '/../includes/auth.php';
sessionStart();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../dashboard/index.php');
    exit;
}

// ── 1. Somente admin pode excluir usuários ───────────────────
requireAdmin();

// ── 2. Validação CSRF ────────────────────────────────────────
csrfValidar();

require_once __DIR__ . '/../classes/usuario_class.php';

// ── 3. Validação do ID ───────────────────────────────────────
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    setFlash('erro', 'ID inválido.');
    header('Location: ../dashboard/index.php');
    exit; // estava faltando — sem exit o código continuava rodando
}

// ── 4. Impede auto-exclusão ───────────────────────────────────
// Um admin não pode se excluir enquanto está logado
if ((int) $id === (int) $_SESSION['usuario_id']) {
    setFlash('erro', 'Você não pode excluir sua própria conta.');
    header('Location: ../dashboard/index.php');
    exit;
}

// ── 5. Executa a exclusão ────────────────────────────────────
$usuario  = new Usuario();
$excluido = $usuario->Excluir($id);

if ($excluido > 0) {
    setFlash('sucesso', 'Usuário excluído com sucesso!');
} else {
    setFlash('erro', 'Usuário não encontrado ou já foi excluído.');
}

header('Location: ../dashboard/index.php');
exit;