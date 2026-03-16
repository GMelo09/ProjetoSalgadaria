<?php
session_start();
require_once '../classes/usuario_class.php';

// Garante que o acesso seja via POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../dashboard/index.php");
    exit;
}

$usuario = new Usuario();

// Sanitização e validação do ID
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?? 0;

// Validação do ID
if ($id <= 0) {
    $_SESSION['mensagem'] = "ID inválido.";
    header("Location: ../dashboard/index.php");
    exit;
}

// Executa a exclusão
$clienteExcluido = $usuario->Excluir($id);

// Define mensagem de feedback
if ($clienteExcluido > 0) {
    $_SESSION['mensagem'] = "Usuário excluído com sucesso!";
} else {
    $_SESSION['mensagem'] = "Erro ao excluir usuário.";
}

// Redirecionamento final
header("Location: ../dashboard/index.php");
?>