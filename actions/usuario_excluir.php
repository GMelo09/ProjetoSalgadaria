<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
sessionStart();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('../admin/dashboard.php');
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
    redirectTo('../admin/dashboard.php');
}

// ── 4. Impede auto-exclusão ───────────────────────────────────
// Um admin não pode se excluir enquanto está logado
if ((int) $id === (int) $_SESSION['usuario_id']) {
    setFlash('erro', 'Você não pode excluir sua própria conta.');
    redirectTo('../admin/dashboard.php');
}

// ── 5. Executa a exclusão ────────────────────────────────────
$usuario  = new Usuario();
$excluido = $usuario->Excluir($id);

if ($excluido > 0) {
    setFlash('sucesso', 'Usuário excluído com sucesso!');
} else {
    setFlash('erro', 'Usuário não encontrado ou já foi excluído.');
}

redirectTo('../admin/dashboard.php');
