<?php
/* =============================================================
 *  includes/auth.php — Helpers de autenticação e sessão
 * ============================================================= */

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
 *  sessionStart — Inicia sessão com configurações seguras
 * ============================================================= */
function sessionStart(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_strict_mode', 1);
        session_start();
    }
}

/* =============================================================
 *  setFlash / getFlash — Mensagens flash via sessão
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