<?php
/* =============================================================
 *  actions/sair.php — Encerra a sessão com segurança
 * ============================================================= */
require_once __DIR__ . '/../includes/auth.php';
sessionStart();

/* Destrói todos os dados da sessão */
$_SESSION = [];

/* Apaga o cookie de sessão */
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();
header('Location: ../index.php');
exit;
