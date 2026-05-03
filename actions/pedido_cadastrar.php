<?php

declare(strict_types=1);
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/delivery.php';
sessionStart();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirectTo('/pages/carrinho.php');
}

requireLogin('pages/login.php?erro=login_obrigatorio&tab=cadastro');

// CSRF
csrfValidar();

require_once __DIR__ . '/../classes/pedido_class.php';
require_once __DIR__ . '/../classes/produto_class.php';
require_once __DIR__ . '/../classes/usuario_class.php';
require_once __DIR__ . '/../classes/pacote_class.php';

// ── 1. Valida e sanitiza os dados do formulário ───────────────
$nome            = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$telefoneDigits  = preg_replace('/\D/', '', filter_input(INPUT_POST, 'telefone', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$cep             = deliveryFormatCep(filter_input(INPUT_POST, 'cep', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$logradouro      = trim(filter_input(INPUT_POST, 'logradouro', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$numero          = trim(filter_input(INPUT_POST, 'numero', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$complemento     = trim(filter_input(INPUT_POST, 'complemento', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$bairro          = trim(filter_input(INPUT_POST, 'bairro', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$cidade          = trim(filter_input(INPUT_POST, 'cidade', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$uf              = strtoupper(trim(filter_input(INPUT_POST, 'uf', FILTER_SANITIZE_SPECIAL_CHARS) ?? ''));
$data_entrega    = filter_input(INPUT_POST, 'data_entrega', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$horarioEntrega  = filter_input(INPUT_POST, 'horario_entrega', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$obs             = trim(filter_input(INPUT_POST, 'obs', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$forma_pagamento = filter_input(INPUT_POST, 'forma_pagamento', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$usuario_id      = (int) ($_SESSION['usuario_id'] ?? 0);
$endereco        = '';
$areaEntrega     = deliveryFindArea($cep);

// Formata telefone
if (strlen($telefoneDigits) === 11) {
    $telefone = '(' . substr($telefoneDigits, 0, 2) . ') ' . substr($telefoneDigits, 2, 5) . '-' . substr($telefoneDigits, 7);
} elseif (strlen($telefoneDigits) === 10) {
    $telefone = '(' . substr($telefoneDigits, 0, 2) . ') ' . substr($telefoneDigits, 2, 4) . '-' . substr($telefoneDigits, 6);
} else {
    $telefone = '';
}

// Validações obrigatórias
if (
    empty($nome)
    || empty($telefone)
    || empty($cep)
    || empty($logradouro)
    || empty($numero)
    || empty($bairro)
    || empty($cidade)
    || empty($uf)
    || empty($data_entrega)
    || empty($horarioEntrega)
    || empty($forma_pagamento)
) {
    redirectTo('/pages/checkout.php?erro=campos_obrigatorios');
}

if (strlen(deliveryNormalizeCep($cep)) !== 8) {
    redirectTo('/pages/checkout.php?erro=cep_invalido');
}

if (!$areaEntrega) {
    redirectTo('pages/checkout.php?erro=cep_fora_area');
}

// Forma de pagamento válida
$formasValidas = ['pix', 'dinheiro', 'cartao'];
if (!in_array($forma_pagamento, $formasValidas, true)) {
    redirectTo('/pages/checkout.php?erro=pagamento_invalido');
}

// Data de entrega respeita antecedência mínima
$dataMinima = DateTime::createFromFormat('!Y-m-d', deliveryMinimumDate()) ?: new DateTime('tomorrow');
$dataEntregaObj = DateTime::createFromFormat('!Y-m-d', $data_entrega);
if (!$dataEntregaObj || $dataEntregaObj < $dataMinima) {
    redirectTo('/pages/checkout.php?erro=prazo_minimo');
}

if (!deliveryTimeIsValid($horarioEntrega)) {
    redirectTo('/pages/checkout.php?erro=horario_invalido');
}

$cidadeUf = trim($cidade . ($uf ? '/' . $uf : ''));
$primeiraLinha = trim($logradouro . ', ' . $numero);
if ($complemento !== '') {
    $primeiraLinha .= ' - ' . $complemento;
}
$endereco = implode(', ', array_filter([$primeiraLinha, $bairro, $cidadeUf]));
$horarioEntregaDb = $horarioEntrega . ':00';

// ── 2. Processa os itens do carrinho ─────────────────────────
$itensJson = $_POST['itens_carrinho'] ?? '';
$itensBrutos = json_decode($itensJson, true);

if (empty($itensBrutos) || !is_array($itensBrutos)) {
    redirectTo('/pages/carrinho.php?erro=carrinho_vazio');
}

// ── 3. VALIDAÇÃO DE PREÇOS NO BACKEND ────────────────────────
// Busca o preço real de cada produto/pacote no banco — ignora o preço vindo do JS
$produtoObj = new Produto();
$pacoteObj  = new Pacote();
$itensValidados = [];
$totalReal = 0;

foreach ($itensBrutos as $item) {
    $itemId     = (string) ($item['id'] ?? '');
    $quantidade = max(1, (int) ($item['quantidade'] ?? 1));
    $nomeProduto = trim(strip_tags($item['nome'] ?? ''));

    // ── Pacote: ID no formato "pacote_3" ou "pacote-timestamp" ──
    if (str_contains($itemId, 'pacote')) {
        // [1.1] CORREÇÃO: extrai o ID numérico do pacote e busca preço no banco
        // Tenta capturar o id numérico depois do separador (ex: "pacote_3" → 3)
        preg_match('/\D(\d+)$/', $itemId, $matches);
        $pacoteId = isset($matches[1]) ? (int) $matches[1] : 0;

        if ($pacoteId <= 0) {
            // ID não reconhecível — descarta o item
            error_log('[pedido_cadastrar] Pacote com ID inválido descartado: ' . $itemId);
            continue;
        }

        $pacoteBanco = $pacoteObj->BuscarPorId($pacoteId);

        if (!$pacoteBanco || empty($pacoteBanco['ativo'])) {
            // Pacote não existe ou está inativo — descarta
            error_log('[pedido_cadastrar] Pacote ID ' . $pacoteId . ' não encontrado ou inativo.');
            continue;
        }

        // Preço vem EXCLUSIVAMENTE do banco — nunca do frontend
        $precoUnitario = round((float) ($pacoteBanco['preco'] ?? 0), 2);

        if ($precoUnitario <= 0) {
            error_log('[pedido_cadastrar] Pacote ID ' . $pacoteId . ' sem preço configurado.');
            continue;
        }

        $quantidade = min(max(1, $quantidade), 500);

        $itensValidados[] = [
            'produto_id'     => null,
            'nome_produto'   => $nomeProduto ?: ($pacoteBanco['titulo'] ?? 'Pacote ' . $pacoteBanco['quantidade'] . ' un.'),
            'quantidade'     => $quantidade,
            'preco_unitario' => $precoUnitario,
        ];
        $totalReal += $precoUnitario * $quantidade;
        continue;
    }

    // ── Produto normal: busca preço real no banco ──
    $produto_id = filter_var($itemId, FILTER_VALIDATE_INT);

    if (!$produto_id) {
        continue;
    }

    $produto = $produtoObj->BuscarPorId($produto_id);

    if (!$produto || empty($produto['ativo'])) {
        // Produto não existe ou está inativo — pula
        continue;
    }

    $precoReal  = (float) $produto['preco'];
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
    redirectTo('pages/carrinho.php?erro=itens_invalidos');
}

$subtotalItens = round($totalReal, 2);
$taxaEntrega = round((float) ($areaEntrega['taxa'] ?? 0), 2);
$totalFinal = round($subtotalItens + $taxaEntrega, 2);

// ── 4. Cria o pedido ─────────────────────────────────────────
$pedido                 = new Pedido();
$pedido->usuario_id     = $usuario_id;
$pedido->nome_cliente   = $nome;
$pedido->telefone       = $telefone;
$pedido->endereco       = $endereco;
$pedido->cep_entrega    = $cep;
$pedido->area_entrega   = $areaEntrega['nome'] ?? null;
$pedido->data_entrega   = $data_entrega;
$pedido->horario_entrega = $horarioEntregaDb;
$pedido->observacao     = $obs;
$pedido->forma_pagamento = $forma_pagamento;
$pedido->taxa_entrega   = $taxaEntrega;
$pedido->total          = $totalFinal;
$pedido->itens          = $itensValidados;

$pedido_id = $pedido->Criar();

if (!$pedido_id) {
    error_log('[pedido_cadastrar] Falha ao criar pedido para ' . $nome);
    redirectTo('/pages/checkout.php?erro=servidor');
}

// ── 5. Salva endereço do cliente para os próximos checkouts ──
try {
    $usuario = new Usuario();
    $usuario->telefone = $telefone;
    $usuario->cep = $cep;
    $usuario->logradouro = $logradouro;
    $usuario->numero = $numero;
    $usuario->complemento = $complemento;
    $usuario->bairro = $bairro;
    $usuario->cidade = $cidade;
    $usuario->uf = $uf;
    $usuario->SalvarDadosEntrega($usuario_id);

    if (!empty($_SESSION['usuario']) && is_array($_SESSION['usuario'])) {
        $_SESSION['usuario']['telefone'] = $telefone;
        $_SESSION['usuario']['cep'] = $cep;
        $_SESSION['usuario']['logradouro'] = $logradouro;
        $_SESSION['usuario']['numero'] = $numero;
        $_SESSION['usuario']['complemento'] = $complemento;
        $_SESSION['usuario']['bairro'] = $bairro;
        $_SESSION['usuario']['cidade'] = $cidade;
        $_SESSION['usuario']['uf'] = $uf;
    }
} catch (Throwable $exception) {
    error_log('[pedido_cadastrar] Falha ao salvar dados de entrega do usuario ' . $usuario_id . ': ' . $exception->getMessage());
}

// ── 6. Sucesso — redireciona para página de confirmação ───────
redirectTo('/pages/pedido_confirmado.php?id=' . $pedido_id);
