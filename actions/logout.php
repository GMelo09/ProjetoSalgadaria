<?php
/**
 * actions/logout.php
 *
 * Logout seguro via POST com token CSRF.
 *
 * Por que POST e não GET?
 * Um link GET pode ser acionado por uma imagem em um e-mail ou
 * em outro site (<img src="seusite.com/logout.php">), forçando
 * o logout do usuário sem ele clicar em nada. POST + CSRF elimina isso.
 *
 * Como usar no HTML (substitui todos os <a href="login.php?sair=1">):
 *
 *   <form action="/actions/logout.php" method="POST" style="display:inline;">
 *     <?= csrfField() ?>
 *     <button type="submit" class="btn-link">Sair</button>
 *   </form>
 *
 * Ou, com um link estilizado via JS:
 *
 *   <a href="#" onclick="document.getElementById('formLogout').submit()">Sair</a>
 *   <form id="formLogout" action="/actions/logout.php" method="POST" hidden>
 *     <?= csrfField() ?>
 *   </form>
 */

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