<?php
session_start();
require 'config/conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$mensagem = '';

// Cadastrar Cliente (RF04)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar_cliente'])) {
    $nome     = trim($_POST['nome']);
    $cpf      = trim($_POST['cpf']);
    $telefone = trim($_POST['telefone']);
    $email    = trim($_POST['email']);

    try {
        $stmt = $pdo->prepare("INSERT INTO clientes (nome, cpf, telefone, email) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nome, $cpf ?: null, $telefone, $email]);
        $mensagem = "<div class='alert alert-success'>Cliente cadastrado com sucesso!</div>";
    } catch (PDOException $e) {
        $mensagem = "<div class='alert alert-danger'>Erro ao cadastrar: CPF já registrado ou dados inválidos.</div>";
    }
}

$clientes = $pdo->query("SELECT * FROM clientes ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

// Histórico do Cliente Selecionado (RF11)
$historico = [];
$clienteSelecionado = null;
if (isset($_GET['historico_id'])) {
    $cid = (int)$_GET['historico_id'];
    $stmtC = $pdo->prepare("SELECT * FROM clientes WHERE id = ?");
    $stmtC->execute([$cid]);
    $clienteSelecionado = $stmtC->fetch(PDO::FETCH_ASSOC);

    $stmtH = $pdo->prepare("
        SELECT v.*, u.nome AS vendedor 
        FROM vendas v 
        JOIN usuarios u ON v.usuario_id = u.id 
        WHERE v.cliente_id = ? 
        ORDER BY v.data_venda DESC
    ");
    $stmtH->execute([$cid]);
    $historico = $stmtH->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOGTS - Clientes</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <nav class="navbar">
        <a href="dashboard.php" class="brand">VOGTS</a>
        <div class="nav-links">
            <a href="dashboard.php">Início</a>
            <a href="pdv.php">Nova Venda (PDV)</a>
            <a href="produtos.php">Produtos / Estoque</a>
            <a href="clientes.php" class="active">Clientes</a>
            <a href="trocas.php">Trocas</a>
            <?php if ($_SESSION['usuario_perfil'] === 'gerente'): ?>
                <a href="relatorios.php">Relatórios</a>
                <a href="usuarios.php">Usuários</a>
            <?php endif; ?>
            <a href="logout.php" style="background: #d9534f;">Sair</a>
        </div>
    </nav>

    <div class="container">
        <?= $mensagem ?>

        <div class="card">
            <h2>Cadastrar Cliente (RF04)</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Nome Completo</label>
                        <input type="text" name="nome" required>
                    </div>
                    <div class="form-group">
                        <label>CPF</label>
                        <input type="text" name="cpf" placeholder="000.000.000-00">
                    </div>
                    <div class="form-group">
                        <label>Telefone / WhatsApp</label>
                        <input type="text" name="telefone" required placeholder="(41) 99999-9999">
                    </div>
                    <div class="form-group">
                        <label>E-mail</label>
                        <input type="email" name="email" placeholder="cliente@email.com">
                    </div>
                </div>
                <button type="submit" name="cadastrar_cliente" class="btn btn-primary" style="margin-top: 15px;">Salvar Cliente</button>
            </form>
        </div>

        <?php if ($clienteSelecionado): ?>
            <div class="card" style="border-left: 5px solid var(--accent);">
                <h2>Histórico de Compras: <?= htmlspecialchars($clienteSelecionado['nome']) ?> (RF11)</h2>
                <?php if (count($historico) > 0): ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Venda #</th>
                                    <th>Data</th>
                                    <th>Vendedor</th>
                                    <th>Pagamento</th>
                                    <th>Valor Final</th>
                                    <th>Status</th>
                                    <th>Cupom</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($historico as $h): ?>
                                    <tr>
                                        <td>#<?= $h['id'] ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($h['data_venda'])) ?></td>
                                        <td><?= htmlspecialchars($h['vendedor']) ?></td>
                                        <td><?= strtoupper($h['forma_pagamento']) ?></td>
                                        <td>R$ <?= number_format($h['valor_final'], 2, ',', '.') ?></td>
                                        <td><?= strtoupper($h['status']) ?></td>
                                        <td><a href="cupom.php?id=<?= $h['id'] ?>" target="_blank" class="btn btn-primary" style="padding: 4px 10px; font-size: 0.8rem;">Ver Cupom</a></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>Este cliente ainda não possui compras registradas.</p>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <h2>Clientes Cadastrados</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>CPF</th>
                            <th>Telefone</th>
                            <th>E-mail</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($clientes as $c): ?>
                            <tr>
                                <td><?= htmlspecialchars($c['nome']) ?></td>
                                <td><?= htmlspecialchars($c['cpf'] ?? '-') ?></td>
                                <td><?= htmlspecialchars($c['telefone']) ?></td>
                                <td><?= htmlspecialchars($c['email']) ?></td>
                                <td><a href="clientes.php?historico_id=<?= $c['id'] ?>" class="btn btn-primary" style="padding: 5px 12px; font-size: 0.85rem;">Ver Histórico</a></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
