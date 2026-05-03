<?php

declare(strict_types=1);
require_once('banco_class.php');

class Pedido
{
    public ?int $usuario_id = null;
    public string $nome_cliente = '';
    public string $telefone = '';
    public string $endereco = '';
    public string $cep_entrega = '';
    public ?string $area_entrega = null;
    public string $data_entrega = '';
    public string $horario_entrega = '';
    public ?string $observacao = null;
    public string $forma_pagamento = '';
    public string $status = 'pendente';
    public float $taxa_entrega = 0.0;
    public float $total = 0.0;
    public array $itens = []; // array de ['produto_id', 'nome_produto', 'quantidade', 'preco_unitario']

    public function Criar(): int|false
    {
        $sql = "INSERT INTO pedidos (
                    usuario_id,
                    nome_cliente,
                    telefone,
                    endereco,
                    cep_entrega,
                    area_entrega,
                    data_entrega,
                    horario_entrega,
                    observacao,
                    forma_pagamento,
                    taxa_entrega,
                    total
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $banco = Banco::conectar();
        $banco->beginTransaction();

        try {
            $comando = $banco->prepare($sql);
            $comando->execute([
                $this->usuario_id,
                $this->nome_cliente,
                $this->telefone,
                $this->endereco,
                $this->cep_entrega,
                $this->area_entrega,
                $this->data_entrega,
                $this->horario_entrega,
                $this->observacao,
                $this->forma_pagamento,
                $this->taxa_entrega,
                $this->total,
            ]);

            $pedido_id = $banco->lastInsertId();

            if (!empty($this->itens)) {
                $sqlItem = "INSERT INTO pedido_itens (pedido_id, produto_id, nome_produto, quantidade, preco_unitario)
                            VALUES (?, ?, ?, ?, ?)";
                $comandoItem = $banco->prepare($sqlItem);

                foreach ($this->itens as $item) {
                    $comandoItem->execute([
                        $pedido_id,
                        $item['produto_id'],
                        $item['nome_produto'],
                        $item['quantidade'],
                        $item['preco_unitario'],
                    ]);
                }
            }

            $banco->commit();
            return (int) $pedido_id;
        } catch (Exception $e) {
            $banco->rollBack();
            error_log('[Pedido::Criar] ' . $e->getMessage());
            return false;
        }
    }

    public function Editar(int $id_pedido): int
    {
        $sql = "UPDATE pedidos SET usuario_id = ?, nome_cliente = ?, telefone = ?, endereco = ?,
                cep_entrega = ?, area_entrega = ?, data_entrega = ?, horario_entrega = ?,
                observacao = ?, forma_pagamento = ?, status = ?, taxa_entrega = ?, total = ?
                WHERE id = ?";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([
            $this->usuario_id,
            $this->nome_cliente,
            $this->telefone,
            $this->endereco,
            $this->cep_entrega,
            $this->area_entrega,
            $this->data_entrega,
            $this->horario_entrega,
            $this->observacao,
            $this->forma_pagamento,
            $this->status,
            $this->taxa_entrega,
            $this->total,
            $id_pedido,
        ]);
        return $comando->rowCount();
    }

    public function AtualizarStatus(int $id_pedido, string $status): int
    {
        $sql = "UPDATE pedidos SET status = ? WHERE id = ?";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$status, $id_pedido]);
        return $comando->rowCount();
    }

    public function Excluir(int $id_pedido): int
    {
        $sql = "DELETE FROM pedidos WHERE id = ?";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$id_pedido]);
        return $comando->rowCount();
    }

    public function BuscarPorId(int $id_pedido): array|false
    {
        $sql = "SELECT p.*, u.nome AS usuario_nome
                FROM pedidos p
                LEFT JOIN usuarios u ON p.usuario_id = u.id
                WHERE p.id = ?";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$id_pedido]);
        return $comando->fetch(PDO::FETCH_ASSOC);
    }

    public function ListarItens(int $id_pedido): array
    {
        $sql = "SELECT pi.*
                FROM pedido_itens pi
                WHERE pi.pedido_id = ?";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$id_pedido]);
        return $comando->fetchAll(PDO::FETCH_ASSOC);
    }

    public function ListarPorUsuario(int $id_usuario): array
    {
        $sql = "SELECT * FROM pedidos
                WHERE usuario_id = ?
                ORDER BY criado_em DESC";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$id_usuario]);
        return $comando->fetchAll(PDO::FETCH_ASSOC);
    }

    public function ListarPorStatus(string $status): array
    {
        $sql = "SELECT p.*, u.nome AS usuario_nome
                FROM pedidos p
                LEFT JOIN usuarios u ON p.usuario_id = u.id
                WHERE p.status = ?
                ORDER BY p.data_entrega ASC";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$status]);
        return $comando->fetchAll(PDO::FETCH_ASSOC);
    }

    public function ListarPorData(string $data_entrega): array
    {
        $sql = "SELECT p.*, u.nome AS usuario_nome
                FROM pedidos p
                LEFT JOIN usuarios u ON p.usuario_id = u.id
                WHERE p.data_entrega = ?
                ORDER BY p.criado_em ASC";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$data_entrega]);
        return $comando->fetchAll(PDO::FETCH_ASSOC);
    }

    public function TotalPedidosHoje(): int
    {
        // [3.3] Usa range explícito para aproveitar índice em criado_em
        $sql = "SELECT COUNT(*) AS total FROM pedidos
                WHERE criado_em >= CURDATE()
                  AND criado_em < CURDATE() + INTERVAL 1 DAY";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute();
        return (int) $comando->fetch(PDO::FETCH_ASSOC)['total'];
    }

    public function UltimoPedidoPorUsuario(int $id_usuario): string|null
    {
        $sql = "SELECT MAX(criado_em) AS ultimo_pedido
                FROM pedidos
                WHERE usuario_id = ?";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$id_usuario]);
        $resultado = $comando->fetch(PDO::FETCH_ASSOC);
        return $resultado['ultimo_pedido'] ?? null;
    }

    // [2.1] CORRIGIDO: placeholder :limite no SQL antes do prepare()
    public function ListarRecentes(int $limite = 10): array
    {
        $limite = max(1, $limite);
        $sql = "SELECT p.*, u.nome AS nome_usuario
                FROM pedidos p
                LEFT JOIN usuarios u ON p.usuario_id = u.id
                ORDER BY p.criado_em DESC
                LIMIT :limite";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->bindValue(':limite', $limite, PDO::PARAM_INT);
        $comando->execute();
        return $comando->fetchAll(PDO::FETCH_ASSOC);
    }

    public function ListarTodos(): array
    {
        $sql = "SELECT p.*, u.nome AS nome
                FROM pedidos p
                LEFT JOIN usuarios u ON p.usuario_id = u.id
                ORDER BY p.criado_em DESC";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute();
        return $comando->fetchAll(PDO::FETCH_ASSOC);
    }
}
