<?php
require_once('banco_class.php');

class Pedido
{
    public $usuario_id;
    public $nome_cliente;
    public $telefone;
    public $endereco;
    public $data_entrega;
    public $observacao;
    public $forma_pagamento;
    public $status;
    public $total;
    public $itens; // array de ['produto_id', 'nome_produto', 'quantidade', 'preco_unitario']

    public function Criar()
    {
        $sql = "INSERT INTO pedidos (usuario_id, nome_cliente, telefone, endereco, data_entrega, observacao, forma_pagamento, total)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $banco = Banco::conectar();
        $banco->beginTransaction();

        try {
            $comando = $banco->prepare($sql);
            $comando->execute([
                $this->usuario_id,
                $this->nome_cliente,
                $this->telefone,
                $this->endereco,
                $this->data_entrega,
                $this->observacao,
                $this->forma_pagamento,
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
            Banco::desconectar();
            return $pedido_id;
        } catch (Exception $e) {
            $banco->rollBack();
            Banco::desconectar();
            return false;
        }
    }

    public function Editar($id_pedido)
    {
        $sql = "UPDATE pedidos SET usuario_id = ?, nome_cliente = ?, telefone = ?, endereco = ?,
                data_entrega = ?, observacao = ?, forma_pagamento = ?, status = ?, total = ?
                WHERE id = ?";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([
            $this->usuario_id,
            $this->nome_cliente,
            $this->telefone,
            $this->endereco,
            $this->data_entrega,
            $this->observacao,
            $this->forma_pagamento,
            $this->status,
            $this->total,
            $id_pedido,
        ]);
        Banco::desconectar();
        return $comando->rowCount();
    }

    public function AtualizarStatus($id_pedido, $status)
    {
        $sql = "UPDATE pedidos SET status = ? WHERE id = ?";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$status, $id_pedido]);
        Banco::desconectar();
        return $comando->rowCount();
    }

    public function Excluir($id_pedido)
    {
        $sql = "DELETE FROM pedidos WHERE id = ?";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$id_pedido]);
        Banco::desconectar();
        return $comando->rowCount();
    }

    public function BuscarPorId($id_pedido)
    {
        $sql = "SELECT p.*, u.nome AS usuario_nome
                FROM pedidos p
                LEFT JOIN usuarios u ON p.usuario_id = u.id
                WHERE p.id = ?";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$id_pedido]);
        $pedido = $comando->fetch(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $pedido;
    }

    public function ListarItens($id_pedido)
    {
        $sql = "SELECT pi.*
                FROM pedido_itens pi
                WHERE pi.pedido_id = ?";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$id_pedido]);
        $itens = $comando->fetchAll(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $itens;
    }

    public function ListarPorUsuario($id_usuario)
    {
        $sql = "SELECT * FROM pedidos
                WHERE usuario_id = ?
                ORDER BY criado_em DESC";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$id_usuario]);
        $pedidos = $comando->fetchAll(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $pedidos;
    }

    public function ListarPorStatus($status)
    {
        $sql = "SELECT p.*, u.nome AS usuario_nome
                FROM pedidos p
                LEFT JOIN usuarios u ON p.usuario_id = u.id
                WHERE p.status = ?
                ORDER BY p.data_entrega ASC";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$status]);
        $pedidos = $comando->fetchAll(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $pedidos;
    }

    public function ListarPorData($data_entrega)
    {
        $sql = "SELECT p.*, u.nome AS usuario_nome
                FROM pedidos p
                LEFT JOIN usuarios u ON p.usuario_id = u.id
                WHERE p.data_entrega = ?
                ORDER BY p.criado_em ASC";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$data_entrega]);
        $pedidos = $comando->fetchAll(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $pedidos;
    }

    public function TotalPedidosHoje()
    {
        $sql = "SELECT COUNT(*) AS total FROM pedidos
                WHERE DATE(criado_em) = CURDATE()";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute();
        $total = $comando->fetch(PDO::FETCH_ASSOC)['total'];
        Banco::desconectar();
        return $total;
    }

    public function UltimoPedidoPorUsuario($id_usuario)
    {
        $sql = "SELECT MAX(criado_em) AS ultimo_pedido
                FROM pedidos
                WHERE usuario_id = ?";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$id_usuario]);
        $resultado = $comando->fetch(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $resultado['ultimo_pedido'] ?? null;
    }
    public function ListarRecentes($limite = 10)
    {
        $limite = (int) $limite;
        $sql = "SELECT p.*, u.nome AS nome
            FROM pedidos p
            LEFT JOIN usuarios u ON p.usuario_id = u.id
            ORDER BY p.criado_em DESC LIMIT $limite";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute();
        $pedidos = $comando->fetchAll(PDO::FETCH_ASSOC);
        $comando->bindValue(':limite', (int) $limite, PDO::PARAM_INT);
        Banco::desconectar();
        return $pedidos;
    }
    public function ListarTodos()
    {
        $sql = "SELECT p.*, u.nome AS nome
            FROM pedidos p
            LEFT JOIN usuarios u ON p.usuario_id = u.id
            ORDER BY p.criado_em DESC";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute();
        $pedidos = $comando->fetchAll(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $pedidos;
    }
}