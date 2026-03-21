<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once('../classes/usuario_class.php');
    $usuario = new Usuario();

    $usuario->nome = strip_tags($_POST['nome']);
    $usuario->email = strip_tags($_POST['email']);
    $usuario->telefone = strip_tags($_POST['telefone']);
    $usuario->senha = strip_tags($_POST['senha']);

    if (empty($usuario->nome)) {
        header('Location: ../login.php?err=nome_vazio');
    } else if (empty($usuario->email)) {
        header('Location: ../login.php?err=email_vazio');
    } else if (empty($usuario->telefone)) {
        header('Location: ../login.php?err=telefone_vazio');
    } else if (empty($usuario->senha)) {
        header('Location: ../login.php?err=senha_vazia');
    } else {
        if ($usuario->EmailExiste($usuario->email)) {
            header('Location: ../pages/login.php?erro=email_existente&tab=cadastro');
            exit;
        }

        try {
            if ($usuario->Cadastrar() > 0) {
                header('Location: ../pages/login.php?cadastro=sucesso');
            } else {
                header('Location: ../pages/login.php?err=usuario_cadastro_falha');
            }
        } catch (PDOException $e) {
            header('Location: ../pages/login.php?err=usuario_cadastro_falha');
        }
        exit;
    }
} else {
    echo "Essa página deve ser carregada por POST.";
}
