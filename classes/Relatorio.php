<?php
/* =============================================================
 *  Relatorio — Faturamento, vendas e estatísticas
 *  Todo SQL de relatórios fica AQUI.
 * ============================================================= */

class Relatorio
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /* =============================================================
     *  faturamentoTotal — Total geral (excluindo cancelados)
     * ============================================================= */
    public function faturamentoTotal(): float
    {
        $st = $this->db->prepare("
            SELECT COALESCE(SUM(total), 0) AS total
            FROM pedidos
            WHERE status <> 'cancelado'
        ");
        $st->execute();
        return (float) $st->fetchColumn();
    }

    /* =============================================================
     *  faturamentoHoje — Faturamento do dia atual
     * ============================================================= */
    public function faturamentoHoje(): float
    {
        $st = $this->db->prepare("
            SELECT COALESCE(SUM(total), 0) AS total
            FROM pedidos
            WHERE status <> 'cancelado'
              AND DATE(criado_em) = CURDATE()
        ");
        $st->execute();
        return (float) $st->fetchColumn();
    }

    /* =============================================================
     *  faturamentoPorPeriodo — Agrupa por dia nos últimos N dias
     * ============================================================= */
    public function faturamentoPorPeriodo(int $dias = 30): array
    {
        $st = $this->db->prepare("
            SELECT DATE(criado_em) AS dia,
                   COUNT(*)        AS total_pedidos,
                   SUM(total)      AS faturamento
            FROM pedidos
            WHERE status <> 'cancelado'
              AND criado_em >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(criado_em)
            ORDER BY dia DESC
        ");
        $st->execute([$dias]);
        return $st->fetchAll();
    }

    /* =============================================================
     *  faturamentoPorMes — Agrupado por mês (últimos 12 meses)
     * ============================================================= */
    public function faturamentoPorMes(): array
    {
        $st = $this->db->prepare("
            SELECT DATE_FORMAT(criado_em, '%Y-%m') AS mes,
                   DATE_FORMAT(criado_em, '%m/%Y') AS mes_label,
                   COUNT(*)                        AS total_pedidos,
                   SUM(total)                      AS faturamento
            FROM pedidos
            WHERE status <> 'cancelado'
              AND criado_em >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(criado_em, '%Y-%m')
            ORDER BY mes ASC
        ");
        $st->execute();
        return $st->fetchAll();
    }

    /* =============================================================
     *  produtosMaisVendidos — Top N produtos por quantidade
     * ============================================================= */
    public function produtosMaisVendidos(int $limite = 10): array
    {
        $st = $this->db->prepare("
            SELECT pi.nome,
                   SUM(pi.quantidade)              AS total_vendido,
                   SUM(pi.subtotal)                AS total_faturado,
                   COUNT(DISTINCT pi.pedido_id)    AS total_pedidos
            FROM pedido_itens pi
            JOIN pedidos p ON p.id = pi.pedido_id
            WHERE p.status <> 'cancelado'
            GROUP BY pi.produto_id, pi.nome
            ORDER BY total_vendido DESC
            LIMIT ?
        ");
        $st->execute([$limite]);
        return $st->fetchAll();
    }

    /* =============================================================
     *  pedidosPorDia — Quantidade de pedidos por dia
     * ============================================================= */
    public function pedidosPorDia(int $dias = 30): array
    {
        $st = $this->db->prepare("
            SELECT DATE(criado_em) AS dia,
                   COUNT(*)        AS total
            FROM pedidos
            WHERE criado_em >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
            GROUP BY DATE(criado_em)
            ORDER BY dia ASC
        ");
        $st->execute([$dias]);
        return $st->fetchAll();
    }

    /* =============================================================
     *  resumoGeral — Cards do dashboard
     * ============================================================= */
    public function resumoGeral(): array
    {
        $st = $this->db->prepare("
            SELECT
                (SELECT COUNT(*) FROM pedidos WHERE status = 'pendente')                      AS pedidos_pendentes,
                (SELECT COUNT(*) FROM pedidos WHERE DATE(criado_em) = CURDATE())              AS pedidos_hoje,
                (SELECT COALESCE(SUM(total), 0) FROM pedidos
                    WHERE status <> 'cancelado' AND DATE(criado_em) = CURDATE())              AS faturamento_hoje,
                (SELECT COALESCE(SUM(total), 0) FROM pedidos
                    WHERE status <> 'cancelado'
                      AND MONTH(criado_em) = MONTH(CURDATE())
                      AND YEAR(criado_em)  = YEAR(CURDATE()))                                 AS faturamento_mes,
                (SELECT COUNT(*) FROM usuarios WHERE eh_admin = 0)                            AS total_clientes,
                (SELECT COUNT(*) FROM produtos WHERE ativo = 1)                               AS total_produtos
        ");
        $st->execute();
        return $st->fetch();
    }
}
