<?php

declare(strict_types=1);

require_once('banco_class.php');

class Produto
{
    public int    $categoria_id = 0;
    public string $nome         = '';
    public string $descricao    = '';
    public float  $preco        = 0.0;
    public ?string $imagem      = null; // nome do arquivo em uploads/produtos/
    public string $tag          = '';
    public int    $ativo        = 1;

    public function Criar(): int|false
    {
        $sql = "INSERT INTO produtos (categoria_id, nome, descricao, preco, imagem, tag, ativo)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([
            $this->categoria_id,
            $this->nome,
            $this->descricao,
            $this->preco,
            $this->imagem,
            $this->tag,
            $this->ativo,
        ]);
        $id = (int) $banco->lastInsertId();
        return $id > 0 ? $id : false;
    }

    public function Editar(int $id_produto): int
    {
        // Se $this->imagem for null, nao altera a coluna imagem no banco
        if ($this->imagem !== null) {
            $sql = "UPDATE produtos
                    SET categoria_id = ?, nome = ?, descricao = ?, preco = ?, imagem = ?, tag = ?, ativo = ?
                    WHERE id = ?";
            $params = [
                $this->categoria_id,
                $this->nome,
                $this->descricao,
                $this->preco,
                $this->imagem,
                $this->tag,
                $this->ativo,
                $id_produto,
            ];
        } else {
            $sql = "UPDATE produtos
                    SET categoria_id = ?, nome = ?, descricao = ?, preco = ?, tag = ?, ativo = ?
                    WHERE id = ?";
            $params = [
                $this->categoria_id,
                $this->nome,
                $this->descricao,
                $this->preco,
                $this->tag,
                $this->ativo,
                $id_produto,
            ];
        }

        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute($params);
        return $comando->rowCount();
    }

    public function Excluir(int $id_produto): int
    {
        $sql     = "UPDATE produtos SET ativo = 0 WHERE id = ?";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$id_produto]);
        return $comando->rowCount();
    }

    public function BuscarPorId(int $id_produto): array|false
    {
        $sql = "SELECT p.*, c.nome AS categoria_nome
                FROM produtos p
                INNER JOIN categorias c ON p.categoria_id = c.id
                WHERE p.id = ?";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$id_produto]);
        return $comando->fetch(PDO::FETCH_ASSOC);
    }

    public function ListarTodos(): array
    {
        $sql = "SELECT p.*, c.nome AS categoria_nome
                FROM produtos p
                INNER JOIN categorias c ON p.categoria_id = c.id
                ORDER BY c.nome ASC, p.nome ASC";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute();
        return $comando->fetchAll(PDO::FETCH_ASSOC);
    }

    public function ListarPorCategoria(int $id_categoria): array
    {
        $sql = "SELECT p.*, c.nome AS categoria_nome
                FROM produtos p
                INNER JOIN categorias c ON p.categoria_id = c.id
                WHERE p.categoria_id = ? AND p.ativo = 1
                ORDER BY p.nome ASC";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$id_categoria]);
        return $comando->fetchAll(PDO::FETCH_ASSOC);
    }

    public function ListarPorTag(string $tag): array
    {
        $sql = "SELECT p.*, c.nome AS categoria_nome
                FROM produtos p
                INNER JOIN categorias c ON p.categoria_id = c.id
                WHERE p.tag = ? AND p.ativo = 1
                ORDER BY p.nome ASC";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$tag]);
        return $comando->fetchAll(PDO::FETCH_ASSOC);
    }

    public function SalgadosDestaque(): array
    {
        $banco     = Banco::conectar();
        $comando   = $banco->query("SELECT * FROM vw_salgados_destaque");
        $todos     = $comando->fetchAll(PDO::FETCH_ASSOC);

        $destaques = array_slice($todos, 0, 5);

        // Fallback: sem vendas no mes passado → pega os mais recentes
        if (empty($destaques)) {
            $comando   = $banco->query(
                "SELECT * FROM produtos WHERE categoria_id = 1 AND ativo = 1 ORDER BY criado_em DESC LIMIT 5"
            );
            $destaques = $comando->fetchAll(PDO::FETCH_ASSOC);
        }

        return $destaques;
    }

    public function DocesDestaque(): array
    {
        $banco     = Banco::conectar();
        $comando   = $banco->query("SELECT * FROM vw_doces_destaque");
        $todos     = $comando->fetchAll(PDO::FETCH_ASSOC);

        $destaques = array_slice($todos, 0, 5);

        // Fallback: sem vendas no mes passado → pega os mais recentes
        if (empty($destaques)) {
            $comando   = $banco->query(
                "SELECT * FROM produtos WHERE categoria_id = 2 AND ativo = 1 ORDER BY criado_em DESC LIMIT 5"
            );
            $destaques = $comando->fetchAll(PDO::FETCH_ASSOC);
        }

        return $destaques;
    }

    public function TotalProdutos(): int
    {
        $sql     = "SELECT COUNT(*) AS total FROM produtos WHERE ativo = 1";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute();
        return (int) $comando->fetch(PDO::FETCH_ASSOC)['total'];
    }
}
