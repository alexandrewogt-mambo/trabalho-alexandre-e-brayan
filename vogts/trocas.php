<?php
session_start();
require 'config/conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$mensagem = '';
$venda = null;
$itens = [];
$diasPassados = 0;

// Registrar a devolução e gerar vale-crédito (RF08, RN07)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirmar_devolucao'])) {
    $venda_id   = (int)$_POST['venda_id'];
    $produto_id = (int)$_POST['produto_id'];
    $valor      = (float)$_POST['valor_credito'];
    $validade   = date('Y-m-d', strtotime('+90 days')); // Validade de 90 dias (RN07)

    try {
        $pdo->beginTransaction();

        $stmtDev = $pdo->prepare("INSERT INTO devolucoes_creditos (venda_id, produto_id, valor_credito, data_validade) VALUES (?, ?, ?, ?)");
        $stmtDev->execute([$venda_id, $produto_id, $valor, $validade]);
        $vale_id = $pdo->lastInsertId();

        // Retorna 1 unidade da peça devolvida ao estoque
        $stmtEst = $pdo->prepare("UPDATE produtos SET estoque = estoque + 1 WHERE id = ?");
        $stmtEst->execute([$produto_id]);

        $pdo->commit();
        $mensagem = "<div class='alert alert-success'>Devolução registrada! <strong>Vale-Crédito #$vale_id</strong> gerado no valor de <strong>R$ " . number_format($valor, 2, ',', '.') . "</strong>, válido até <strong>" . date('d/m/Y', strtotime($validade)) . "</strong> (90 dias).</div>";
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = "<div class='alert alert-danger'>Erro ao processar troca: " . $e->getMessage() . "</div>";
    }
}

// Buscar venda pelo número do cupom
if (isset($_GET['venda_id']) && !empty($_GET['venda_id'])) {
    $vid = (int)$_GET['venda_id'];
    $stmtV = $pdo->prepare("SELECT *, DATEDIFF(NOW(), data_venda) AS dias_passados FROM vendas WHERE id = ? AND status = 'concluida'");
    $stmtV->execute([$vid]);
    $venda = $stmtV->fetch(PDO::FETCH_ASSOC);

    if ($venda) {
        $diasPassados = (int)$venda['dias_passados'];
        $stmtI = $pdo->prepare("
            SELECT iv.*, p.codigo, p.nome, p.categoria, p.tamanho 
            FROM itens_venda iv 
            JOIN produtos p ON iv.produto_id = p.id 
            WHERE iv.venda_id = ?
        ");
        $stmtI->execute([$vid]);
        $itens = $stmtI->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $mensagem = "<div class='alert alert-danger'>Venda não encontrada ou já cancelada.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOGTS - Trocas e Devoluções</title>
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
            <a href="trocas.php" class="active">Trocas</a>
            <a href="logout.php" style="background: #d9534f;">Sair</a>
        </div>
    </nav>

    <div class="container">
        <?= $mensagem ?>

        <div class="card">
            <h2>Consultar Cupom para Troca (RF08)</h2>
            <form method="GET" style="display: flex; gap: 10px;">
                <input type="number" name="venda_id" placeholder="Digite o número do Cupom / Venda (Ex: 1)" required style="flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                <button type="submit" class="btn btn-primary">Buscar Venda</button>
            </form>
        </div>

        <?php if ($venda): ?>
            <div class="card">
                <h2>Itens da Venda #<?= $venda['id'] ?> (Realizada há <?= $diasPassados ?> dias)</h2>

                <!-- Regra RN02: Prazo máximo de 30 dias -->
                <?php if ($diasPassados > 30): ?>
                    <div class="alert alert-danger">
                        ⚠️ <strong>Troca Bloqueada (RN02):</strong> Esta compra ultrapassou o prazo máximo de 30 dias para troca ou devolução.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Código</th>
                                    <th>Peça</th>
                                    <th>Categoria</th>
                                    <th>Valor Pago</th>
                                    <th>Ação</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($itens as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['codigo']) ?></td>
                                        <td><?= htmlspecialchars($item['nome']) ?> (<?= $item['tamanho'] ?>)</td>
                                        <td><?= htmlspecialchars($item['categoria']) ?></td>
                                        <td>R$ <?= number_format($item['preco_unitario'], 2, ',', '.') ?></td>
                                        <td>
                                            <!-- Regra RN05: Bloqueio para Moda Íntima -->
                                            <?php if ($item['categoria'] === 'Moda Íntima'): ?>
                                                <span class="badge-alerta">Troca Bloqueada: Moda Íntima (RN05)</span>
                                            <?php else: ?>
                                                <form method="POST" onsubmit="return confirm('Confirmar devolução desta peça e gerar vale-crédito?');">
                                                    <input type="hidden" name="venda_id" value="<?= $venda['id'] ?>">
                                                    <input type="hidden" name="produto_id" value="<?= $item['produto_id'] ?>">
                                                    <input type="hidden" name="valor_credito" value="<?= $item['preco_unitario'] ?>">
                                                    <button type="submit" name="confirmar_devolucao" class="btn btn-primary" style="padding: 5px 12px; font-size: 0.85rem;">Gerar Vale-Crédito</button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
