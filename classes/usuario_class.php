<?php
require_once('banco_class.php');

class Usuario
{
    public $id;
    public $nome;
    public $email;
    public $senha;
    public $telefone;
    public $id_tipo;

    /* =============================================================
     *  IMPORTANTE: Todas as senhas agora usam password_hash()
     *  com bcrypt (PASSWORD_BCRYPT), que é intencionalmente lento
     *  e resistente a ataques de força bruta por GPU.
     *
     *  O SHA-256 antigo era rápido demais — um atacante com GPU
     *  consegue testar bilhões de hashes/segundo. Bcrypt força
     *  milissegundos por tentativa, tornando ataques inviáveis.
     * ============================================================= */

    public function Cadastrar(): int|false
    {
        $sql = "INSERT INTO usuarios (nome, email, senha, telefone, id_tipo)
                VALUES (?, ?, ?, ?, 2)";

        $hashSenha = password_hash($this->senha, PASSWORD_BCRYPT);

        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $ok      = $comando->execute([
            $this->nome,
            $this->email,
            $hashSenha,
            $this->telefone,
        ]);

        $id = $ok ? (int) $banco->lastInsertId() : false;
        Banco::desconectar();
        return $id;
    }

    public function Editar(int $id_usuario): int
    {
        $sql = "UPDATE usuarios SET nome = ?, email = ?, telefone = ?, id_tipo = ? WHERE id = ?";

        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([
            $this->nome,
            $this->email,
            $this->telefone,
            $this->id_tipo,
            $id_usuario,
        ]);
        $linhas = $comando->rowCount();
        Banco::desconectar();
        return $linhas;
    }

    public function AlterarSenha(int $id_usuario, string $senha_nova): int
    {
        $sql = "UPDATE usuarios SET senha = ? WHERE id = ?";

        $hash    = password_hash($senha_nova, PASSWORD_BCRYPT);
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$hash, $id_usuario]);
        $linhas = $comando->rowCount();
        Banco::desconectar();
        return $linhas;
    }

    public function Excluir(int $id_usuario): int
    {
        $sql = "DELETE FROM usuarios WHERE id = ?";

        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$id_usuario]);
        $linhas = $comando->rowCount();
        Banco::desconectar();
        return $linhas;
    }

    /**
     * Busca o usuário pelo e-mail e verifica a senha com password_verify().
     *
     * Retorna o array do usuário em caso de sucesso, ou null em caso de falha.
     * Nunca retorna mensagens diferentes para "usuário não existe" vs "senha errada"
     * (prevenção de user enumeration).
     *
     * Também implementa migração transparente de SHA-256 → bcrypt:
     * Se o hash armazenado for SHA-256 (não começa com "$2y$"), verifica com
     * SHA-256 e, se correto, regrava o hash em bcrypt automaticamente.
     */
    public function Logar(): array|null
    {
        // Busca apenas pelo e-mail — a verificação da senha é feita em PHP
        $sql = "SELECT * FROM usuarios WHERE email = ? LIMIT 1";

        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$this->email]);
        $usuario = $comando->fetch(PDO::FETCH_ASSOC);
        Banco::desconectar();

        if (!$usuario) {
            // Usuário não encontrado — simula tempo de verificação para
            // evitar timing attack (user enumeration por diferença de tempo)
            password_verify('dummy', '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ01234');
            return null;
        }

        $hashArmazenado = $usuario['senha'];

        // ── Migração transparente SHA-256 → bcrypt ─────────────
        // Remove da base ao longo do tempo sem forçar reset de senha.
        if (!str_starts_with($hashArmazenado, '$2y$')) {
            // Hash antigo (SHA-256) — verifica e migra se correto
            if (!hash_equals($hashArmazenado, hash('sha256', $this->senha))) {
                return null; // Senha errada
            }
            // Senha correta com hash antigo — regrava em bcrypt
            $this->AlterarSenha((int) $usuario['id'], $this->senha);
            return $usuario;
        }

        // ── Verificação normal com bcrypt ──────────────────────
        if (!password_verify($this->senha, $hashArmazenado)) {
            return null;
        }

        // Regravar se o custo do bcrypt foi atualizado (futuro-safe)
        if (password_needs_rehash($hashArmazenado, PASSWORD_BCRYPT)) {
            $this->AlterarSenha((int) $usuario['id'], $this->senha);
        }

        return $usuario;
    }

    public function BuscarPorId(int $id_usuario): array|false
    {
        $sql = "SELECT u.*, t.tipo
                FROM usuarios u
                INNER JOIN usuario_tipo t ON u.id_tipo = t.id
                WHERE u.id = ?";

        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$id_usuario]);
        $usuario = $comando->fetch(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $usuario;
    }

    public function BuscarPorEmail(string $email): array|false
    {
        $sql = "SELECT * FROM usuarios WHERE email = ? LIMIT 1";

        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$email]);
        $usuario = $comando->fetch(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $usuario;
    }

    public function ListarTodos(): array
    {
        $sql = "SELECT u.*, t.tipo
                FROM usuarios u
                INNER JOIN usuario_tipo t ON u.id_tipo = t.id
                ORDER BY u.nome ASC";

        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute();
        $usuarios = $comando->fetchAll(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $usuarios;
    }

    public function ListarPorTipo(int $id_tipo): array
    {
        $sql = "SELECT u.*, t.tipo
                FROM usuarios u
                INNER JOIN usuario_tipo t ON u.id_tipo = t.id
                WHERE u.id_tipo = ?
                ORDER BY u.nome ASC";

        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$id_tipo]);
        $usuarios = $comando->fetchAll(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $usuarios;
    }

    public function EmailExiste(string $email): bool
    {
        $sql = "SELECT COUNT(*) AS total FROM usuarios WHERE email = ?";

        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$email]);
        $total = (int) $comando->fetch(PDO::FETCH_ASSOC)['total'];
        Banco::desconectar();
        return $total > 0;
    }
}