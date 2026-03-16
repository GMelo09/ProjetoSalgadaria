<?php
session_start();
require_once '../classes/usuario_class.php';

// Garante que o acesso seja via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    header("Location: ../dashboard/index.php");
    exit;
}

print_r($_POST);

$usuario = new Usuario();

// Sanitização e validação dos dados
$usuario->id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?? 0;

$usuario->nome = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');

$usuario->email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL) ?? '';

$telefone = filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$usuario->telefone = preg_replace('/\D/', '', $telefone); // mantém apenas números

$senha = $_POST['senha'] ?? '';
if (!empty($senha)) {
    $usuario->senha = $senha;
} else {
    $usuario->senha = null; // ou mantenha a senha atual no método EditarUsuario
}

// Validação do ID
if ($usuario->id <= 0) {
    $_SESSION['mensagem'] = "ID inválido.";
     header("Location: ../dashboard/index.php");
    exit;
}

// Executa a atualização
$alteradas = $usuario->Editar($usuario->id);

// Redirecionamento final
header("Location: ../dashboard/index.php");
exit;
