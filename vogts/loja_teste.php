<?php
session_start();
require 'config/conexao.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'gerente') {
    header("Location: dashboard.php");
    exit;
}

if (!isset($_SESSION['sacola_teste'])) {
    $_SESSION['sacola_teste'] = [];
}

$mensagem = '';

if (isset($_POST['add_sacola'])) {
    $pid = (int)$_POST['produto_id'];
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ? AND estoque > 0");
    $stmt->execute([$pid]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($prod) {
        if (isset($_SESSION['sacola_teste'][$pid])) {
            if ($_SESSION['sacola_teste'][$pid]['qtd'] < $prod['estoque']) {
                $_SESSION['sacola_teste'][$pid]['qtd']++;
            }
        } else {
            $_SESSION['sacola_teste'][$pid] = [
                'id'      => $prod['id'],
                'nome'    => $prod['nome'],
                'tamanho' => $prod['tamanho'],
                'cor'     => $prod['cor'],
                'preco'   => $prod['preco'],
                'qtd'     => 1
            ];
        }
        $mensagem = "<div class='alert alert-success'>[TESTE] Peça adicionada à sacola de simulação!</div>";
    }
}

if (isset($_GET['limpar_sacola'])) {
    $_SESSION['sacola_teste'] = [];
    header("Location: loja_teste.php");
    exit;
}

if (isset($_POST['finalizar_teste']) && count($_SESSION['sacola_teste']) > 0) {
    $pagamento = strtoupper($_POST['forma_pagamento']);
    $total = 0;
    foreach ($_SESSION['sacola_teste'] as $item) {
        $total += $item['preco'] * $item['qtd'];
    }
    $pedidoSimulado = rand(1000, 9999);
    $_SESSION['sacola_teste'] = [];
    $mensagem = "<div class='alert alert-success'>🧪 <strong>Simulação Concluída!</strong> Pedido Teste <strong>#$pedidoSimulado</strong> (R$ " . number_format($total, 2, ',', '.') . " via $pagamento). <strong>Nenhum estoque real foi alterado.</strong></div>";
}

$cat = $_GET['cat'] ?? '';
if ($cat) {
    $stmtProd = $pdo->prepare("SELECT * FROM produtos WHERE estoque > 0 AND categoria = ? ORDER BY id DESC");
    $stmtProd->execute([$cat]);
} else {
    $stmtProd = $pdo->query("SELECT * FROM produtos WHERE estoque > 0 ORDER BY id DESC");
}
$produtos = $stmtProd->fetchAll(PDO::FETCH_ASSOC);

$totalSacola = 0;
$qtdItensSacola = 0;
foreach ($_SESSION['sacola_teste'] as $it) {
    $totalSacola += $it['preco'] * $it['qtd'];
    $qtdItensSacola += $it['qtd'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOGTS - Loja Teste</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .faixa-teste { background: #d9534f; color: #fff; text-align: center; padding: 8px; font-weight: bold; font-size: 0.9rem; }
        .banner-loja { background: linear-gradient(135deg, #111 0%, #2c2c2c 100%); color: #fff; padding: 25px 20px; text-align: center; border-radius: 8px; margin-bottom: 20px; }
        .banner-loja h1 { letter-spacing: 4px; color: var(--accent); font-size: 1.8rem; }
        .categorias-bar { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 10px; margin-bottom: 20px; }
        .cat-pill { padding: 8px 16px; background: #fff; border: 1px solid #ddd; border-radius: 20px; text-decoration: none; color: #111; font-size: 0.9rem; font-weight: 600; white-space: nowrap; }
        .cat-pill.active, .cat-pill:hover { background: #111; color: var(--accent); border-color: #111; }
        .grid-produtos { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 20px; }
        .produto-card { background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.06); display: flex; flex-direction: column; justify-content: space-between; }
        .produto-img-placeholder { height: 190px; background: #eaeaea; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #666; font-weight: bold; position: relative; }
        .produto-info { padding: 15px; }
        .produto-preco { font-size: 1.2rem; font-weight: bold; color: #111; margin: 8px 0; }
        .tag-info { font-size: 0.75rem; background: #f0f0f0; padding: 3px 8px; border-radius: 4px; color: #555; }
    </style>
</head>
<body>
    <div class="faixa-teste">⚠️ AMBIENTE DE TESTE DO GERENTE — Nenhuma compra altera o estoque real.</div>
    <nav class="navbar">
        <a href="dashboard.php" class="brand">VOGTS</a>
        <div class="nav-links">
            <a href="dashboard.php">Início</a>
            <a href="pdv.php">Nova Venda (PDV)</a>
            <a href="produtos.php">Produtos / Estoque</a>
            <a href="clientes.php">Clientes</a>
            <a href="trocas.php">Trocas</a>
            <a href="relatorios.php">Relatórios</a>
            <a href="usuarios.php">Usuários</a>
            <a href="backup.php">Backup</a>
            <a href="loja_teste.php" class="active">Loja Teste</a>
            <a href="logout.php" style="background: #d9534f;">Sair</a>
        </div>
    </nav>

    <div class="container">
        <?php echo $mensagem; ?>
        <div class="banner-loja">
            <h1>VITRINE DE TESTE (SANDBOX)</h1>
            <p style="margin-top: 6px; color: #ddd;">Simule compras e confira como os produtos aparecem para os clientes.</p>
        </div>

        <div class="categorias-bar">
            <a href="loja_teste.php" class="cat-pill <?php echo $cat === '' ? 'active' : ''; ?>">Todas as Peças</a>
            <?php foreach (['Camisetas', 'Calças', 'Vestidos', 'Casacos', 'Moda Íntima', 'Acessórios'] as $c): ?>
                <a href="loja_teste.php?cat=<?php echo urlencode($c); ?>" class="cat-pill <?php echo $cat === $c ? 'active' : ''; ?>"><?php echo $c; ?></a>
            <?php endforeach; ?>
        </div>

        <div style="display: grid; grid-template-columns: 3fr 1fr; gap: 25px; align-items: start;">
            <div class="grid-produtos">
                <?php if (count($produtos) > 0): ?>
                    <?php foreach ($produtos as $p): ?>
                        <div class="produto-card">
                            <div class="produto-img-placeholder">
                                <span style="position: absolute; top: 10px; left: 10px; background: #111; color: var(--accent, #fff); font-size: 0.7rem; padding: 3px 8px; border-radius: 4px;"><?php echo htmlspecialchars($p['categoria']); ?></span>
                                <span style="position: absolute; top: 10px; right: 10px; background: #fff; color: #111; font-size: 0.75rem; padding: 3px 8px; border-radius: 4px;">Tam: <?php echo htmlspecialchars($p['tamanho']); ?></span>
                                <span style="font-size: 2.5rem; margin-bottom: 5px;">👕</span>
                                <span>VOGTS</span>
                            </div>
                            <div class="produto-info">
                                <h3 style="font-size: 1rem; margin: 0 0 6px 0; color: #111;"><?php echo htmlspecialchars($p['nome']); ?></h3>
                                <div style="display: flex; gap: 5px; flex-wrap: wrap; margin-bottom: 8px;">
                                    <span class="tag-info">Cor: <?php echo htmlspecialchars($p['cor']); ?></span>
                                    <span class="tag-info">Estoque Real: <?php echo $p['estoque']; ?></span>
                                </div>
                                <div class="produto-preco">R$ <?php echo number_format($p['preco'], 2, ',', '.'); ?></div>
                                <form method="POST">
                                    <input type="hidden" name="produto_id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" name="add_sacola" class="btn btn-primary" style="width: 100%; background: #111; color: var(--accent, #fff); border: none; padding: 10px; border-radius: 5px; font-weight: bold; cursor: pointer;">+ Simular na Sacola</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                        <h3>Nenhuma peça disponível no estoque</h3>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card" style="position: sticky; top: 20px;">
                <h3 style="margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">🧪 Sacola Teste (<?php echo $qtdItensSacola; ?>)</h3>
                <?php if ($qtdItensSacola > 0): ?>
                    <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 15px;">
                        <?php foreach ($_SESSION['sacola_teste'] as $item): ?>
                            <div style="font-size: 0.85rem; border-bottom: 1px dashed #eee; padding-bottom: 8px;">
                                <strong><?php echo htmlspecialchars($item['nome']); ?></strong><br>
                                <span style="color: #666;">Tam: <?php echo $item['tamanho']; ?> | Qtd: <?php echo $item['qtd']; ?></span>
                                <div style="font-weight: bold; margin-top: 2px;">R$ <?php echo number_format($item['preco'] * $item['qtd'], 2, ',', '.'); ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div style="font-size: 1.1rem; font-weight: bold; margin-bottom: 15px;">                    <div style="font-size: 1.1rem; font-weight: bold; margin-bottom: 15px;">
                        Total Simulado: R$ <?php echo number_format($totalSacola, 2, ',', '.'); ?>
                    </div>

                    <form method="POST">
                        <label style="font-size: 0.85rem; display: block; margin-bottom: 5px;">Forma de Pagamento</label>
                        <select name="forma_pagamento" required style="width: 100%; padding: 8px; margin-bottom: 12px; border: 1px solid #ccc; border-radius: 5px;">
                            <option value="pix">PIX</option>
                            <option value="credito">Cartão de Crédito</option>
                            <option value="debito">Cartão de Débito</option>
                        </select>
                        <button type="submit" name="finalizar_teste" class="btn btn-primary" style="width: 100%; margin-bottom: 8px;">
                            Simular Finalização
                        </button>
                    </form>

                    <a href="loja_teste.php?limpar_sacola=1" style="display: block; text-align: center; font-size: 0.8rem; color: #d9534f; text-decoration: none;">
                        Limpar Sacola Teste
                    </a>
                <?php else: ?>
                    <p style="font-size: 0.9rem; color: #777;">Adicione peças ao lado para testar o fluxo de compra sem afetar o banco de dados.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>

