<?php
require_once __DIR__ . '/../includes/auth.php';
sessionStart();
requireAdminAjax();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método inválido.']);
    exit;
}

require_once __DIR__ . '/../classes/pacote_class.php';

$id          = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: null;
$quantidade  = filter_input(INPUT_POST, 'quantidade', FILTER_VALIDATE_INT);
$max_sabores = filter_input(INPUT_POST, 'max_sabores', FILTER_VALIDATE_INT);
$descricao   = trim(filter_input(INPUT_POST, 'descricao', FILTER_SANITIZE_SPECIAL_CHARS) ?? '');
$popular     = isset($_POST['popular']) ? 1 : 0;
$ativo       = isset($_POST['ativo'])   ? 1 : 0;

if (!$quantidade || $quantidade < 1) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Quantidade inválida.']);
    exit;
}
if (!$max_sabores || $max_sabores < 1) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Máximo de sabores inválido.']);
    exit;
}

$pacote              = new Pacote();
$pacote->quantidade  = $quantidade;
$pacote->max_sabores = $max_sabores;
$pacote->descricao   = $descricao;
$pacote->popular     = $popular;
$pacote->ativo       = $ativo;

if ($id) {
    $ok = $pacote->Editar($id);
    // Se marcou como popular, garante que só ele seja
    if ($popular) $pacote->DefinirPopular($id);
    echo json_encode(['sucesso' => true, 'mensagem' => 'Pacote atualizado!']);
} else {
    $novo_id = $pacote->Criar();
    if ($popular && $novo_id) $pacote->DefinirPopular($novo_id);
    echo json_encode([
        'sucesso'  => $novo_id > 0,
        'mensagem' => $novo_id > 0 ? 'Pacote criado!' : 'Erro ao criar pacote.',
    ]);
}
exit;