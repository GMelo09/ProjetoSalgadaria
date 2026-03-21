<?php
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    require_once('../classes/usuario_class.php');
    $usuario = new Usuario();
    $usuario->email = strip_tags($_POST['email']);
    $usuario->senha = strip_tags($_POST['senha']);

    if(empty($usuario->email)){
       header('Location: ../Pages/login.php?err=email_vazio');
       exit();
    }
    else if(empty($usuario->senha)){
       header('Location: ../Pages/login.php?err=senha_vazia');
       exit();
    }
    else{
        $resultado = $usuario->Logar();
        if(sizeof($resultado) == 0){
            header('Location: ../Pages/login.php?erro=credenciais');
            exit();
        }
        else{
            session_start();
            $_SESSION['usuario']      = $resultado[0];
            $_SESSION['usuario_id']   = $resultado[0]['id'];
            $_SESSION['usuario_nome'] = $resultado[0]['nome'];
            $_SESSION['eh_admin']     = ($resultado[0]['id_tipo'] == 1) ? 1 : 0;
            header('Location: ../index.php');
            exit();
        }
    }
}
else{
    echo "Essa página deve ser carregada por POST.";
}
?>