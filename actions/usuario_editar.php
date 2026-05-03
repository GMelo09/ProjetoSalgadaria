<?php

declare(strict_types=1);


require_once __DIR__ . '/../includes/auth.php';
sessionStart();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('../admin/dashboard.php');
}

// ── 1. Autenticação ──────────────────────────────────────────
requireLogin('../pages/login.php');

// ── 2. Validação CSRF ────────────────────────────────────────
csrfValidar();

require_once __DIR__ . '/../classes/usuario_class.php';

// ── 3. Sanitização e validação ───────────────────────────────
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    setFlash('erro', 'ID de usuário inválido.');
    redirectTo('../admin/dashboard.php');
}

// ── 4. Autorização: somente admin pode editar outros usuários ─
// Um cliente só pode editar a si mesmo
$ehAdmin       = isAdmin();
$ehProprioUser = ((int) $_SESSION['usuario_id'] === (int) $id);

if (!$ehAdmin && !$ehProprioUser) {
    http_response_code(403);
    setFlash('erro', 'Sem permissão para editar este usuário.');
    redirectTo('../index.php');
}

// ── 5. Prepara o objeto com os dados sanitizados ─────────────
$usuario = new Usuario();

$usuario->id      = $id;
$usuario->nome    = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$usuario->email   = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?? '';
$telefone         = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$usuario->telefone = preg_replace('/\D/', '', $telefone);

// id_tipo: somente admin pode alterar — cliente não pode se autopromover
if ($ehAdmin) {
    $usuario->id_tipo = filter_input(INPUT_POST, 'id_tipo', FILTER_VALIDATE_INT) ?? 2;
} else {
    // Mantém o tipo atual do usuário logado
    $usuario->id_tipo = (int) ($_SESSION['usuario']['id_tipo'] ?? 2);
}

// Validações mínimas
if (empty($usuario->nome)) {
    setFlash('erro', 'O nome não pode ser vazio.');
    redirectTo('../admin/dashboard.php');
}

if (empty($usuario->email)) {
    setFlash('erro', 'E-mail inválido.');
    redirectTo('../admin/dashboard.php');
}

// ── 6. Atualização de senha (opcional) ───────────────────────
$senha = $_POST['senha'] ?? '';

if (!empty($senha)) {
    if (strlen($senha) < 6) {
        setFlash('erro', 'A nova senha deve ter pelo menos 6 caracteres.');
        redirectTo('../admin/dashboard.php');
    }
    $usuario->AlterarSenha($id, $senha);
}

// ── 7. Executa a atualização ─────────────────────────────────
$alteradas = $usuario->Editar($id);

if ($alteradas > 0) {
    setFlash('sucesso', 'Usuário atualizado com sucesso!');
} else {
    setFlash('info', 'Nenhuma alteração detectada.');
}

redirectTo('../admin/dashboard.php');
