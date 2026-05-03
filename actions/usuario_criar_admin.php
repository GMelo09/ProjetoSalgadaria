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

require_once __DIR__ . '/../classes/usuario_class.php';

$nome     = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$email    = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$telefone = preg_replace('/\D/', '', filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$senha    = $_POST['senha'] ?? '';
$id_tipo  = filter_input(INPUT_POST, 'id_tipo', FILTER_VALIDATE_INT) ?: 2;

// Formata telefone
if (strlen($telefone) === 11) {
    $telefone = '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
} elseif (strlen($telefone) === 10) {
    $telefone = '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
}

// Validações
if (empty($nome)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Nome obrigatório.']);
    exit;
}
if (!$email) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'E-mail inválido.']);
    exit;
}
if (strlen($senha) < 6) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Senha deve ter no mínimo 6 caracteres.']);
    exit;
}
if (!in_array($id_tipo, [1, 2], true)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Tipo de usuário inválido.']);
    exit;
}

$usuario = new Usuario();

if ($usuario->EmailExiste($email)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Este e-mail já está cadastrado.']);
    exit;
}

$novo_id = $usuario->CriarAdmin($nome, $email, $senha, $telefone, $id_tipo);

echo json_encode([
    'sucesso'  => $novo_id > 0,
    'mensagem' => $novo_id > 0 ? 'Usuário cadastrado com sucesso!' : 'Erro ao cadastrar.',
]);
exit;
