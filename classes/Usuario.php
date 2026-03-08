<?php
/* =============================================================
 *  Usuario — Autenticação, cadastro e listagem de usuários
 *  Todo SQL relativo a usuários fica AQUI.
 * ============================================================= */

class Usuario
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /* =============================================================
     *  login — Autentica usuário pelo e-mail e senha
     *  Retorna array do usuário ou false em caso de falha
     * ============================================================= */
    public function login(string $email, string $senha): array|false
    {
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);

        $st = $this->db->prepare('
            SELECT id, nome, email, senha, eh_admin, bloqueado
            FROM usuarios
            WHERE email = ?
            LIMIT 1
        ');
        $st->execute([$email]);
        $usuario = $st->fetch();

        if (!$usuario) {
            return false;
        }

        if ((int) $usuario['bloqueado'] === 1) {
            return false;
        }

        if (!password_verify($senha, $usuario['senha'])) {
            return false;
        }

        // Remove hash da senha antes de retornar
        unset($usuario['senha'], $usuario['bloqueado']);
        return $usuario;
    }

    /* =============================================================
     *  registrar — Cadastra novo usuário
     *  Retorna ID do usuário criado ou false
     * ============================================================= */
    public function registrar(string $nome, string $email, string $senha, string $telefone = ''): int|false
    {
        $email = filter_var(trim($email), FILTER_SANITIZE_EMAIL);
        $nome  = htmlspecialchars(strip_tags(trim($nome)), ENT_QUOTES, 'UTF-8');
        $tel   = htmlspecialchars(strip_tags(trim($telefone)), ENT_QUOTES, 'UTF-8');

        // Verifica duplicidade de e-mail
        $check = $this->db->prepare('SELECT id FROM usuarios WHERE email = ? LIMIT 1');
        $check->execute([$email]);
        if ($check->fetch()) {
            return false;
        }

        $hash = password_hash($senha, PASSWORD_BCRYPT);

        $st = $this->db->prepare('
            INSERT INTO usuarios (nome, email, telefone, senha)
            VALUES (?, ?, ?, ?)
        ');
        $st->execute([$nome, $email, $tel ?: null, $hash]);

        return (int) $this->db->lastInsertId();
    }

    /* =============================================================
     *  listarUsuarios — Lista todos os usuários (uso admin)
     * ============================================================= */
    public function listarUsuarios(): array
    {
        $st = $this->db->prepare('
            SELECT id, nome, email, telefone, eh_admin, bloqueado, criado_em,
                   (SELECT COUNT(*) FROM pedidos WHERE usuario_id = usuarios.id) AS total_pedidos
            FROM usuarios
            ORDER BY criado_em DESC
        ');
        $st->execute();
        return $st->fetchAll();
    }

    /* =============================================================
     *  buscarUsuario — Busca usuário por ID
     * ============================================================= */
    public function buscarUsuario(int $id): array|false
    {
        $st = $this->db->prepare('
            SELECT id, nome, email, telefone, eh_admin, bloqueado, criado_em
            FROM usuarios
            WHERE id = ?
        ');
        $st->execute([$id]);
        return $st->fetch();
    }

    /* =============================================================
     *  alterarBloqueio — Bloqueia ou desbloqueia usuário
     * ============================================================= */
    public function alterarBloqueio(int $id, int $bloqueado): bool
    {
        $st = $this->db->prepare('UPDATE usuarios SET bloqueado = ? WHERE id = ?');
        return $st->execute([$bloqueado ? 1 : 0, $id]);
    }
}
