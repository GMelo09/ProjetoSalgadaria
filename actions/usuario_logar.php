<?php

declare(strict_types=1);


require_once __DIR__ . '/../includes/auth.php';
sessionStart(); // SEMPRE antes de qualquer $_SESSION

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('../pages/login.php');
}

// ── 1. Validação CSRF ────────────────────────────────────────
csrfValidar();

// ── 2. Rate limiting — bloqueia IPs com muitas tentativas ────
if (rateLimitVerificar()) {
    $segundos = rateLimitSegundosRestantes();
    $minutos  = ceil($segundos / 60);
    redirectTo('../pages/login.php?erro=bloqueado&min=' . $minutos);
}

require_once __DIR__ . '/../classes/usuario_class.php';

// ── 3. Sanitização e validação dos campos ───────────────────
// filter_input valida o tipo antes — strip_tags não é adequado para senhas
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
$senha = $_POST['senha'] ?? ''; // senha não deve ser transformada

if (empty($email)) {
    redirectTo('../pages/login.php?erro=email_invalido');
}

if (empty($senha)) {
    redirectTo('../pages/login.php?erro=senha_vazia');
}

// ── 4. Autenticação ──────────────────────────────────────────
$usuario       = new Usuario();
$usuario->email = $email;
$usuario->senha = $senha;

$resultado = $usuario->Logar();

if ($resultado === null) {
    // Login falhou — registra tentativa
    rateLimitRegistrarFalha();

    // Mensagem genérica — nunca diga se foi o email ou a senha
    // (previne user enumeration)
    redirectTo('../pages/login.php?erro=credenciais');
}

// ── 5. Login bem-sucedido ────────────────────────────────────

// Reseta contador de tentativas
rateLimitResetar();

// CRUCIAL: regenera o ID de sessão para prevenir session fixation.
// Sem isso, um atacante que plantou um session ID no browser da vítima
// antes do login consegue assumir a sessão após o login.
session_regenerate_id(true);

// Armazena apenas o necessário na sessão — nunca a senha
$_SESSION['usuario_id']   = (int)  $resultado['id'];
$_SESSION['usuario_nome'] = $resultado['nome'];
$_SESSION['eh_admin']     = ((int) $resultado['id_tipo'] === 1) ? 1 : 0;

// Guarda o array completo também (sem a senha, por segurança)
unset($resultado['senha']);
$_SESSION['usuario'] = $resultado;

redirectTo('../index.php');
