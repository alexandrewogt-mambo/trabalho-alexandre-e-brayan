<?php
session_start();
require 'config/conexao.php';

$erro = '';
$sucesso = '';

// Registrar Novo Cliente
if (isset($_POST['registrar'])) {
    $nome     = trim($_POST['nome']);
    $email    = trim($_POST['email']);
    $telefone = trim($_POST['telefone']);
    $cpf      = trim($_POST['cpf']);
    $senha    = password_hash($_POST['senha'], PASSWORD_DEFAULT);

    try {
        $stmt = $pdo->prepare("INSERT INTO clientes (nome, cpf, telefone, email, senha) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nome, $cpf ?: null, $telefone, $email, $senha]);
        
        // Loga o cliente automaticamente após cadastrar
        $_SESSION['cliente_id']   = $pdo->lastInsertId();
        $_SESSION['cliente_nome'] = $nome;
        header("Location: loja.php");
        exit;
    } catch (PDOException $e) {
        $erro = "Este e-mail ou CPF já está cadastrado. Tente fazer login.";
    }
}

// Login de Cliente Existente
if (isset($_POST['entrar_cliente'])) {
    $email = trim($_POST['email_login']);
    $senha = $_POST['senha_login'];

    $stmt = $pdo->prepare("SELECT * FROM clientes WHERE email = ?");
    $stmt->execute([$email]);
    $cliente = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($cliente && !empty($cliente['senha']) && password_verify($senha, $cliente['senha'])) {
        $_SESSION['cliente_id']   = $cliente['id'];
        $_SESSION['cliente_nome'] = $cliente['nome'];
        header("Location: loja.php");
        exit;
    } else {
        $erro = "E-mail ou senha incorretos.";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOGTS Online - Entrar ou Registrar-se</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <nav class="navbar">
        <a href="loja.php" class="brand">VOGTS <span style="font-size: 0.8rem; color: #fff; letter-spacing: 1px;">ONLINE</span></a>
        <div class="nav-links">
            <a href="loja.php">Ver Vitrine</a>
            <a href="index.php" style="font-size: 0.8rem; opacity: 0.7;">Área da Equipe</a>
        </div>
    </nav>

    <div class="container">
        <?php if ($erro): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px;">
            <!-- Coluna 1: Criar Conta -->
            <div class="card">
                <h2>Criar Nova Conta</h2>
                <p style="margin-bottom: 15px; color: #666; font-size: 0.9rem;">Cadastre-se para comprar online e acompanhar seus pedidos.</p>
                <form method="POST">
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>Nome Completo</label>
                        <input type="text" name="nome" required placeholder="Seu nome">
                    </div>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>E-mail</label>
                        <input type="email" name="email" required placeholder="seu@email.com">
                    </div>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>WhatsApp / Telefone</label>
                        <input type="text" name="telefone" required placeholder="(41) 99999-9999">
                    </div>
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>CPF (Opcional)</label>
                        <input type="text" name="cpf" placeholder="000.000.000-00">
                    </div>
                    <div class="form-group" style="margin-bottom: 18px;">
                        <label>Crie uma Senha</label>
                        <input type="password" name="senha" required placeholder="••••••">
                    </div>
                    <button type="submit" name="registrar" class="btn btn-primary" style="width: 100%;">Registrar-se e Ir para Loja</button>
                </form>
            </div>

            <!-- Coluna 2: Já tenho conta -->
            <div class="card">
                <h2>Já sou Cliente</h2>
                <p style="margin-bottom: 15px; color: #666; font-size: 0.9rem;">Entre com seu e-mail e senha para finalizar suas compras.</p>
                <form method="POST">
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>E-mail</label>
                        <input type="email" name="email_login" required placeholder="seu@email.com">
                    </div>
                    <div class="form-group" style="margin-bottom: 18px;">
                        <label>Senha</label>
                        <input type="password" name="senha_login" required placeholder="••••••">
                    </div>
                    <button type="submit" name="entrar_cliente" class="btn btn-primary" style="width: 100%; background: var(--accent); color: var(--primary);">Entrar na Loja</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
