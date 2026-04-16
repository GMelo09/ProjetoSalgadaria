<?php
require_once('banco_class.php');

class Usuario
{
    public $id;
    public $nome;
    public $email;
    public $senha;
    public $telefone;
    public $cep;
    public $logradouro;
    public $numero;
    public $complemento;
    public $bairro;
    public $cidade;
    public $uf;
    public $id_tipo;

    public function Cadastrar(): int|false
    {
        $sql       = "INSERT INTO usuarios (nome, email, senha, telefone, id_tipo) VALUES (?, ?, ?, ?, 2)";
        $hashSenha = password_hash($this->senha, PASSWORD_BCRYPT);
        $banco     = Banco::conectar();
        $comando   = $banco->prepare($sql);
        $ok        = $comando->execute([$this->nome, $this->email, $hashSenha, $this->telefone]);
        $id        = $ok ? (int) $banco->lastInsertId() : false;
        Banco::desconectar();
        return $id;
    }

    public function Editar(int $id_usuario): int
    {
        $sql     = "UPDATE usuarios SET nome = ?, email = ?, telefone = ?, id_tipo = ? WHERE id = ?";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$this->nome, $this->email, $this->telefone, $this->id_tipo, $id_usuario]);
        $linhas  = $comando->rowCount();
        Banco::desconectar();
        return $linhas;
    }

    public function AlterarSenha(int $id_usuario, string $senha_nova): int
    {
        $sql     = "UPDATE usuarios SET senha = ? WHERE id = ?";
        $hash    = password_hash($senha_nova, PASSWORD_BCRYPT);
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$hash, $id_usuario]);
        $linhas  = $comando->rowCount();
        Banco::desconectar();
        return $linhas;
    }

    public function SalvarDadosEntrega(int $id_usuario): int
    {
        $sql = "UPDATE usuarios
                SET telefone = ?, cep = ?, logradouro = ?, numero = ?, complemento = ?, bairro = ?, cidade = ?, uf = ?
                WHERE id = ?";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([
            $this->telefone,
            $this->cep,
            $this->logradouro,
            $this->numero,
            $this->complemento,
            $this->bairro,
            $this->cidade,
            $this->uf,
            $id_usuario,
        ]);
        $linhas = $comando->rowCount();
        Banco::desconectar();
        return $linhas;
    }

    public function Excluir(int $id_usuario): int
    {
        $sql     = "DELETE FROM usuarios WHERE id = ?";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$id_usuario]);
        $linhas  = $comando->rowCount();
        Banco::desconectar();
        return $linhas;
    }

    public function AlterarBloqueio(int $id_usuario, int $bloqueado): int
    {
        $sql     = "UPDATE usuarios SET bloqueado = ? WHERE id = ?";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$bloqueado ? 1 : 0, $id_usuario]);
        $linhas  = $comando->rowCount();
        Banco::desconectar();
        return $linhas;
    }

    public function Logar(): array|null
    {
        // Bloqueia login de usuários bloqueados diretamente na query
        $sql     = "SELECT * FROM usuarios WHERE email = ? AND bloqueado = 0 LIMIT 1";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$this->email]);
        $usuario = $comando->fetch(PDO::FETCH_ASSOC);
        Banco::desconectar();

        if (!$usuario) {
            // Simula tempo de hash para evitar timing attack
            password_verify('dummy', '$2y$10$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ01234');
            return null;
        }

        $hashArmazenado = $usuario['senha'];

        // Migração transparente SHA-256 → bcrypt
        if (!str_starts_with($hashArmazenado, '$2y$')) {
            if (!hash_equals($hashArmazenado, hash('sha256', $this->senha))) return null;
            $this->AlterarSenha((int) $usuario['id'], $this->senha);
            return $usuario;
        }

        if (!password_verify($this->senha, $hashArmazenado)) return null;

        if (password_needs_rehash($hashArmazenado, PASSWORD_BCRYPT)) {
            $this->AlterarSenha((int) $usuario['id'], $this->senha);
        }

        return $usuario;
    }

    public function BuscarPorId(int $id_usuario): array|false
    {
        $sql     = "SELECT u.*, t.tipo FROM usuarios u
                    INNER JOIN usuario_tipo t ON u.id_tipo = t.id WHERE u.id = ?";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$id_usuario]);
        $usuario = $comando->fetch(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $usuario;
    }

    public function BuscarPorEmail(string $email): array|false
    {
        $sql     = "SELECT * FROM usuarios WHERE email = ? LIMIT 1";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$email]);
        $usuario = $comando->fetch(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $usuario;
    }

    /**
     * Lista todos os usuários.
     * Inclui: total_pedidos (LEFT JOIN), eh_admin (id_tipo = 1), bloqueado.
     */
    public function ListarTodos(): array
    {
        $sql = "SELECT
                    u.*,
                    t.tipo,
                    (u.id_tipo = 1)  AS eh_admin,
                    COUNT(p.id)      AS total_pedidos
                FROM usuarios u
                INNER JOIN usuario_tipo t ON u.id_tipo = t.id
                LEFT JOIN pedidos p ON p.usuario_id = u.id
                GROUP BY u.id
                ORDER BY u.nome ASC";
        $banco    = Banco::conectar();
        $comando  = $banco->prepare($sql);
        $comando->execute();
        $usuarios = $comando->fetchAll(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $usuarios;
    }

    public function ListarPorTipo(int $id_tipo): array
    {
        $sql     = "SELECT u.*, t.tipo FROM usuarios u
                    INNER JOIN usuario_tipo t ON u.id_tipo = t.id
                    WHERE u.id_tipo = ? ORDER BY u.nome ASC";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$id_tipo]);
        $usuarios = $comando->fetchAll(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $usuarios;
    }

    public function EmailExiste(string $email): bool
    {
        $sql     = "SELECT COUNT(*) AS total FROM usuarios WHERE email = ?";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$email]);
        $total   = (int) $comando->fetch(PDO::FETCH_ASSOC)['total'];
        Banco::desconectar();
        return $total > 0;
    }
    // usuario_class.php — adicionar este método
    public function CriarAdmin(string $nome, string $email, string $senha, string $telefone, int $id_tipo): int
    {
        $sql     = "INSERT INTO usuarios (nome, email, senha, telefone, id_tipo) VALUES (?, ?, ?, ?, ?)";
        $banco   = Banco::conectar();
        $comando = $banco->prepare($sql);
        $ok      = $comando->execute([
            $nome,
            $email,
            password_hash($senha, PASSWORD_BCRYPT),
            $telefone,
            $id_tipo,
        ]);
        $id = $ok ? (int) $banco->lastInsertId() : 0;
        Banco::desconectar();
        return $id;
    }
}
