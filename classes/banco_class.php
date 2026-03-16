<?php
require_once(__DIR__ . '/../config.php');

class Banco
{
    private static $bdNome = DB_NAME;
    private static $dbHost = DB_HOST;
    private static $dbUsuario = DB_USER;
    private static $dbSenha = DB_PASS;

    private static $cont = null;

    public function __construct()
    {
        die('A função Init não é permitida');
    }

    public static function conectar()
    {
        if(null == self::$cont){
            try{
                self::$cont = new PDO("mysql:host=".self::$dbHost.";"."dbname=".self::$bdNome.";charset=utf8mb4", self::$dbUsuario, self::$dbSenha);
            }
            catch(PDOException $exception){
                die($exception->getMessage());
            }
        }
        return self::$cont;
    }
    public static function desconectar()
    {
        self::$cont = null;
    }

}
?>