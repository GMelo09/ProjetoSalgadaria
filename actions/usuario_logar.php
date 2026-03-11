<?php
/* =============================================================
 *  actions/usuario_logar.php — Processa o login
 * ============================================================= */
require_once __DIR__ . '/../includes/auth.php';
sessionStart();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Usuario.php';

/* Aceita apenas POST */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/login.php');
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) ?? '';
$senha = $_POST['senha'] ?? '';

/* Validação básica */
if (!$email || !$senha) {
    header('Location: ../pages/login.php?erro=campos_obrigatorios');
    exit;
}

try {
    $usuarioObj = new Usuario();
    $usuario    = $usuarioObj->login($email, $senha);

    if (!$usuario) {
        header('Location: ../pages/login.php?erro=credenciais');
        exit;
    }

    /* Regenera session ID para evitar session fixation */
    session_regenerate_id(true);

    /* Persiste dados na sessão */
    $_SESSION['usuario_id']   = $usuario['id'];
    $_SESSION['usuario_nome'] = $usuario['nome'];
    $_SESSION['eh_admin']     = (int) $usuario['eh_admin'];

    header('Location: ../index.php');
    exit;

} catch (Exception $e) {
    header('Location: ../pages/login.php?erro=servidor');
    exit;
}
