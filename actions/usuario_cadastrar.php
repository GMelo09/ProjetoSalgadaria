<?php
/* =============================================================
 *  actions/usuario_cadastrar.php — Processa o cadastro
 * ============================================================= */
require_once __DIR__ . '/../includes/auth.php';
sessionStart();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Usuario.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/login.php');
    exit;
}

$nome  = trim($_POST['nome']             ?? '');
$email = trim($_POST['email']            ?? '');
$tel   = trim($_POST['telefone']         ?? '');
$senha = $_POST['senha']                 ?? '';
$conf  = $_POST['senha_confirmacao']     ?? '';

/* Validações */
if (!$nome || !$email || !$senha) {
    header('Location: ../pages/login.php?erro=campos_obrigatorios&tab=cadastro');
    exit;
}
if ($senha !== $conf) {
    header('Location: ../pages/login.php?erro=senhas_diferentes&tab=cadastro');
    exit;
}
if (strlen($senha) < 6) {
    header('Location: ../pages/login.php?erro=senha_curta&tab=cadastro');
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../pages/login.php?erro=campos_obrigatorios&tab=cadastro');
    exit;
}

try {
    $usuarioObj = new Usuario();
    $novoId     = $usuarioObj->registrar($nome, $email, $senha, $tel);

    if (!$novoId) {
        header('Location: ../pages/login.php?erro=email_existente&tab=cadastro');
        exit;
    }

    /* Regenera session ID */
    session_regenerate_id(true);

    $_SESSION['usuario_id']   = $novoId;
    $_SESSION['usuario_nome'] = htmlspecialchars(strip_tags($nome), ENT_QUOTES, 'UTF-8');
    $_SESSION['eh_admin']     = 0;

    setFlash('sucesso', 'Conta criada com sucesso! Bem-vindo(a)!');
    header('Location: ../index.php');
    exit;

} catch (Exception $e) {
    header('Location: ../pages/login.php?erro=servidor&tab=cadastro');
    exit;
}
