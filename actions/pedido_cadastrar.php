<?php
require_once __DIR__ . '/../includes/auth.php';
sessionStart();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../pages/carrinho.php');
    exit;
}

// CSRF
csrfValidar();

require_once __DIR__ . '/../classes/pedido_class.php';
require_once __DIR__ . '/../classes/produto_class.php';

// ── 1. Valida e sanitiza os dados do formulário ───────────────
$nome            = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$telefone        = preg_replace('/\D/', '', filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$endereco        = trim(filter_input(INPUT_POST, 'endereco', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$data_entrega    = filter_input(INPUT_POST, 'data_entrega', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$obs             = trim(filter_input(INPUT_POST, 'obs', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$forma_pagamento = filter_input(INPUT_POST, 'forma_pagamento', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$usuario_id      = filter_input(INPUT_POST, 'usuario_id', FILTER_VALIDATE_INT) ?: null;

// Formata telefone
if (strlen($telefone) === 11) {
    $telefone = '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 5) . '-' . substr($telefone, 7);
} elseif (strlen($telefone) === 10) {
    $telefone = '(' . substr($telefone, 0, 2) . ') ' . substr($telefone, 2, 4) . '-' . substr($telefone, 6);
}

// Validações obrigatórias
if (empty($nome) || empty($telefone) || empty($endereco) || empty($data_entrega) || empty($forma_pagamento)) {
    header('Location: ../pages/checkout.php?erro=campos_obrigatorios');
    exit;
}

// Forma de pagamento válida
$formasValidas = ['pix', 'dinheiro', 'cartao'];
if (!in_array($forma_pagamento, $formasValidas, true)) {
    header('Location: ../pages/checkout.php?erro=pagamento_invalido');
    exit;
}

// Data de entrega não pode ser no passado
$hoje = new DateTime('today');
$dataEntregaObj = DateTime::createFromFormat('Y-m-d', $data_entrega);
if (!$dataEntregaObj || $dataEntregaObj < $hoje) {
    header('Location: ../pages/checkout.php?erro=data_invalida');
    exit;
}

// ── 2. Processa os itens do carrinho ─────────────────────────
$itensJson = $_POST['itens_carrinho'] ?? '';
$itensBrutos = json_decode($itensJson, true);

if (empty($itensBrutos) || !is_array($itensBrutos)) {
    header('Location: ../pages/carrinho.php?erro=carrinho_vazio');
    exit;
}

// ── 3. VALIDAÇÃO DE PREÇOS NO BACKEND ────────────────────────
// Busca o preço real de cada produto no banco — ignora o preço vindo do JS
$produtoObj = new Produto();
$itensValidados = [];
$totalReal = 0;

foreach ($itensBrutos as $item) {
    $produto_id = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);
    $quantidade = max(1, (int) ($item['quantidade'] ?? 1));
    $nomeProduto = trim(strip_tags($item['nome'] ?? ''));

    // Pacotes têm ID no formato 'pacote-timestamp' — trata separado
    if (!$produto_id || str_contains((string)($item['id'] ?? ''), 'pacote')) {
        // Pacote: usa o nome e o preço do frontend (não tem produto_id fixo)
        // mas valida quantidade mínima e máxima
        $quantidade = min(max(1, $quantidade), 500);
        $precoUnitario = round((float) ($item['preco'] ?? 0), 2);

        if ($precoUnitario <= 0) continue;

        $itensValidados[] = [
            'produto_id'     => null,
            'nome_produto'   => $nomeProduto,
            'quantidade'     => $quantidade,
            'preco_unitario' => $precoUnitario,
        ];
        $totalReal += $precoUnitario * $quantidade;
        continue;
    }

    // Produto normal: busca preço real no banco
    $produto = $produtoObj->BuscarPorId($produto_id);

    if (!$produto || empty($produto['ativo'])) {
        // Produto não existe ou está inativo — pula
        continue;
    }

    $precoReal = (float) $produto['preco'];
    $quantidade = min(max(1, $quantidade), 999);

    $itensValidados[] = [
        'produto_id'     => $produto_id,
        'nome_produto'   => $produto['nome'],
        'quantidade'     => $quantidade,
        'preco_unitario' => $precoReal,
    ];
    $totalReal += $precoReal * $quantidade;
}

if (empty($itensValidados)) {
    header('Location: ../pages/carrinho.php?erro=itens_invalidos');
    exit;
}

$totalReal = round($totalReal, 2);

// ── 4. Cria o pedido ─────────────────────────────────────────
$pedido                 = new Pedido();
$pedido->usuario_id     = $usuario_id;
$pedido->nome_cliente   = $nome;
$pedido->telefone       = $telefone;
$pedido->endereco       = $endereco;
$pedido->data_entrega   = $data_entrega;
$pedido->observacao     = $obs;
$pedido->forma_pagamento = $forma_pagamento;
$pedido->total          = $totalReal;
$pedido->itens          = $itensValidados;

$pedido_id = $pedido->Criar();

if (!$pedido_id) {
    error_log('[pedido_cadastrar] Falha ao criar pedido para ' . $nome);
    header('Location: ../pages/checkout.php?erro=servidor');
    exit;
}

// ── 5. Sucesso — redireciona para página de confirmação ───────
header('Location: ../pages/pedido_confirmado.php?id=' . $pedido_id);
exit;