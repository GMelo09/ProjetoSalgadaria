<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/config_check.php';
appConfigLoad();

class Banco
{
    private static $bdNome = DB_NAME;
    private static $dbHost = DB_HOST;
    private static $dbUsuario = DB_USER;
    private static $dbSenha = DB_PASS;

    private static $cont = null;

    private function __construct() {}

    public static function conectar()
    {
        if (self::$cont === null) {
            try {
                self::$cont = new PDO(
                    'mysql:host=' . self::$dbHost . ';dbname=' . self::$bdNome . ';charset=utf8mb4',
                    self::$dbUsuario,
                    self::$dbSenha,
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
            } catch (PDOException $exception) {
                error_log('[db] Falha ao conectar ao banco de dados: ' . $exception->getMessage());
                http_response_code(500);
                exit('Erro interno ao conectar ao banco de dados. Tente novamente mais tarde.');
            }
        }

        return self::$cont;
    }

    public static function desconectar()
    {
        self::$cont = null;
    }
}
