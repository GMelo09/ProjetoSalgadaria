<?php
require_once('banco_class.php');

class Relatorio
{
    public function FaturamentoPorPeriodo($data_inicio, $data_fim)
    {
        $sql = "SELECT 
                    COUNT(*) AS total_pedidos,
                    SUM(total) AS faturamento_total,
                    AVG(total) AS ticket_medio
                FROM pedidos
                WHERE DATE(criado_em) BETWEEN ? AND ?
                AND status != 'cancelado'";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$data_inicio, $data_fim]);
        $resultado = $comando->fetch(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $resultado;
    }

    public function FaturamentoPorDia($data_inicio, $data_fim)
    {
        $sql = "SELECT 
                    DATE(criado_em) AS dia,
                    COUNT(*) AS total_pedidos,
                    SUM(total) AS faturamento
                FROM pedidos
                WHERE DATE(criado_em) BETWEEN ? AND ?
                AND status != 'cancelado'
                GROUP BY DATE(criado_em)
                ORDER BY dia ASC";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$data_inicio, $data_fim]);
        $resultado = $comando->fetchAll(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $resultado;
    }

    /**
     * Faturamento agrupado por mês — retorna mes_label formatado (ex: "Mar/25")
     * Usado nos gráficos e tabela do dashboard.
     */
    public function FaturamentoPorMes($data_inicio, $data_fim)
    {
        $sql = "SELECT 
                    DATE_FORMAT(criado_em, '%Y-%m') AS mes_chave,
                    DATE_FORMAT(criado_em, '%b/%y') AS mes_label,
                    COUNT(*) AS total_pedidos,
                    SUM(total) AS faturamento
                FROM pedidos
                WHERE DATE(criado_em) BETWEEN ? AND ?
                AND status != 'cancelado'
                GROUP BY mes_chave, mes_label
                ORDER BY mes_chave ASC";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$data_inicio, $data_fim]);
        $resultado = $comando->fetchAll(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $resultado;
    }

    public function ProdutosMaisVendidos($limite = 10)
    {
        $limite = (int) $limite;
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
                LIMIT $limite";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute();
        $resultado = $comando->fetchAll(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $resultado;
    }

    public function PedidosPorStatus()
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
        $resultado = $comando->fetchAll(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $resultado;
    }

    public function FaturamentoPorFormaPagamento($data_inicio, $data_fim)
    {
        $sql = "SELECT 
                    forma_pagamento,
                    COUNT(*) AS total_pedidos,
                    SUM(total) AS valor_total
                FROM pedidos
                WHERE DATE(criado_em) BETWEEN ? AND ?
                AND status != 'cancelado'
                GROUP BY forma_pagamento
                ORDER BY valor_total DESC";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$data_inicio, $data_fim]);
        $resultado = $comando->fetchAll(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $resultado;
    }

    public function ResumoHoje()
    {
        $sql = "SELECT 
                    COUNT(*) AS total_pedidos,
                    SUM(total) AS faturamento,
                    AVG(total) AS ticket_medio,
                    SUM(CASE WHEN status = 'pendente' THEN 1 ELSE 0 END) AS pendentes,
                    SUM(CASE WHEN status = 'cancelado' THEN 1 ELSE 0 END) AS cancelados
                FROM pedidos
                WHERE DATE(criado_em) = CURDATE()";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute();
        $resultado = $comando->fetch(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $resultado;
    }

    public function VendasPorCategoria($data_inicio, $data_fim)
    {
        $sql = "SELECT 
                    c.nome AS categoria,
                    SUM(pi.quantidade) AS total_vendido,
                    SUM(pi.quantidade * pi.preco_unitario) AS receita_total
                FROM pedido_itens pi
                INNER JOIN produtos pr ON pi.produto_id = pr.id
                INNER JOIN categorias c ON pr.categoria_id = c.id
                INNER JOIN pedidos p ON pi.pedido_id = p.id
                WHERE DATE(p.criado_em) BETWEEN ? AND ?
                AND p.status != 'cancelado'
                GROUP BY c.id, c.nome
                ORDER BY receita_total DESC";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$data_inicio, $data_fim]);
        $resultado = $comando->fetchAll(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $resultado;
    }
}