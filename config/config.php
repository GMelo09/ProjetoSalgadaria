<?php
// Configurações do banco de dados

define('DB_HOST', 'localhost');
define('DB_NAME', 'doce_salgado');
define('DB_USER', 'root'); // usuário do banco
define('DB_PASS', '');     // senha do banco
define('DB_CHAR', 'utf8mb4');

// Importa a classe de conexão com o banco
require_once __DIR__ . '/../classes/Database.php';


// Função para buscar configurações da tabela "configuracoes"
function cfg($chave, $default = '')
{
    static $cache = [];

    // Se ainda não buscou essa chave
    if (!isset($cache[$chave])) {

        try {
            $db = Database::getInstance();

            $sql = "SELECT valor FROM configuracoes WHERE chave = ? LIMIT 1";
            $stmt = $db->prepare($sql);
            $stmt->execute([$chave]);

            $resultado = $stmt->fetch();

            if ($resultado) {
                $cache[$chave] = $resultado['valor'];
            } else {
                $cache[$chave] = $default;
            }

        } catch (Exception $e) {
            $cache[$chave] = $default;
        }
    }

    return $cache[$chave];
}