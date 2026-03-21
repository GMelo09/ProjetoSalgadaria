<?php
/* =============================================================
 *  includes/auth.php — Helpers de autenticação e sessão
 * ============================================================= */

/* =============================================================
 *  sessionStart — Inicia sessão com configurações seguras
 * ============================================================= */
function sessionStart(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        // ── Proteções de cookie ──────────────────────────────
        ini_set('session.cookie_httponly', 1);   // JS não acessa o cookie
        ini_set('session.cookie_secure', 1);     // só HTTPS (remova em localhost se necessário)
        ini_set('session.cookie_samesite', 'Lax'); // mitiga CSRF via cookie
        ini_set('session.use_only_cookies', 1);  // proíbe session via URL
        ini_set('session.use_strict_mode', 1);   // rejeita IDs de sessão inventados
        ini_set('session.gc_maxlifetime', 7200); // sessão expira em 2 horas

        session_start();
    }
}

/* =============================================================
 *  requireLogin — Redireciona para login se não autenticado
 * ============================================================= */
function requireLogin(string $redirect = '/pages/login.php'): void
{
    if (empty($_SESSION['usuario_id'])) {
        header('Location: ' . $redirect);
        exit;
    }
}

/* =============================================================
 *  requireAdmin — Bloqueia acesso a não-admins
 * ============================================================= */
function requireAdmin(): void
{
    if (empty($_SESSION['usuario_id']) || empty($_SESSION['eh_admin'])) {
        http_response_code(403);
        header('Location: /index.php?erro=acesso_negado');
        exit;
    }
}

/* =============================================================
 *  isLoggedIn — Verifica se há sessão ativa
 * ============================================================= */
function isLoggedIn(): bool
{
    return !empty($_SESSION['usuario_id']);
}

/* =============================================================
 *  isAdmin — Verifica se o usuário logado é admin
 * ============================================================= */
function isAdmin(): bool
{
    return !empty($_SESSION['eh_admin']) && (int) $_SESSION['eh_admin'] === 1;
}

/* =============================================================
 *  CSRF — Geração e validação de token
 * ============================================================= */

/**
 * Retorna o token CSRF da sessão, gerando um novo se não existir.
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Retorna o campo hidden HTML com o token CSRF.
 * Uso: <?= csrfField() ?> dentro de qualquer <form>.
 */
function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken()) . '">';
}

/**
 * Valida o token CSRF do POST.
 * Deve ser chamado no início de todo action que recebe POST.
 * Em caso de falha, encerra a execução com 403.
 */
function csrfValidar(): void
{
    $tokenEnviado = $_POST['csrf_token'] ?? '';
    $tokenSessao  = $_SESSION['csrf_token'] ?? '';

    // hash_equals previne timing attacks
    if (empty($tokenEnviado) || !hash_equals($tokenSessao, $tokenEnviado)) {
        http_response_code(403);
        // Em produção, logue este evento — pode ser um ataque
        die('Requisição inválida. Token de segurança ausente ou incorreto.');
    }
}

/* =============================================================
 *  Flash messages — Mensagens de uma única exibição via sessão
 * ============================================================= */
function setFlash(string $tipo, string $mensagem): void
{
    $_SESSION['flash'] = ['tipo' => $tipo, 'mensagem' => $mensagem];
}

function getFlash(): array|null
{
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/* =============================================================
 *  Rate limiting — Proteção contra força bruta no login
 *  Armazena tentativas em sessão por IP usando arquivo de lock.
 *  Para produção com alto volume, substitua por Redis ou tabela DB.
 * ============================================================= */
define('RATE_LIMIT_MAX_TENTATIVAS', 5);
define('RATE_LIMIT_JANELA_SEGUNDOS', 300); // 5 minutos de bloqueio

function rateLimitChave(): string
{
    // Usa IP do cliente como chave (considera proxies confiáveis)
    $ip = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
    // Pega apenas o primeiro IP da lista (caso haja proxy)
    $ip = trim(explode(',', $ip)[0]);
    return 'rl_' . md5($ip);
}

/**
 * Verifica se o IP atual está bloqueado.
 * Retorna true se estiver bloqueado, false se pode tentar.
 */
function rateLimitVerificar(): bool
{
    $chave = rateLimitChave();
    if (!isset($_SESSION[$chave])) return false;

    $dados = $_SESSION[$chave];

    // Janela de bloqueio expirou — reseta
    if (time() - $dados['primeiro_em'] > RATE_LIMIT_JANELA_SEGUNDOS) {
        unset($_SESSION[$chave]);
        return false;
    }

    return $dados['tentativas'] >= RATE_LIMIT_MAX_TENTATIVAS;
}

/**
 * Registra uma tentativa de login falha.
 */
function rateLimitRegistrarFalha(): void
{
    $chave = rateLimitChave();

    if (!isset($_SESSION[$chave])) {
        $_SESSION[$chave] = ['tentativas' => 1, 'primeiro_em' => time()];
        return;
    }

    // Janela expirou — reinicia contador
    if (time() - $_SESSION[$chave]['primeiro_em'] > RATE_LIMIT_JANELA_SEGUNDOS) {
        $_SESSION[$chave] = ['tentativas' => 1, 'primeiro_em' => time()];
        return;
    }

    $_SESSION[$chave]['tentativas']++;
}

/**
 * Reseta o contador após login bem-sucedido.
 */
function rateLimitResetar(): void
{
    $chave = rateLimitChave();
    unset($_SESSION[$chave]);
}

/**
 * Retorna quantos segundos faltam para o desbloqueio, ou 0 se não bloqueado.
 */
function rateLimitSegundosRestantes(): int
{
    $chave = rateLimitChave();
    if (!isset($_SESSION[$chave])) return 0;

    $decorrido = time() - $_SESSION[$chave]['primeiro_em'];
    $restante  = RATE_LIMIT_JANELA_SEGUNDOS - $decorrido;

    return max(0, (int) $restante);
}