<?php
/* =============================================================
 *  config/db.php — Configurações do banco de dados
 * ============================================================= */

define('DB_HOST', 'localhost');
define('DB_NAME', 'doce_salgado');
define('DB_USER', 'root');   // Altere para o usuário do seu banco
define('DB_PASS', '');       // Altere para a sua senha
define('DB_CHAR', 'utf8mb4');

require_once __DIR__ . '/../classes/Database.php';

/* =============================================================
 *  cfg() — Busca configuração da tabela `configuracoes`
 * ============================================================= */
function cfg(string $chave, string $default = ''): string
{
    static $cache = [];

    if (!isset($cache[$chave])) {
        try {
            $st = Database::getInstance()
                ->prepare('SELECT valor FROM configuracoes WHERE chave = ? LIMIT 1');
            $st->execute([$chave]);
            $row = $st->fetch();
            $cache[$chave] = $row ? (string) $row['valor'] : $default;
        } catch (Exception $e) {
            $cache[$chave] = $default;
        }
    }

    return $cache[$chave];
}
