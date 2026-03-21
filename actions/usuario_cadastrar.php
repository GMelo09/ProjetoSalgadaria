<?php

/**
 * actions/usuario_cadastrar.php
 *
 * Correções aplicadas:
 *  1. session_start() adicionado (necessário para CSRF e flash)
 *  2. Validação CSRF
 *  3. Validação de email no servidor (filter_var)
 *  4. Caminhos de redirect unificados (todos para pages/login.php)
 *  5. Parâmetro de erro unificado: sempre "erro=" (era mistura de err= e erro=)
 *  6. Validação de tamanho mínimo de senha no servidor
 *  7. Senha confirmação validada no servidor (não só no JS)
 */

require_once __DIR__ . '/../includes/auth.php';
sessionStart();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/login.php');
    exit;
}

// ── 1. Validação CSRF ────────────────────────────────────────
csrfValidar();

require_once __DIR__ . '/../classes/usuario_class.php';

// ── 2. Captura e sanitiza os campos ─────────────────────────
$nome     = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$email    = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);   // false se inválido
$telefone = trim(filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$telefone = preg_replace('/\D/', '', $telefone); // mantém só números
$telefone = preg_replace('/\D/', '', $telefone);

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
// (não confiar apenas no HTML5/JS — qualquer um pode bypassar)

if (empty($nome)) {
    header('Location: ../pages/login.php?erro=nome_vazio&tab=cadastro');
    exit;
}

if (!$email) {
    header('Location: ../pages/login.php?erro=email_invalido&tab=cadastro');
    exit;
}

if (empty($senha)) {
    header('Location: ../pages/login.php?erro=senha_vazia&tab=cadastro');
    exit;
}

if (strlen($senha) < 6) {
    header('Location: ../pages/login.php?erro=senha_curta&tab=cadastro');
    exit;
}

if ($senha !== $senha_confirmacao) {
    header('Location: ../pages/login.php?erro=senhas_diferentes&tab=cadastro');
    exit;
}

// ── 4. Verifica duplicidade de e-mail ────────────────────────
$usuario = new Usuario();

if ($usuario->EmailExiste($email)) {
    header('Location: ../pages/login.php?erro=email_existente&tab=cadastro');
    exit;
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
        header('Location: ../pages/login.php?cadastro=sucesso');
    } else {
        header('Location: ../pages/login.php?erro=servidor&tab=cadastro');
    }
} catch (PDOException $e) {
    // Loga o erro real internamente — não exibe ao usuário
    error_log('[usuario_cadastrar] PDOException: ' . $e->getMessage());
    header('Location: ../pages/login.php?erro=servidor&tab=cadastro');
}

exit;
