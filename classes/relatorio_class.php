<?php

declare(strict_types=1);

require_once('banco_class.php');

class Relatorio
{
    public function FaturamentoPorPeriodo(string $data_inicio, string $data_fim): array|false
    {
        $sql = "SELECT
                    COUNT(*) AS total_pedidos,
                    SUM(total) AS faturamento_total,
                    AVG(total) AS ticket_medio
                FROM pedidos
                WHERE criado_em >= ?
                  AND criado_em < DATE_ADD(?, INTERVAL 1 DAY)
                AND status != 'cancelado'";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$data_inicio, $data_fim]);
        return $comando->fetch(PDO::FETCH_ASSOC);
    }

    public function FaturamentoPorDia(string $data_inicio, string $data_fim): array
    {
        $sql = "SELECT
                    DATE(criado_em) AS dia,
                    COUNT(*) AS total_pedidos,
                    SUM(total) AS faturamento
                FROM pedidos
                WHERE criado_em >= ?
                  AND criado_em < DATE_ADD(?, INTERVAL 1 DAY)
                AND status != 'cancelado'
                GROUP BY DATE(criado_em)
                ORDER BY dia ASC";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$data_inicio, $data_fim]);
        return $comando->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Faturamento agrupado por mes — retorna mes_label formatado (ex: "Mar/25")
     * Usado nos graficos e tabela do dashboard.
     */
    public function FaturamentoPorMes(string $data_inicio, string $data_fim): array
    {
        $sql = "SELECT
                    DATE_FORMAT(criado_em, '%Y-%m') AS mes_chave,
                    DATE_FORMAT(criado_em, '%b/%y') AS mes_label,
                    COUNT(*) AS total_pedidos,
                    SUM(total) AS faturamento
                FROM pedidos
                WHERE criado_em >= ?
                  AND criado_em < DATE_ADD(?, INTERVAL 1 DAY)
                AND status != 'cancelado'
                GROUP BY mes_chave, mes_label
                ORDER BY mes_chave ASC";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$data_inicio, $data_fim]);
        return $comando->fetchAll(PDO::FETCH_ASSOC);
    }

    public function ProdutosMaisVendidos(int $limite = 10): array
    {
        $limite = max(1, $limite);
        $sql = "SELECT
                    pi.nome_produto,
                    c.nome AS categoria,
                    SUM(pi.quantidade) AS total_vendido,
                    SUM(pi.quantidade * pi.preco_unitario) AS receita_total
                FROM pedido_itens pi
                LEFT JOIN produtos pr ON pi.produto_id = pr.id
                LEFT JOIN categorias c ON pr.categoria_id = c.id
                INNER JOIN pedidos p ON pi.pedido_id = p.id
                WHERE p.status != 'cancelado'
                GROUP BY pi.produto_id, pi.nome_produto
                ORDER BY total_vendido DESC
                LIMIT :limite";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->bindValue(':limite', $limite, PDO::PARAM_INT);
        $comando->execute();
        return $comando->fetchAll(PDO::FETCH_ASSOC);
    }

    public function PedidosPorStatus(): array
    {
        $sql = "SELECT
                    status,
                    COUNT(*) AS total,
                    SUM(total) AS valor_total
                FROM pedidos
                GROUP BY status
                ORDER BY total DESC";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute();
        return $comando->fetchAll(PDO::FETCH_ASSOC);
    }

    public function FaturamentoPorFormaPagamento(string $data_inicio, string $data_fim): array
    {
        $sql = "SELECT
                    forma_pagamento,
                    COUNT(*) AS total_pedidos,
                    SUM(total) AS valor_total
                FROM pedidos
                WHERE criado_em >= ?
                  AND criado_em < DATE_ADD(?, INTERVAL 1 DAY)
                AND status != 'cancelado'
                GROUP BY forma_pagamento
                ORDER BY valor_total DESC";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$data_inicio, $data_fim]);
        return $comando->fetchAll(PDO::FETCH_ASSOC);
    }

    public function ResumoHoje(): array|false
    {
        $sql = "SELECT
                    COUNT(*) AS total_pedidos,
                    SUM(total) AS faturamento,
                    AVG(total) AS ticket_medio,
                    SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) AS pendentes,
                    SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) AS cancelados
                FROM pedidos
                WHERE criado_em >= CURDATE()
                  AND criado_em < CURDATE() + INTERVAL 1 DAY";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute();
        return $comando->fetch(PDO::FETCH_ASSOC);
    }

    public function VendasPorCategoria(string $data_inicio, string $data_fim): array
    {
        $sql = "SELECT
                    c.nome AS categoria,
                    SUM(pi.quantidade) AS total_vendido,
                    SUM(pi.quantidade * pi.preco_unitario) AS receita_total
                FROM pedido_itens pi
                INNER JOIN produtos pr ON pi.produto_id = pr.id
                INNER JOIN categorias c ON pr.categoria_id = c.id
                INNER JOIN pedidos p ON pi.pedido_id = p.id
                WHERE p.criado_em >= ?
                  AND p.criado_em < DATE_ADD(?, INTERVAL 1 DAY)
                AND p.status != 'cancelado'
                GROUP BY c.id, c.nome
                ORDER BY receita_total DESC";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$data_inicio, $data_fim]);
        return $comando->fetchAll(PDO::FETCH_ASSOC);
    }

    // Retorna faturamento dos ultimos N meses em UMA query — elimina N+1
    public function FaturamentoPorMeses(int $meses = 6): array
    {
        $sql = "SELECT
                    DATE_FORMAT(criado_em, '%Y-%m') AS mes,
                    DATE_FORMAT(criado_em, '%b/%y') AS mes_label,
                    COUNT(*) AS total_pedidos,
                    SUM(total) AS faturamento
                FROM pedidos
                WHERE criado_em >= DATE_SUB(CURDATE(), INTERVAL :meses MONTH)
                  AND status != 'cancelado'
                GROUP BY mes, mes_label
                ORDER BY mes ASC";
        $banco = Banco::conectar();
        $cmd   = $banco->prepare($sql);
        $cmd->bindValue(':meses', $meses, PDO::PARAM_INT);
        $cmd->execute();
        return $cmd->fetchAll(PDO::FETCH_ASSOC);
    }
}
