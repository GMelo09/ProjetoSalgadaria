<?php
/* =============================================================
 *  Database — Conexão PDO Singleton
 *  Centraliza a conexão com o banco de dados.
 * ============================================================= */

class Database
{
    private static ?PDO $instance = null;

    /* Impede instanciação direta */
    private function __construct() {}
    private function __clone() {}

    /* =============================================================
     *  getInstance — Retorna a instância PDO (Singleton)
     * ============================================================= */
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            $host    = DB_HOST;
            $name    = DB_NAME;
            $charset = DB_CHAR;

            $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";

            self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }

        return self::$instance;
    }
}
