<?php
require_once('banco_class.php');

class Pacote
{
    public $quantidade;
    public $max_sabores;
    public $descricao;
    public $popular;
    public $ativo;

    public function Criar(): int|false
    {
        $sql = "INSERT INTO pacotes (quantidade, max_sabores, descricao, popular, ativo)
                VALUES (?, ?, ?, ?, ?)";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $ok      = $comando->execute([
            $this->quantidade,
            $this->max_sabores,
            $this->descricao,
            $this->popular ? 1 : 0,
            $this->ativo   ? 1 : 0,
        ]);
        $id = $ok ? (int) $banco->lastInsertId() : false;
        Banco::desconectar();
        return $id;
    }

    public function Editar(int $id): int
    {
        $sql = "UPDATE pacotes
                SET quantidade = ?, max_sabores = ?, descricao = ?, popular = ?, ativo = ?
                WHERE id = ?";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([
            $this->quantidade,
            $this->max_sabores,
            $this->descricao,
            $this->popular ? 1 : 0,
            $this->ativo   ? 1 : 0,
            $id,
        ]);
        $linhas = $comando->rowCount();
        Banco::desconectar();
        return $linhas;
    }

    public function Excluir(int $id): int
    {
        // Soft delete
        $banco   = Banco::conectar();
        $comando = $banco->prepare("UPDATE pacotes SET ativo = 0 WHERE id = ?");
        $comando->execute([$id]);
        $linhas  = $comando->rowCount();
        Banco::desconectar();
        return $linhas;
    }

    public function BuscarPorId(int $id): array|false
    {
        $banco   = Banco::conectar();
        $comando = $banco->prepare("SELECT * FROM pacotes WHERE id = ?");
        $comando->execute([$id]);
        $pacote  = $comando->fetch(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $pacote;
    }

    // Listagem pública — só ativos, ordenados por quantidade
    public function ListarAtivos(): array
    {
        $banco   = Banco::conectar();
        $comando = $banco->prepare("SELECT * FROM pacotes WHERE ativo = 1 ORDER BY quantidade ASC");
        $comando->execute();
        $pacotes = $comando->fetchAll(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $pacotes;
    }

    // Listagem admin — todos, ativos e inativos
    public function ListarTodos(): array
    {
        $banco   = Banco::conectar();
        $comando = $banco->prepare("SELECT * FROM pacotes ORDER BY quantidade ASC");
        $comando->execute();
        $pacotes = $comando->fetchAll(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $pacotes;
    }

    // Garante que só um pacote seja "popular" por vez
    public function DefinirPopular(int $id): void
    {
        $banco = Banco::conectar();
        $banco->prepare("UPDATE pacotes SET popular = 0")->execute();
        $banco->prepare("UPDATE pacotes SET popular = 1 WHERE id = ?")->execute([$id]);
        Banco::desconectar();
    }
}