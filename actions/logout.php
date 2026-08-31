<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
sessionStart();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Método não permitido. Use o botão Sair para encerrar a sessão.');
}

csrfValidar();

// A saida vale para qualquer usuario, sem acessar ou alterar o banco.
$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $cookie = session_get_cookie_params();

    setcookie(session_name(), '', [
        'expires' => time() - 42000,
        'path' => $cookie['path'],
        'domain' => $cookie['domain'],
        'secure' => $cookie['secure'],
        'httponly' => $cookie['httponly'],
        'samesite' => $cookie['samesite'] ?? 'Lax',
    ]);
}

session_destroy();
redirectTo('index.php', 303);
