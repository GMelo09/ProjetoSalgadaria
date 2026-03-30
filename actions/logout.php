<?php
require_once __DIR__ . '/../includes/auth.php';
sessionStart();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Se alguém acessar via GET, redireciona sem fazer nada
    header('Location: ../index.php');
    exit;
}

// Valida CSRF mesmo no logout — evita logout forçado por terceiros
csrfValidar();

// Destrói todos os dados da sessão
$_SESSION = [];

// Apaga o cookie de sessão do navegador
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();

header('Location: ../pages/login.php?saiu=1');
exit;