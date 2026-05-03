<?php

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';
sessionStart();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('../pages/login.php');
}

// ── 1. Validação CSRF ────────────────────────────────────────
csrfValidar();

require_once __DIR__ . '/../classes/usuario_class.php';

// ── 2. Captura e sanitiza os campos ─────────────────────────
$nome     = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$email    = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);   // false se inválido
$telefone = trim(filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$telefone = preg_replace('/\D/', '', $telefone); // [2.6] mantém só uma chamada (linha duplicada removida)

// Aplica máscara antes de salvar no banco
if (strlen($telefone) === 11) {
    // Celular: (XX) XXXXX-XXXX
    $telefone = '(' . substr($telefone, 0, 2) . ') '
        . substr($telefone, 2, 5) . '-'
        . substr($telefone, 7);
} elseif (strlen($telefone) === 10) {
    // Fixo: (XX) XXXX-XXXX
    $telefone = '(' . substr($telefone, 0, 2) . ') '
        . substr($telefone, 2, 4) . '-'
        . substr($telefone, 6);
}

// Senhas: não sanitizar — apenas verificar e usar no hash
$senha              = $_POST['senha']              ?? '';
$senha_confirmacao  = $_POST['senha_confirmacao']  ?? '';

// ── 3. Validações no servidor ────────────────────────────────
if (empty($nome)) {
    redirectTo('../pages/login.php?erro=nome_vazio&tab=cadastro');
}

if (!$email) {
    redirectTo('../pages/login.php?erro=email_invalido&tab=cadastro');
}

if (empty($senha)) {
    redirectTo('../pages/login.php?erro=senha_vazia&tab=cadastro');
}

// [2.5] Mínimo de 8 caracteres (padrão NIST 2025)
if (strlen($senha) < 8) {
    redirectTo('../pages/login.php?erro=senha_curta&tab=cadastro');
}

if ($senha !== $senha_confirmacao) {
    redirectTo('../pages/login.php?erro=senhas_diferentes&tab=cadastro');
}

// ── 4. Verifica duplicidade de e-mail ────────────────────────
$usuario = new Usuario();

if ($usuario->EmailExiste($email)) {
    // [3.4] Mensagem genérica — não revela se o e-mail já está cadastrado
    redirectTo('../pages/login.php?erro=cadastro_invalido&tab=cadastro');
}

// ── 5. Cadastra o usuário ────────────────────────────────────
$usuario->nome     = $nome;
$usuario->email    = $email;
$usuario->telefone = $telefone;
$usuario->senha    = $senha; // hash é feito dentro de Cadastrar()

try {
    $id = $usuario->Cadastrar();

    if ($id > 0) {
        setFlash('sucesso', 'Conta criada com sucesso! Faça login para continuar.');
        redirectTo('../pages/login.php?cadastro=sucesso');
    } else {
        redirectTo('../pages/login.php?erro=servidor&tab=cadastro');
    }
} catch (PDOException $e) {
    // Loga o erro real internamente — não exibe ao usuário
    error_log('[usuario_cadastrar] PDOException: ' . $e->getMessage());
    redirectTo('../pages/login.php?erro=servidor&tab=cadastro');
}

exit;
