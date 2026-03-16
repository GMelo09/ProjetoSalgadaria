<?php
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    require_once('../classes/usuario_class.php');
    $usuario = new Usuario();
    $usuario->email = strip_tags($_POST['email']);
    $usuario->senha = strip_tags($_POST['senha']);


    if(empty($usuario->email)){
       header('Location: ../login.php?err=email_vazio');
    }
    else if(empty($usuario->senha)){
       header('Location: ../login.php?err=senha_vazia');
    }
    else{
        $resultado = $usuario->Logar();
        if(sizeof($resultado) == 0 ){
            header('Location: ../login.php?err=usuario_login_falha');
            // exit();
        //     print_r('Usuário ou senha inválidos.');
        }
        else{
            //Iniciar sessão de 
            session_start();
            //criar sessão com os dados vindo do banco de dados
            $_SESSION['usuario'] = $resultado[0];
            //redirecionar para a área padina inicial
            header('Location: ../index.php'); // redirecionar para o conteudo principal
            exit();
        }
    }
}
else{
    echo "Essa página deve ser carregada por POST.";
}
?>