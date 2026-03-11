<?php
/* =============================================================
 *  actions/pedido_cadastrar.php — Processa criação de pedido
 * ============================================================= */
require_once __DIR__ . '/../includes/auth.php';
sessionStart();

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Pedido.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/carrinho.php');
    exit;
}

/* =============================================================
 *  COLETA E VALIDAÇÃO DOS DADOS
 * ============================================================= */
$nome      = trim($_POST['nome']           ?? '');
$telefone  = trim($_POST['telefone']       ?? '');
$email     = trim($_POST['email']          ?? '');
$endereco  = trim($_POST['endereco']       ?? '');
$dataEnt   = trim($_POST['data_entrega']   ?? '');
$obs       = trim($_POST['obs']            ?? '');
$pagamento = trim($_POST['forma_pagamento']?? '');
$itensJson = $_POST['itens_carrinho']      ?? '[]';

/* Campos obrigatórios */
if (!$nome || !$telefone || !$endereco || !$dataEnt || !$pagamento) {
    header('Location: ../pages/checkout.php?erro=campos_obrigatorios');
    exit;
}

/* Valida forma de pagamento */
$pagamentosValidos = ['pix', 'dinheiro', 'cartao'];
if (!in_array($pagamento, $pagamentosValidos, true)) {
    header('Location: ../pages/checkout.php?erro=campos_obrigatorios');
    exit;
}

/* Valida data de entrega */
$dataMin = date('Y-m-d', strtotime('+' . max(1, (int) cfg('entrega_min_dias','1')) . ' day'));
if ($dataEnt < $dataMin) {
    header('Location: ../pages/checkout.php?erro=campos_obrigatorios');
    exit;
}

/* Decodifica e valida itens */
$itens = json_decode($itensJson, true);
if (!is_array($itens) || count($itens) === 0) {
    header('Location: ../pages/carrinho.php?erro=carrinho_vazio');
    exit;
}

/* Valida estrutura de cada item */
foreach ($itens as $item) {
    if (!isset($item['nome'], $item['preco'], $item['quantidade'])) {
        header('Location: ../pages/carrinho.php?erro=carrinho_vazio');
        exit;
    }
    if (!is_numeric($item['preco']) || !is_numeric($item['quantidade']) || $item['quantidade'] < 1) {
        header('Location: ../pages/carrinho.php?erro=carrinho_vazio');
        exit;
    }
}

/* =============================================================
 *  CRIAÇÃO DO PEDIDO
 * ============================================================= */
try {
    $pedidoObj = new Pedido();

    $pedidoId = $pedidoObj->criarPedido(
        [
            'usuario_id'      => $_SESSION['usuario_id'] ?? null,
            'nome'            => $nome,
            'telefone'        => $telefone,
            'email'           => $email ?: '',
            'endereco'        => $endereco,
            'data_entrega'    => $dataEnt,
            'obs'             => $obs,
            'forma_pagamento' => $pagamento,
        ],
        $itens
    );

    header("Location: ../pages/pedido_confirmado.php?id={$pedidoId}");
    exit;

} catch (Exception $e) {
    header('Location: ../pages/checkout.php?erro=servidor');
    exit;
}
