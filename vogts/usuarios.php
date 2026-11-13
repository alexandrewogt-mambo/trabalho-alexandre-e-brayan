<?php
session_start();
require 'config/conexao.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'gerente') {
    header("Location: dashboard.php");
    exit;
}

$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_usuario'])) {
    $nome   = trim($_POST['nome']);
    $login  = trim($_POST['login']);
    $senha  = password_hash($_POST['senha'], PASSWORD_DEFAULT);
    $perfil = $_POST['perfil'];

    try {
        $stmt = $pdo->prepare("INSERT INTO usuarios (nome, login, senha, perfil) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nome, $login, $senha, $perfil]);
        $mensagem = "<div class='alert alert-success'>Funcionário cadastrado com sucesso!</div>";
    } catch (PDOException $e) {
        $mensagem = "<div class='alert alert-danger'>Erro: Login já existe no sistema.</div>";
    }
}

$usuarios = $pdo->query("SELECT id, nome, login, perfil FROM usuarios ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOGTS - Usuários</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <nav class="navbar">
        <a href="dashboard.php" class="brand">VOGTS</a>
        <div class="nav-links">
            <a href="dashboard.php">Início</a>
            <a href="pdv.php">Nova Venda (PDV)</a>
            <a href="produtos.php">Produtos / Estoque</a>
            <a href="relatorios.php">Relatórios</a>
            <a href="usuarios.php" class="active">Usuários</a>
            <a href="logout.php" style="background: #d9534f;">Sair</a>
        </div>
    </nav>

    <div class="container">
        <?= $mensagem ?>

        <div class="card">
            <h2>Cadastrar Funcionário (RF10)</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nome do Funcionário</label>
                        <input type="text" name="nome" required>
                    </div>
                    <div class="form-group">
                        <label>Login de Acesso</label>
                        <input type="text" name="login" required>
                    </div>
                    <div class="form-group">
                        <label>Senha</label>
                        <input type="password" name="senha" required>
                    </div>
                    <div class="form-group">
                        <label>Perfil de Acesso</label>
                        <select name="perfil" required>
                            <option value="vendedor">Vendedor</option>
                            <option value="gerente">Gerente</option>
                        </select>
                    </div>
                </div>
                <button type="submit" name="cadastrar_usuario" class="btn btn-primary" style="margin-top: 15px;">Cadastrar Usuário</button>
            </form>
        </div>

        <div class="card">
            <h2>Equipe Cadastrada</h2>
            <table>
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Login</th>
                        <th>Perfil</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td><?= htmlspecialchars($u['nome']) ?></td>
                            <td><?= htmlspecialchars($u['login']) ?></td>
                            <td><strong><?= strtoupper($u['perfil']) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
