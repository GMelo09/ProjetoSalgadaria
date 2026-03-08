<?php
/* =============================================================
 *  Produto — CRUD e consultas de produtos
 *  Todo SQL relativo a produtos fica AQUI.
 * ============================================================= */

class Produto
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /* =============================================================
     *  listarProdutos — Retorna todos os produtos ativos
     * ============================================================= */
    public function listarProdutos(int $categoriaId = 0): array
    {
        if ($categoriaId > 0) {
            $st = $this->db->prepare('
                SELECT p.*, c.nome AS categoria_nome, c.slug AS categoria_slug
                FROM produtos p
                JOIN categorias c ON c.id = p.categoria_id
                WHERE p.ativo = 1 AND p.categoria_id = ?
                ORDER BY p.nome ASC
            ');
            $st->execute([$categoriaId]);
        } else {
            $st = $this->db->prepare('
                SELECT p.*, c.nome AS categoria_nome, c.slug AS categoria_slug
                FROM produtos p
                JOIN categorias c ON c.id = p.categoria_id
                WHERE p.ativo = 1
                ORDER BY c.id ASC, p.nome ASC
            ');
            $st->execute();
        }

        return $st->fetchAll();
    }

    /* =============================================================
     *  listarTodos — Inclui inativos (uso admin)
     * ============================================================= */
    public function listarTodos(): array
    {
        $st = $this->db->prepare('
            SELECT p.*, c.nome AS categoria_nome
            FROM produtos p
            JOIN categorias c ON c.id = p.categoria_id
            ORDER BY c.id ASC, p.nome ASC
        ');
        $st->execute();
        return $st->fetchAll();
    }

    /* =============================================================
     *  buscarProduto — Busca produto por ID
     * ============================================================= */
    public function buscarProduto(int $id): array|false
    {
        $st = $this->db->prepare('
            SELECT p.*, c.nome AS categoria_nome
            FROM produtos p
            JOIN categorias c ON c.id = p.categoria_id
            WHERE p.id = ?
        ');
        $st->execute([$id]);
        return $st->fetch();
    }

    /* =============================================================
     *  criarProduto — Insere novo produto
     * ============================================================= */
    public function criarProduto(array $dados): int
    {
        $st = $this->db->prepare('
            INSERT INTO produtos (categoria_id, nome, descricao, preco, emoji, tag, ativo)
            VALUES (:categoria_id, :nome, :descricao, :preco, :emoji, :tag, :ativo)
        ');
        $st->execute([
            ':categoria_id' => (int) $dados['categoria_id'],
            ':nome'         => htmlspecialchars(strip_tags($dados['nome']), ENT_QUOTES, 'UTF-8'),
            ':descricao'    => htmlspecialchars(strip_tags($dados['descricao'] ?? ''), ENT_QUOTES, 'UTF-8'),
            ':preco'        => (float) $dados['preco'],
            ':emoji'        => $dados['emoji'] ?? null,
            ':tag'          => $dados['tag'] ?? null,
            ':ativo'        => isset($dados['ativo']) ? 1 : 0,
        ]);
        return (int) $this->db->lastInsertId();
    }

    /* =============================================================
     *  editarProduto — Atualiza produto existente
     * ============================================================= */
    public function editarProduto(int $id, array $dados): bool
    {
        $st = $this->db->prepare('
            UPDATE produtos
            SET categoria_id = :categoria_id,
                nome         = :nome,
                descricao    = :descricao,
                preco        = :preco,
                emoji        = :emoji,
                tag          = :tag,
                ativo        = :ativo
            WHERE id = :id
        ');
        return $st->execute([
            ':categoria_id' => (int) $dados['categoria_id'],
            ':nome'         => htmlspecialchars(strip_tags($dados['nome']), ENT_QUOTES, 'UTF-8'),
            ':descricao'    => htmlspecialchars(strip_tags($dados['descricao'] ?? ''), ENT_QUOTES, 'UTF-8'),
            ':preco'        => (float) $dados['preco'],
            ':emoji'        => $dados['emoji'] ?? null,
            ':tag'          => $dados['tag'] ?? null,
            ':ativo'        => isset($dados['ativo']) ? 1 : 0,
            ':id'           => $id,
        ]);
    }

    /* =============================================================
     *  excluirProduto — Soft delete (ativo = 0)
     * ============================================================= */
    public function excluirProduto(int $id): bool
    {
        $st = $this->db->prepare('UPDATE produtos SET ativo = 0 WHERE id = ?');
        return $st->execute([$id]);
    }

    /* =============================================================
     *  listarCategorias — Retorna todas as categorias
     * ============================================================= */
    public function listarCategorias(): array
    {
        $st = $this->db->prepare('SELECT * FROM categorias ORDER BY nome ASC');
        $st->execute();
        return $st->fetchAll();
    }
}
