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

    public function Cadastrar()
    {
        $sql = "INSERT INTO usuarios (nome, email, senha, telefone, id_tipo)
                VALUES (?, ?, ?, ?, 2)";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([
        $this->nome,
        $this->email,
        hash('sha256', $this->senha),
        $this->telefone,
        ]);
        $id = $banco->lastInsertId();
        Banco::desconectar();
        return $id;
    }

    public function Editar($id_usuario)
    {
        $sql = "UPDATE usuarios SET nome = ?, email = ?, telefone = ?, id_tipo = ? WHERE id = ?";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([
            $this->nome,
            $this->email,
            $this->telefone,
            $this->id_tipo,
            $id_usuario,
        ]);
        Banco::desconectar();
        return $comando->rowCount();
    }

    public function AlterarSenha($id_usuario, $senha_nova)
    {
        $sql = "UPDATE usuarios SET senha = ? WHERE id = ?";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([hash('sha256', $senha_nova), $id_usuario]);
        Banco::desconectar();
        return $comando->rowCount();
    }

    public function Excluir($id_usuario)
    {
        $sql = "DELETE FROM usuarios WHERE id = ?";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$id_usuario]);
        Banco::desconectar();
        return $comando->rowCount();
    }

     public function Logar()
    {
        $sql = "SELECT * FROM usuarios WHERE email = ? AND senha = ?";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([
            $this->email,
            hash('sha256', $this->senha)
        ]);
        $resultado = $comando->fetchAll(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $resultado;
    }

    public function BuscarPorId($id_usuario)
    {
        $sql = "SELECT u.*, t.tipo
                FROM usuarios u
                INNER JOIN usuario_tipo t ON u.id_tipo = t.id
                WHERE u.id = ?";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$id_usuario]);
        $usuario = $comando->fetch(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $usuario;
    }

    public function BuscarPorEmail($email)
    {
        $sql = "SELECT * FROM usuarios WHERE email = ?";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$email]);
        $usuario = $comando->fetch(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $usuario;
    }

    public function ListarTodos()
    {
        $sql = "SELECT u.*, t.tipo
                FROM usuarios u
                INNER JOIN usuario_tipo t ON u.id_tipo = t.id
                ORDER BY u.nome ASC";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute();
        $usuarios = $comando->fetchAll(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $usuarios;
    }

    public function ListarPorTipo($id_tipo)
    {
        $sql = "SELECT u.*, t.tipo
                FROM usuarios u
                INNER JOIN usuario_tipo t ON u.id_tipo = t.id
                WHERE u.id_tipo = ?
                ORDER BY u.nome ASC";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$id_tipo]);
        $usuarios = $comando->fetchAll(PDO::FETCH_ASSOC);
        Banco::desconectar();
        return $usuarios;
    }

    public function EmailExiste($email)
    {
        $sql = "SELECT COUNT(*) AS total FROM usuarios WHERE email = ?";
        $banco = Banco::conectar();
        $comando = $banco->prepare($sql);
        $comando->execute([$email]);
        $total = $comando->fetch(PDO::FETCH_ASSOC)['total'];
        Banco::desconectar();
        return $total > 0;
    }
}