<?php
require_once('banco_class.php');

class Categoria
{
    public $nome;

    public function Criar()
    {
        $sql = "INSERT INTO categorias (nome) VALUES (?)";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$this->nome]);
        $id = $banco->lastInsertId();
        Banco::desconectar();
        return $id;
    }

    public function Editar($id_categoria)
    {
        $sql = "UPDATE categorias SET nome = ? WHERE id = ?";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$this->nome, $id_categoria]);
        Banco::desconectar();
        return $comando->rowCount();
    }

    public function Excluir($id_categoria)
    {
        $sql = "DELETE FROM categorias WHERE id = ?";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$id_categoria]);
        Banco::desconectar();
        return $comando->rowCount();
    }

    public function BuscarPorId($id_categoria)
    {
        $sql = "SELECT * FROM categorias WHERE id = ?";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$id_categoria]);
        $categoria = $comando->fetch(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $categoria;
    }

    public function ListarTodas()
    {
        $sql = "SELECT * FROM categorias ORDER BY nome ASC";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute();
        $categorias = $comando->fetchAll(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $categorias;
    }

    public function ListarComTotalProdutos()
    {
        $sql = "SELECT c.*, COUNT(p.id) AS total_produtos
                FROM categorias c
                LEFT JOIN produtos p ON p.categoria_id = c.id
                GROUP BY c.id
                ORDER BY c.nome ASC";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute();
        $categorias = $comando->fetchAll(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $categorias;
    }
}