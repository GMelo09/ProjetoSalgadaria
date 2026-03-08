<?php
/* =============================================================
 *  admin/actions/produto_salvar.php — Cria ou edita produto
 * ============================================================= */
require_once __DIR__ . '/../../includes/auth.php';
sessionStart();
requireAdmin();

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../classes/Produto.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false]);
    exit;
}

$id          = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$nome        = trim($_POST['nome']        ?? '');
$descricao   = trim($_POST['descricao']   ?? '');
$preco       = filter_input(INPUT_POST, 'preco', FILTER_VALIDATE_FLOAT);
$categoriaId = filter_input(INPUT_POST, 'categoria_id', FILTER_VALIDATE_INT);
$emoji       = trim($_POST['emoji']       ?? '');
$tag         = trim($_POST['tag']         ?? '');
$ativo       = isset($_POST['ativo']) ? 1 : 0;

if (!$nome || !$preco || !$categoriaId) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Preencha os campos obrigatórios.']);
    exit;
}

$dados = compact('nome', 'descricao', 'preco', 'categoria_id', 'emoji', 'tag', 'ativo');
$dados['categoria_id'] = $categoriaId;
$dados['ativo'] = $ativo ? '1' : '0'; // workaround para o método

try {
    $produtoObj = new Produto();

    if ($id) {
        $ok = $produtoObj->editarProduto($id, $dados);
        echo json_encode(['sucesso' => $ok, 'mensagem' => 'Produto atualizado com sucesso!']);
    } else {
        $newId = $produtoObj->criarProduto($dados);
        echo json_encode(['sucesso' => true, 'mensagem' => 'Produto criado com sucesso!', 'id' => $newId]);
    }
} catch (Exception $e) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Erro interno ao salvar produto.']);
}