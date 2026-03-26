<?php
/**
 * actions/produto_salvar.php
 * Cria ou edita um produto via AJAX do dashboard.
 * Retorna JSON.
 */

require_once __DIR__ . '/../includes/auth.php';
sessionStart();
requireAdminAjax();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método inválido.']);
    exit;
}

require_once __DIR__ . '/../classes/produto_class.php';

$id          = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: null;
$nome        = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$descricao   = trim(filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$categoria_id = filter_input(INPUT_POST, 'categoria_id', FILTER_VALIDATE_INT);
$preco       = filter_input(INPUT_POST, 'preco', FILTER_VALIDATE_FLOAT);
$emoji       = trim(filter_input(INPUT_POST, 'emoji', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$tag         = trim(filter_input(INPUT_POST, 'tag', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'Clássico');
$ativo       = isset($_POST['ativo']) ? 1 : 0;

if (empty($nome)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'O nome é obrigatório.']);
    exit;
}

if (!$categoria_id || $categoria_id <= 0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Categoria inválida.']);
    exit;
}

if (!$preco || $preco <= 0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Preço inválido.']);
    exit;
}

$produto               = new Produto();
$produto->nome         = $nome;
$produto->descricao    = $descricao;
$produto->categoria_id = $categoria_id;
$produto->preco        = round($preco, 2);
$produto->emoji        = $emoji;
$produto->tag          = $tag;
$produto->ativo        = $ativo;

if ($id) {
    // Editar produto existente
    $ok = $produto->Editar($id);
    echo json_encode([
        'sucesso'  => $ok >= 0,
        'mensagem' => 'Produto atualizado com sucesso!',
    ]);
} else {
    // Criar novo produto
    $novo_id = $produto->Criar();
    echo json_encode([
        'sucesso'  => $novo_id > 0,
        'mensagem' => $novo_id > 0 ? 'Produto criado com sucesso!' : 'Erro ao criar produto.',
    ]);
}
exit;