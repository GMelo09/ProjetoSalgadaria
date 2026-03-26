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

// Cadastra com o tipo escolhido pelo admin (diferente do cadastro público que é sempre tipo 2)
$sql = "INSERT INTO usuarios (nome, email, senha, telefone, id_tipo)
        VALUES (?, ?, ?, ?, ?)";
$banco   = Banco::conectar();
$comando = $banco->prepare($sql);
$ok      = $comando->execute([
    $nome,
    $email,
    password_hash($senha, PASSWORD_BCRYPT),
    $telefone,
    $id_tipo,
]);
$novo_id = $ok ? (int) $banco->lastInsertId() : 0;
Banco::desconectar();

echo json_encode([
    'sucesso'  => $novo_id > 0,
    'mensagem' => $novo_id > 0 ? 'Usuário cadastrado com sucesso!' : 'Erro ao cadastrar.',
]);
exit;