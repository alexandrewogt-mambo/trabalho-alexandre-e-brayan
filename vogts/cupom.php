<?php
session_start();
require 'config/conexao.php';

if (!isset($_SESSION['usuario_id']) || !isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$venda_id = (int)$_GET['id'];

$stmt = $pdo->prepare("
    SELECT v.*, u.nome AS vendedor, c.nome AS cliente 
    FROM vendas v 
    JOIN usuarios u ON v.usuario_id = u.id 
    LEFT JOIN clientes c ON v.cliente_id = c.id 
    WHERE v.id = ?
");
$stmt->execute([$venda_id]);
$venda = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$venda) {
    die("Venda não encontrada.");
}

$stmtItens = $pdo->prepare("
    SELECT iv.*, p.codigo, p.nome, p.tamanho 
    FROM itens_venda iv 
    JOIN produtos p ON iv.produto_id = p.id 
    WHERE iv.venda_id = ?
");
$stmtItens->execute([$venda_id]);
$itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Cupom VOGTS #<?php echo $venda['id']; ?></title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            background: #f4f5f7;
            display: flex;
            flex-direction: column;
            align-items: center;
            padding: 20px;
        }
        .cupom {
            background: #fff9db;
            width: 320px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            color: #111;
            font-size: 13px;
        }
        .center { text-align: center; }
        .divider { border-top: 1px dashed #333; margin: 10px 0; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 3px 0; }
        .right { text-align: right; }
        .actions { margin-top: 20px; display: flex; gap: 10px; }
        .btn { padding: 10px 15px; background: #111; color: #fff; text-decoration: none; border: none; cursor: pointer; font-family: sans-serif; border-radius: 4px; }
        @media print {
            body { background: #fff; padding: 0; }
            .cupom { box-shadow: none; background: #fff; width: 100%; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="cupom">
        <div class="center">
            <h2 style="margin: 0; letter-spacing: 3px;">VOGTS</h2>
            <p style="margin: 4px 0;">MODA & ESTILO</p>
            <small>COMPROVANTE DE VENDA #<?php echo str_pad($venda['id'], 6, '0', STR_PAD_LEFT); ?></small>
        </div>

        <div class="divider"></div>
        <p style="margin: 2px 0;"><strong>Data:</strong> <?php echo date('d/m/Y H:i', strtotime($venda['data_venda'])); ?></p>
        <p style="margin: 2px 0;"><strong>Vendedor:</strong> <?php echo htmlspecialchars($venda['vendedor']); ?></p>
        <p style="margin: 2px 0;"><strong>Cliente:</strong> <?php echo $venda['cliente'] ? htmlspecialchars($venda['cliente']) : 'Consumidor Final'; ?></p>
        <p style="margin: 2px 0;"><strong>CPF na Nota:</strong> <?php echo $venda['cpf_nota'] ? htmlspecialchars($venda['cpf_nota']) : 'Não informado'; ?></p>

        <div class="divider"></div>
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Qtd</th>
                    <th class="right">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itens as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['nome']); ?> (<?php echo $item['tamanho']; ?>)</td>
                        <td><?php echo $item['quantidade']; ?>x</td>
                        <td class="right">R$ <?php echo number_format($item['preco_unitario'] * $item['quantidade'], 2, ',', '.'); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="divider"></div>
        <p style="margin: 3px 0; display: flex; justify-content: space-between;">
            <span>Subtotal:</span>
            <span>R$ <?php echo number_format($venda['valor_bruto'], 2, ',', '.'); ?></span>
        </p>
        <p style="margin: 3px 0; display: flex; justify-content: space-between;">
            <span>Desconto:</span>
            <span>- R$ <?php echo number_format($venda['desconto'], 2, ',', '.'); ?></span>
        </p>
        <p style="margin: 5px 0; font-size: 15px; font-weight: bold; display: flex; justify-content: space-between;">
            <span>TOTAL PAGO:</span>
            <span>R$ <?php echo number_format($venda['valor_final'], 2, ',', '.'); ?></span>
        </p>
        <p style="margin: 3px 0;"><strong>Pagamento:</strong> <?php echo strtoupper($venda['forma_pagamento']); ?></p>

        <div class="divider"></div>
        <div class="center">
            <small>Prazo para troca: até 30 dias com este cupom.<br>Peças de Moda Íntima não possuem troca.</small>
        </div>
    </div>

    <div class="actions">
        <button onclick="window.print()" class="btn">🖨️ Imprimir Cupom</button>
        <a href="pdv.php" class="btn" style="background: #c59d5f; color: #111;">Nova Venda</a>
    </div>
</body>
</html>
