<?php

declare(strict_types=1);
/* =============================================================
 *  includes/auth.php - Helpers de autenticação, sessão e CSRF
 * ============================================================= */

require_once __DIR__ . '/config_check.php';
appConfigLoad();

function appUrl(string $path = ''): string
{
    $base = rtrim((string) BASE_URL, '/');
    $path = trim($path);

    if ($path === '') {
        return $base . '/';
    }

    if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $path)) {
        return $path;
    }

    $path = str_replace('\\', '/', $path);

    while (str_starts_with($path, '../')) {
        $path = substr($path, 3);
    }

    if (str_starts_with($path, './')) {
        $path = substr($path, 2);
    }

    if (str_starts_with($path, '?')) {
        return $base . '/' . $path;
    }

    return $base . '/' . ltrim($path, '/');
}

function redirectTo(string $path, int $status = 302): void
{
    header('Location: ' . appUrl($path), true, $status);
    exit;
}

function requestIsHttps(): bool
{
    $forwardedProto = $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '';

    if ($forwardedProto !== '') {
        $proto = strtolower(trim(explode(',', $forwardedProto)[0]));
        return $proto === 'https';
    }

    if (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') {
        return true;
    }

    if (!empty($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower((string) $_SERVER['HTTP_X_FORWARDED_SSL']) === 'on') {
        return true;
    }

    return (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
}

function normalizePort(?int $port, string $scheme): ?int
{
    if (!$port) {
        return null;
    }

    if (($scheme === 'http' && $port === 80) || ($scheme === 'https' && $port === 443)) {
        return null;
    }

    return $port;
}

function enforceCanonicalRequest(): void
{
    if (PHP_SAPI === 'cli' || headers_sent()) {
        return;
    }

    $baseParts = parse_url((string) BASE_URL);
    if (!is_array($baseParts) || empty($baseParts['scheme']) || empty($baseParts['host'])) {
        return;
    }

    $targetScheme = strtolower($baseParts['scheme']);
    $targetHost = strtolower($baseParts['host']);
    $targetPort = normalizePort(isset($baseParts['port']) ? (int) $baseParts['port'] : null, $targetScheme);

    $hostHeader = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? '';
    $hostParts = parse_url('http://' . $hostHeader);
    $currentHost = strtolower($hostParts['host'] ?? $hostHeader);
    $currentScheme = requestIsHttps() ? 'https' : 'http';
    $currentPort = normalizePort(
        isset($hostParts['port']) ? (int) $hostParts['port'] : (int) ($_SERVER['SERVER_PORT'] ?? 0),
        $currentScheme
    );

    $requestUri = $_SERVER['REQUEST_URI'] ?? '/';
    $requestPath = parse_url($requestUri, PHP_URL_PATH) ?: '/';
    $requestQuery = parse_url($requestUri, PHP_URL_QUERY);
    $basePath = '/' . trim((string) ($baseParts['path'] ?? ''), '/');
    $basePath = $basePath === '/' ? '' : $basePath;
    $pathMatchesBase = $basePath === '' || $requestPath === $basePath || str_starts_with($requestPath, $basePath . '/');

    $needsRedirect =
        $currentScheme !== $targetScheme
        || $currentHost !== $targetHost
        || $currentPort !== $targetPort
        || !$pathMatchesBase;

    if (!$needsRedirect) {
        return;
    }

    $pathToAppend = $requestPath;
    if ($basePath !== '' && $pathMatchesBase) {
        $pathToAppend = substr($requestPath, strlen($basePath)) ?: '/';
    }

    $targetUrl = appUrl($pathToAppend . ($requestQuery !== null && $requestQuery !== '' ? '?' . $requestQuery : ''));
    header('Location: ' . $targetUrl, true, 302);
    exit;
}

function sessionCookiePath(): string
{
    $basePath = parse_url((string) BASE_URL, PHP_URL_PATH) ?: '/';
    $basePath = '/' . trim($basePath, '/');

    return $basePath === '/' ? '/' : $basePath;
}

/* =============================================================
 *  sessionStart - Inicia sessão e envia headers de segurança
 * ============================================================= */
function sessionStart(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        enforceCanonicalRequest();

        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.gc_maxlifetime', '7200');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => sessionCookiePath(),
            'domain' => '',
            'secure' => requestIsHttps(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();
    }

    // [4.1] Headers de segurança HTTP — enviados uma única vez por request
    if (!headers_sent()) {
        header("Content-Security-Policy: default-src 'self'; script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com 'unsafe-inline'; style-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com 'unsafe-inline'; img-src 'self' data:; font-src 'self' https://cdn.jsdelivr.net https://fonts.gstatic.com;");
        header("X-Content-Type-Options: nosniff");
        header("X-Frame-Options: SAMEORIGIN");
        header("Referrer-Policy: strict-origin-when-cross-origin");
    }
}

/* =============================================================
 *  requireLogin - Redireciona para login se não autenticado
 * ============================================================= */
function requireLogin(string $redirect = 'pages/login.php'): void
{
    if (empty($_SESSION['usuario_id'])) {
        redirectTo($redirect);
    }
}

/* =============================================================
 *  requireAdmin - Bloqueia acesso a não-admins
 * ============================================================= */
function requireAdmin(): void
{
    if (empty($_SESSION['usuario_id']) || empty($_SESSION['eh_admin'])) {
        http_response_code(403);
        redirectTo('index.php?erro=acesso_negado');
    }
}

/* =============================================================
 *  requireAdminAjax - Versão para endpoints AJAX
 * ============================================================= */
function requireAdminAjax(): void
{
    if (empty($_SESSION['usuario_id']) || empty($_SESSION['eh_admin'])) {
        http_response_code(403);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['sucesso' => false, 'mensagem' => 'Sem permissão.']);
        exit;
    }
}

function isLoggedIn(): bool
{
    return !empty($_SESSION['usuario_id']);
}

function isAdmin(): bool
{
    return !empty($_SESSION['eh_admin']) && (int) $_SESSION['eh_admin'] === 1;
}

/* =============================================================
 *  CSRF - Geração e validação de token
 * ============================================================= */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrfToken(), ENT_QUOTES, 'UTF-8') . '">';
}

