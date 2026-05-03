<?php

require_once __DIR__ . '/../includes/auth.php';
sessionStart();
requireAdminAjax();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Metodo invalido.']);
    exit;
}

csrfValidar();

require_once __DIR__ . '/../classes/produto_class.php';

$id           = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: null;
$nome         = trim(filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$descricao    = trim(filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$categoria_id = filter_input(INPUT_POST, 'categoria_id', FILTER_VALIDATE_INT);
$preco        = filter_input(INPUT_POST, 'preco', FILTER_VALIDATE_FLOAT);
$tag          = trim(filter_input(INPUT_POST, 'tag', FILTER_SANITIZE_SPECIAL_CHARS) ?? 'Classico');
$ativo        = isset($_POST['ativo']) ? 1 : 0;

if (empty($nome)) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'O nome e obrigatorio.']);
    exit;
}

if (!$categoria_id || $categoria_id <= 0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Categoria invalida.']);
    exit;
}

if (!$preco || $preco <= 0) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Preco invalido.']);
    exit;
}

// -- Processar upload de imagem
$imagem_nome = null; // null = nao alterar a imagem existente

if (isset($_FILES['imagem']) && $_FILES['imagem']['error'] !== UPLOAD_ERR_NO_FILE) {
    if ($_FILES['imagem']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Erro ao receber o arquivo de imagem.']);
        exit;
    }

    if ($_FILES['imagem']['size'] > 5 * 1024 * 1024) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Imagem muito grande. Maximo: 5 MB.']);
        exit;
    }

    // Valida MIME real pelos magic bytes — nao confia na extensao nem no nome enviado pelo cliente
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($_FILES['imagem']['tmp_name']);

    $mimesPermitidos = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/gif'  => 'gif',
        'image/webp' => 'webp',
    ];

    if (!array_key_exists($mime, $mimesPermitidos)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Formato invalido. Use JPG, PNG, GIF ou WEBP.']);
        exit;
    }

    // Extensao derivada do MIME real — ignora a extensao enviada pelo cliente
    $ext = $mimesPermitidos[$mime];

    $dir = __DIR__ . '/../uploads/produtos/';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $imagem_nome = uniqid('prod_') . '.' . $ext;
    if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $dir . $imagem_nome)) {
        echo json_encode(['sucesso' => false, 'mensagem' => 'Falha ao salvar a imagem no servidor.']);
        exit;
    }
}

// -- Montar objeto Produto
$produto               = new Produto();
$produto->nome         = $nome;
$produto->descricao    = $descricao;
$produto->categoria_id = $categoria_id;
$produto->preco        = round($preco, 2);
$produto->tag          = $tag;
$produto->ativo        = $ativo;
$produto->imagem       = $imagem_nome; // null = sem nova imagem; string = nome do arquivo novo

if ($id) {
    // Remove a imagem antiga do disco quando uma nova e enviada
    if ($imagem_nome !== null) {
        $produtoAtual = $produto->BuscarPorId($id);
        if (!empty($produtoAtual['imagem'])) {
            $caminhoAntigo = __DIR__ . '/../uploads/produtos/' . $produtoAtual['imagem'];
            if (is_file($caminhoAntigo)) {
                unlink($caminhoAntigo);
            }
        }
    }

    $ok = $produto->Editar($id);
    echo json_encode([
        'sucesso'  => $ok >= 0,
        'mensagem' => $ok >= 0 ? 'Produto atualizado com sucesso!' : 'Erro ao atualizar produto.',
    ]);
} else {
    $novo_id = $produto->Criar();
    echo json_encode([
        'sucesso'  => $novo_id > 0,
        'mensagem' => $novo_id > 0 ? 'Produto criado com sucesso!' : 'Erro ao criar produto.',
    ]);
}
exit;
