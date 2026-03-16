<?php
require_once('banco_class.php');

class Produto
{
    public $categoria_id;
    public $nome;
    public $descricao;
    public $preco;
    public $emoji;
    public $tag;

    public function Criar()
    {
        $sql = "INSERT INTO produtos (categoria_id, nome, descricao, preco, emoji, tag)
                VALUES (?, ?, ?, ?, ?, ?)";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([
            $this->categoria_id,
            $this->nome,
            $this->descricao,
            $this->preco,
            $this->emoji,
            $this->tag,
        ]);
        $id = $banco->lastInsertId();
        Banco::desconectar();
        return $id;
    }

    public function Editar($id_produto)
    {
        $sql = "UPDATE produtos SET categoria_id = ?, nome = ?, descricao = ?, preco = ?, emoji = ?, tag = ?
                WHERE id = ?";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([
            $this->categoria_id,
            $this->nome,
            $this->descricao,
            $this->preco,
            $this->emoji,
            $this->tag,
            $id_produto,
        ]);
        Banco::desconectar();
        return $comando->rowCount();
    }

    public function Excluir($id_produto)
    {
        $sql = "DELETE FROM produtos WHERE id = ?";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$id_produto]);
        Banco::desconectar();
        return $comando->rowCount();
    }

    public function BuscarPorId($id_produto)
    {
        $sql = "SELECT p.*, c.nome AS categoria_nome
                FROM produtos p
                INNER JOIN categorias c ON p.categoria_id = c.id
                WHERE p.id = ?";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$id_produto]);
        $produto = $comando->fetch(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $produto;
    }

    public function ListarTodos()
    {
        $sql = "SELECT p.*, c.nome AS categoria_nome
                FROM produtos p
                INNER JOIN categorias c ON p.categoria_id = c.id
                ORDER BY c.nome ASC, p.nome ASC";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute();
        $produtos = $comando->fetchAll(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $produtos;
    }

    public function ListarPorCategoria($id_categoria)
    {
        $sql = "SELECT p.*, c.nome AS categoria_nome
                FROM produtos p
                INNER JOIN categorias c ON p.categoria_id = c.id
                WHERE p.categoria_id = ?
                ORDER BY p.nome ASC";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$id_categoria]);
        $produtos = $comando->fetchAll(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $produtos;
    }

    public function ListarPorTag($tag)
    {
        $sql = "SELECT p.*, c.nome AS categoria_nome
                FROM produtos p
                INNER JOIN categorias c ON p.categoria_id = c.id
                WHERE p.tag = ?
                ORDER BY p.nome ASC";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$tag]);
        $produtos = $comando->fetchAll(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $produtos;
    }

    public function TotalProdutos()
    {
        $sql = "SELECT COUNT(*) AS total FROM produtos";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute();
        $total = $comando->fetch(PDO::FETCH_ASSOC)['total'];
        Banco::desconectar();
        return $total;
    }
}