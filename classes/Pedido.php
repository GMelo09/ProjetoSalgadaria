<?php
/* =============================================================
 *  Pedido — Criação, listagem e atualização de pedidos
 *  Todo SQL relativo a pedidos fica AQUI.
 * ============================================================= */

class Pedido
{
    private PDO $db;

    /* Status válidos para pedidos */
    public const STATUS = [
        'pendente'      => 'Pendente',
        'confirmado'    => 'Confirmado',
        'em_preparo'    => 'Em Preparo',
        'saiu_entrega'  => 'Saiu para Entrega',
        'entregue'      => 'Entregue',
        'cancelado'     => 'Cancelado',
    ];

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /* =============================================================
     *  criarPedido — Insere pedido e seus itens (transação)
     *  Retorna ID do pedido criado
     * ============================================================= */
    public function criarPedido(array $dados, array $itens): int
    {
        $this->db->beginTransaction();

        try {
            // Calcula total
            $total = array_reduce($itens, fn($c, $i) => $c + ($i['preco'] * $i['quantidade']), 0);

            $st = $this->db->prepare('
                INSERT INTO pedidos
                    (usuario_id, nome, telefone, email, endereco,
                     data_entrega, obs, forma_pagamento, total)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $st->execute([
                $dados['usuario_id'] ?? null,
                htmlspecialchars(strip_tags($dados['nome']),     ENT_QUOTES, 'UTF-8'),
                htmlspecialchars(strip_tags($dados['telefone']), ENT_QUOTES, 'UTF-8'),
                filter_var($dados['email'] ?? '', FILTER_SANITIZE_EMAIL) ?: null,
                htmlspecialchars(strip_tags($dados['endereco']), ENT_QUOTES, 'UTF-8'),
                $dados['data_entrega'],
                htmlspecialchars(strip_tags($dados['obs'] ?? ''), ENT_QUOTES, 'UTF-8') ?: null,
                $dados['forma_pagamento'],
                round($total, 2),
            ]);

            $pedidoId = (int) $this->db->lastInsertId();

            // Insere itens
            $stItem = $this->db->prepare('
                INSERT INTO pedido_itens
                    (pedido_id, produto_id, nome, preco_unit, quantidade, tipo_pacote)
                VALUES (?, ?, ?, ?, ?, ?)
            ');

            foreach ($itens as $item) {
                $produtoId  = isset($item['id']) && is_numeric($item['id']) ? (int) $item['id'] : null;
                $tipoPacote = isset($item['tipo_pacote']) && $item['tipo_pacote'] ? (int) $item['tipo_pacote'] : null;
                $stItem->execute([
                    $pedidoId,
                    $produtoId,
                    htmlspecialchars(strip_tags($item['nome']), ENT_QUOTES, 'UTF-8'),
                    (float) $item['preco'],
                    (int)   $item['quantidade'],
                    $tipoPacote,
                ]);
            }

            $this->db->commit();
            return $pedidoId;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /* =============================================================
     *  listarPedidos — Lista todos os pedidos (admin)
     * ============================================================= */
    public function listarPedidos(string $status = '', int $limite = 0, int $offset = 0): array
    {
        $where  = $status ? 'WHERE p.status = ?' : '';
        $limit  = $limite > 0 ? "LIMIT {$limite} OFFSET {$offset}" : '';

        $sql = "
            SELECT p.id, p.nome, p.telefone, p.email, p.status,
                   p.forma_pagamento, p.data_entrega, p.total,
                   p.criado_em,
                   COUNT(pi.id) AS total_itens,
                   u.email AS usuario_email
            FROM pedidos p
            LEFT JOIN pedido_itens pi ON pi.pedido_id = p.id
            LEFT JOIN usuarios u ON u.id = p.usuario_id
            {$where}
            GROUP BY p.id
            ORDER BY p.criado_em DESC
            {$limit}
        ";

        $st = $this->db->prepare($sql);
        $status ? $st->execute([$status]) : $st->execute();
        return $st->fetchAll();
    }

    /* =============================================================
     *  listarPedidosUsuario — Pedidos de um usuário específico
     * ============================================================= */
    public function listarPedidosUsuario(int $usuarioId): array
    {
        $st = $this->db->prepare('
            SELECT p.*, COUNT(pi.id) AS total_itens
            FROM pedidos p
            LEFT JOIN pedido_itens pi ON pi.pedido_id = p.id
            WHERE p.usuario_id = ?
            GROUP BY p.id
            ORDER BY p.criado_em DESC
        ');
        $st->execute([$usuarioId]);
        return $st->fetchAll();
    }

    /* =============================================================
     *  buscarPedido — Busca pedido por ID com itens
     * ============================================================= */
    public function buscarPedido(int $id): array|false
    {
        $stPedido = $this->db->prepare('SELECT * FROM pedidos WHERE id = ?');
        $stPedido->execute([$id]);
        $pedido = $stPedido->fetch();
        if (!$pedido) return false;

        $stItens = $this->db->prepare('SELECT * FROM pedido_itens WHERE pedido_id = ?');
        $stItens->execute([$id]);
        $pedido['itens'] = $stItens->fetchAll();

        return $pedido;
    }

    /* =============================================================
     *  alterarStatusPedido — Atualiza status do pedido
     * ============================================================= */
    public function alterarStatusPedido(int $id, string $novoStatus): bool
    {
        if (!array_key_exists($novoStatus, self::STATUS)) {
            return false;
        }
        $st = $this->db->prepare('UPDATE pedidos SET status = ? WHERE id = ?');
        return $st->execute([$novoStatus, $id]);
    }

    /* =============================================================
     *  contarPorStatus — Conta pedidos por cada status
     * ============================================================= */
    public function contarPorStatus(): array
    {
        $st = $this->db->prepare('
            SELECT status, COUNT(*) AS total
            FROM pedidos
            GROUP BY status
        ');
        $st->execute();
        $resultado = [];
        foreach ($st->fetchAll() as $row) {
            $resultado[$row['status']] = (int) $row['total'];
        }
        return $resultado;
    }
}
