<?php
session_start();
require 'config/conexao.php';

if (isset($_SESSION['usuario_id'])) {
    header("Location: dashboard.php");
    exit;
}

$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login']);
    $senha = $_POST['senha'];

    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE login = ?");
    $stmt->execute([$login]);
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($usuario && password_verify($senha, $usuario['senha'])) {
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_perfil'] = $usuario['perfil'];
        header("Location: dashboard.php");
        exit;
    } else {
        $erro = "Login ou senha inválidos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOGTS - Login</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-box">
            <h1>VOGTS</h1>
            <p>Sistema de Gestão & PDV</p>
            
            <?php if ($erro): ?>
                <div class="alert alert-danger"><?= $erro ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group" style="text-align: left; margin-bottom: 15px;">
                    <label>Usuário</label>
                    <input type="text" name="login" required placeholder="Ex: gerente ou vendedor">
                </div>
                <div class="form-group" style="text-align: left; margin-bottom: 20px;">
                    <label>Senha</label>
                    <input type="password" name="senha" required placeholder="••••••">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;">Entrar no Sistema</button>
            </form>
        </div>
    </div>
</body>
</html>
