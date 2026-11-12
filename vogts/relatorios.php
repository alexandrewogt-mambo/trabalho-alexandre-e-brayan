<?php
session_start();
require 'config/conexao.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'gerente') {
    header("Location: dashboard.php");
    exit;
}

$mensagem = '';

// Cancelamento de Venda Concluída pelo Gerente (RN06)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancelar_venda_gerente'])) {
    $venda_id = (int)$_POST['venda_id'];
    $senha_gerente = $_POST['senha_gerente'];

    $stmtG = $pdo->prepare("SELECT senha FROM usuarios WHERE id = ?");
    $stmtG->execute([$_SESSION['usuario_id']]);
    $gerente = $stmtG->fetch(PDO::FETCH_ASSOC);

    if ($gerente && password_verify($senha_gerente, $gerente['senha'])) {
        try {
            $pdo->beginTransaction();

            // Marca venda como cancelada
            $stmtUp = $pdo->prepare("UPDATE vendas SET status = 'cancelada' WHERE id = ? AND status = 'concluida'");
            $stmtUp->execute([$venda_id]);

            if ($stmtUp->rowCount() > 0) {
                // Devolve todos os itens ao estoque
                $stmtItens = $pdo->prepare("SELECT produto_id, quantidade FROM itens_venda WHERE venda_id = ?");
                $stmtItens->execute([$venda_id]);
                $itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

                $stmtEst = $pdo->prepare("UPDATE produtos SET estoque = estoque + ? WHERE id = ?");
                foreach ($itens as $it) {
                    $stmtEst->execute([$it['quantidade'], $it['produto_id']]);
                }
                $pdo->commit();
                $mensagem = "<div class='alert alert-success'>Venda #$venda_id cancelada com sucesso! Estoque e faturamento revertidos.</div>";
            } else {
                $pdo->rollBack();
                $mensagem = "<div class='alert alert-danger'>Esta venda já estava cancelada.</div>";
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $mensagem = "<div class='alert alert-danger'>Erro ao cancelar venda.</div>";
        }
    } else {
        $mensagem = "<div class='alert alert-danger'>Senha do gerente incorreta! Cancelamento não autorizado.</div>";
    }
}

// Faturamento Diário e Mensal (RF07 - ignora vendas canceladas)
$fatHoje = $pdo->query("SELECT COALESCE(SUM(valor_final), 0) FROM vendas WHERE DATE(data_venda) = CURDATE() AND status = 'concluida'")->fetchColumn();
$fatMes  = $pdo->query("SELECT COALESCE(SUM(valor_final), 0) FROM vendas WHERE MONTH(data_venda) = MONTH(CURDATE()) AND YEAR(data_venda) = YEAR(CURDATE()) AND status = 'concluida'")->fetchColumn();

$vendas = $pdo->query("
    SELECT v.*, u.nome AS vendedor 
    FROM vendas v 
    JOIN usuarios u ON v.usuario_id = u.id 
    ORDER BY v.data_venda DESC LIMIT 30
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOGTS - Relatórios Gerenciais</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <nav class="navbar">
        <a href="dashboard.php" class="brand">VOGTS</a>
        <div class="nav-links">
            <a href="dashboard.php">Início</a>
            <a href="pdv.php">Nova Venda (PDV)</a>
            <a href="produtos.php">Produtos / Estoque</a>
            <a href="clientes.php">Clientes</a>
            <a href="relatorios.php" class="active">Relatórios</a>
            <a href="usuarios.php">Usuários</a>
            <a href="logout.php" style="background: #d9534f;">Sair</a>
        </div>
    </nav>

    <div class="container">
        <?= $mensagem ?>

        <div class="form-grid" style="margin-bottom: 20px;">
            <div class="card" style="text-align: center; margin-bottom: 0;">
                <p style="color: #777; font-weight: 600;">Faturamento Hoje (RF07)</p>
                <h1 style="color: var(--success); margin-top: 10px;">R$ <?= number_format($fatHoje, 2, ',', '.') ?></h1>
            </div>
            <div class="card" style="text-align: center; margin-bottom: 0;">
                <p style="color: #777; font-weight: 600;">Faturamento no Mês (RF07)</p>
                <h1 style="color: var(--primary); margin-top: 10px;">R$ <?= number_format($fatMes, 2, ',', '.') ?></h1>
            </div>
        </div>

        <div class="card">
            <h2>Últimas Vendas & Cancelamento Gerencial (RN06)</h2>
            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Cupom</th>
                            <th>Data</th>
                            <th>Vendedor</th>
                            <th>Pagamento</th>
                            <th>Valor Final</th>
                            <th>Status</th>
                            <th>Estorno (Gerente)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($vendas as $v): ?>
                            <tr class="<?= $v['status'] === 'cancelada' ? 'estoque-baixo' : '' ?>">
                                <td>#<?= $v['id'] ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($v['data_venda'])) ?></td>
                                <td><?= htmlspecialchars($v['vendedor']) ?></td>
                                <td><?= strtoupper($v['forma_pagamento']) ?></td>
                                <td>R$ <?= number_format($v['valor_final'], 2, ',', '.') ?></td>
                                <td><strong><?= strtoupper($v['status']) ?></strong></td>
                                <td>
                                    <?php if ($v['status'] === 'concluida'): ?>
                                        <form method="POST" style="display: flex; gap: 5px;">
                                            <input type="hidden" name="venda_id" value="<?= $v['id'] ?>">
                                            <input type="password" name="senha_gerente" placeholder="Sua senha" required style="width: 110px; padding: 5px; border: 1px solid #ccc; border-radius: 4px;">
                                            <button type="submit" name="cancelar_venda_gerente" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;">Cancelar</button>
                                        </form>
                                    <?php else: ?>
                                        <span>Estornada</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
