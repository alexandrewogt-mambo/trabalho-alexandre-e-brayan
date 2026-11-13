<?php
session_start();
require 'config/conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

// Busca peças com menos de 2 unidades em estoque (RN01)
$stmtAlertas = $pdo->query("SELECT * FROM produtos WHERE estoque < 2 ORDER BY estoque ASC");
$produtosBaixoEstoque = $stmtAlertas->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOGTS - Painel</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <nav class="navbar">
        <a href="dashboard.php" class="brand">VOGTS</a>
        <div class="nav-links">
            <a href="dashboard.php" class="active">Início</a>
            <a href="pdv.php">Nova Venda (PDV)</a>
            <a href="produtos.php">Produtos / Estoque</a>
            <a href="clientes.php">Clientes</a>
            <a href="trocas.php">Trocas</a>
            <?php if ($_SESSION['usuario_perfil'] === 'gerente'): ?>
                <?php if ($_SESSION['usuario_perfil'] === 'gerente'): ?>
                <a href="relatorios.php">Relatórios</a>
                <a href="usuarios.php">Usuários</a>
                <a href="backup.php">Backup</a>
                <a href="loja_teste.php" style="color: var(--accent); font-weight: bold;">Loja Teste</a>
            <?php endif; ?>

                <a href="relatorios.php">Relatórios</a>
                <a href="usuarios.php">Usuários</a>
                <a href="backup.php">Backup</a>
            <?php endif; ?>
            <a href="logout.php" style="background: #d9534f;">Sair</a>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <h2>Bem-vindo(a), <?= htmlspecialchars($_SESSION['usuario_nome']) ?>!</h2>
            <p>Perfil de acesso: <strong><?= strtoupper($_SESSION['usuario_perfil']) ?></strong></p>
        </div>

        <!-- Alerta de Estoque Mínimo (RN01) -->
        <div class="card">
            <h2>⚠️ Alerta de Estoque Mínimo (< 2 unidades)</h2>
            <?php if (count($produtosBaixoEstoque) > 0): ?>
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Código</th>
                                <th>Peça</th>
                                <th>Categoria</th>
                                <th>Tam / Cor</th>
                                <th>Estoque Atual</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($produtosBaixoEstoque as $prod): ?>
                                <tr class="estoque-baixo">
                                    <td><?= htmlspecialchars($prod['codigo']) ?></td>
                                    <td><?= htmlspecialchars($prod['nome']) ?></td>
                                    <td><?= htmlspecialchars($prod['categoria']) ?></td>
                                    <td><?= htmlspecialchars($prod['tamanho']) ?> - <?= htmlspecialchars($prod['cor']) ?></td>
                                    <td><?= $prod['estoque'] ?> un. <span class="badge-alerta">REPOR</span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p style="color: var(--success); font-weight: 600;">Tudo certo! Nenhuma peça está abaixo do estoque mínimo.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