function responseIsJson(): bool
{
    foreach (headers_list() as $header) {
        if (stripos($header, 'Content-Type:') === 0 && stripos($header, 'application/json') !== false) {
            return true;
        }
    }

    return stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
}

function csrfValidar(): void
{
    $tokenEnviado = (string) ($_POST['csrf_token'] ?? '');
    $tokenSessao = (string) ($_SESSION['csrf_token'] ?? '');

    if ($tokenEnviado === '' || $tokenSessao === '' || !hash_equals($tokenSessao, $tokenEnviado)) {
        http_response_code(403);
        error_log('[csrf] Token ausente ou invalido para ' . ($_SERVER['REQUEST_URI'] ?? 'URI desconhecida'));

        if (responseIsJson()) {
            echo json_encode(['sucesso' => false, 'mensagem' => 'Requisição inválida. Atualize a página e tente novamente.']);
            exit;
        }

        exit('Requisição inválida. Atualize a página e tente novamente.');
    }
}

/* =============================================================
 *  Flash messages
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
 *  Rate limiting persistente por IP
 * ============================================================= */
define('RATE_LIMIT_MAX_TENTATIVAS', 5);
define('RATE_LIMIT_JANELA_SEGUNDOS', 300);

// [2.2] CORRIGIDO: X-Forwarded-For só é confiável com TRUSTED_PROXY = true
function rateLimitIp(): string
{
    if (defined('TRUSTED_PROXY') && TRUSTED_PROXY && isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        return filter_var($ip, FILTER_VALIDATE_IP) ?: ($_SERVER['REMOTE_ADDR'] ?? 'desconhecido');
    }
    return $_SERVER['REMOTE_ADDR'] ?? 'desconhecido';
}

function rateLimitChave(): string
{
    return hash('sha256', rateLimitIp());
}

function rateLimitDiretorio(): string
{
    static $dir = null;

    if ($dir !== null) {
        return $dir;
    }

    $dir = __DIR__ . '/../tmp/rate_limit';

    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        error_log('[rate-limit] Nao foi possivel criar o diretorio de armazenamento.');
        http_response_code(500);
        exit('Erro de configuração: armazenamento temporário indisponível.');
    }

    if (!is_writable($dir)) {
        error_log('[rate-limit] Diretorio de armazenamento sem permissao de escrita.');
        http_response_code(500);
        exit('Erro de configuração: armazenamento temporário indisponível.');
    }

    return $dir;
}

function rateLimitArquivo(): string
{
    return rateLimitDiretorio() . DIRECTORY_SEPARATOR . rateLimitChave() . '.json';
}

function rateLimitLer(): array|null
{
    $arquivo = rateLimitArquivo();

    if (!is_file($arquivo)) {
        return null;
    }

    $conteudo = file_get_contents($arquivo);
    $dados = json_decode((string) $conteudo, true);

    if (!is_array($dados) || !isset($dados['tentativas'], $dados['primeiro_em'])) {
        @unlink($arquivo);
        return null;
    }

    return [
        'tentativas' => (int) $dados['tentativas'],
        'primeiro_em' => (int) $dados['primeiro_em'],
    ];
}

function rateLimitSalvar(array $dados): void
{
    $arquivo = rateLimitArquivo();
    file_put_contents($arquivo, json_encode($dados), LOCK_EX);
}

function rateLimitVerificar(): bool
{
    $dados = rateLimitLer();

    if ($dados === null) {
        return false;
    }

    if (time() - $dados['primeiro_em'] > RATE_LIMIT_JANELA_SEGUNDOS) {
        rateLimitResetar();
        return false;
    }

    return $dados['tentativas'] >= RATE_LIMIT_MAX_TENTATIVAS;
}

function rateLimitRegistrarFalha(): void
{
    $dados = rateLimitLer();

    if ($dados === null || time() - $dados['primeiro_em'] > RATE_LIMIT_JANELA_SEGUNDOS) {
        rateLimitSalvar(['tentativas' => 1, 'primeiro_em' => time()]);
        return;
    }

    $dados['tentativas']++;
    rateLimitSalvar($dados);
}

function rateLimitResetar(): void
{
    $arquivo = rateLimitArquivo();

    if (is_file($arquivo)) {
        @unlink($arquivo);
    }
}

function rateLimitSegundosRestantes(): int
{
    $dados = rateLimitLer();

    if ($dados === null) {
        return 0;
    }

    $decorrido = time() - $dados['primeiro_em'];
    return max(0, RATE_LIMIT_JANELA_SEGUNDOS - $decorrido);
}
