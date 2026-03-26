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
    public $ativo = 1; // novo campo — padrão ativo

    public function Criar()
    {
        $sql = "INSERT INTO produtos (categoria_id, nome, descricao, preco, emoji, tag, ativo)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([
            $this->categoria_id,
            $this->nome,
            $this->descricao,
            $this->preco,
            $this->emoji,
            $this->tag,
            $this->ativo,
        ]);
        $id = $banco->lastInsertId();
        Banco::desconectar();
        return $id;
    }

    public function Editar($id_produto)
    {
        $sql = "UPDATE produtos SET categoria_id = ?, nome = ?, descricao = ?, preco = ?, emoji = ?, tag = ?, ativo = ?
                WHERE id = ?";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([
            $this->categoria_id,
            $this->nome,
            $this->descricao,
            $this->preco,
            $this->emoji,
            $this->tag,
            $this->ativo,
            $id_produto,
        ]);
        Banco::desconectar();
        return $comando->rowCount();
    }

    // Soft delete — desativa em vez de deletar
    public function Excluir($id_produto)
    {
        $sql     = "UPDATE produtos SET ativo = 0 WHERE id = ?";
        $banco   = Banco::conectar();
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
        $banco   = Banco::conectar();
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
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute();
        $produtos = $comando->fetchAll(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $produtos;
    }

    // Listagem pública — só produtos ativos
    public function ListarPorCategoria($id_categoria)
    {
        $sql = "SELECT p.*, c.nome AS categoria_nome
                FROM produtos p
                INNER JOIN categorias c ON p.categoria_id = c.id
                WHERE p.categoria_id = ? AND p.ativo = 1
                ORDER BY p.nome ASC";
        $banco   = Banco::conectar();
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
                WHERE p.tag = ? AND p.ativo = 1
                ORDER BY p.nome ASC";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$tag]);
        $produtos = $comando->fetchAll(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $produtos;
    }

    public function TotalProdutos()
    {
        $sql     = "SELECT COUNT(*) AS total FROM produtos WHERE ativo = 1";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute();
        $total   = $comando->fetch(PDO::FETCH_ASSOC)['total'];
        Banco::desconectar();
        return $total;
    }
}