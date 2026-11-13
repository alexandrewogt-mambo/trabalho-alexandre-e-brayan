<?php
session_start();
require 'config/conexao.php';

if (!isset($_SESSION['sacola'])) {
    $_SESSION['sacola'] = [];
}

$mensagem = '';

// Sair da conta de cliente
if (isset($_GET['sair_cliente'])) {
    unset($_SESSION['cliente_id'], $_SESSION['cliente_nome']);
    header("Location: loja.php");
    exit;
}

// Adicionar peça à sacola online
if (isset($_POST['add_sacola'])) {
    $pid = (int)$_POST['produto_id'];
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ? AND estoque > 0");
    $stmt->execute([$pid]);
    $prod = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($prod) {
        if (isset($_SESSION['sacola'][$pid])) {
            if ($_SESSION['sacola'][$pid]['qtd'] < $prod['estoque']) {
                $_SESSION['sacola'][$pid]['qtd']++;
            }
        } else {
            $_SESSION['sacola'][$pid] = [
                'id' => $prod['id'],
                'nome' => $prod['nome'],
                'tamanho' => $prod['tamanho'],
                'cor' => $prod['cor'],
                'preco' => $prod['preco'],
                'qtd' => 1
            ];
        }
        $mensagem = "<div class='alert alert-success'>Peça adicionada à sua sacola!</div>";
    }
}

// Limpar sacola
if (isset($_GET['limpar_sacola'])) {
    $_SESSION['sacola'] = [];
    header("Location: loja.php");
    exit;
}

// Finalizar Compra Online
if (isset($_POST['finalizar_online']) && count($_SESSION['sacola']) > 0) {
    if (!isset($_SESSION['cliente_id'])) {
        header("Location: cadastro_cliente.php");
        exit;
    }

    $pagamento = $_POST['forma_pagamento'];
    $total = 0;
    foreach ($_SESSION['sacola'] as $item) {
        $total += $item['preco'] * $item['qtd'];
    }

    try {
        $pdo->beginTransaction();

        // Pega o ID do primeiro funcionário/gerente para vincular a venda online no sistema
        $vendedorSistema = $pdo->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn();

        $stmtVenda = $pdo->prepare("INSERT INTO vendas (cliente_id, usuario_id, valor_bruto, desconto, valor_final, forma_pagamento) VALUES (?, ?, ?, 0, ?, ?)");
        $stmtVenda->execute([$_SESSION['cliente_id'], $vendedorSistema, $total, $total, $pagamento]);
        $venda_id = $pdo->lastInsertId();

        $stmtItem = $pdo->prepare("INSERT INTO itens_venda (venda_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
        $stmtEstoque = $pdo->prepare("UPDATE produtos SET estoque = estoque - ? WHERE id = ?");

        foreach ($_SESSION['sacola'] as $item) {
            $stmtItem->execute([$venda_id, $item['id'], $item['qtd'], $item['preco']]);
            $stmtEstoque->execute([$item['qtd'], $item['id']]);
        }

        $pdo->commit();
        $_SESSION['sacola'] = [];
        $mensagem = "<div class='alert alert-success'>🎉 Pedido <strong>#$venda_id</strong> realizado com sucesso! Obrigado por comprar na VOGTS Online.</div>";
    } catch (Exception $e) {
        $pdo->rollBack();
        $mensagem = "<div class='alert alert-danger'>Erro ao finalizar pedido: " . $e->getMessage() . "</div>";
    }
}

// Filtro de categoria
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
foreach ($_SESSION['sacola'] as $it) {
    $totalSacola += $it['preco'] * $it['qtd'];
    $qtdItensSacola += $it['qtd'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOGTS - Loja Online</title>
    <link rel="stylesheet" href="assets/style.css">
    <style>
        .banner-loja {
            background: linear-gradient(135deg, #111111 0%, #2c2c2c 100%);
            color: #fff;
            padding: 35px 20px;
            text-align: center;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .banner-loja h1 { letter-spacing: 5px; color: var(--accent); font-size: 2rem; }
        .categorias-bar {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .cat-pill {
            padding: 8px 16px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 20px;
            text-decoration: none;
            color: #111;
            font-size: 0.9rem;
            font-weight: 600;
            white-space: nowrap;
        }
        .cat-pill.active, .cat-pill:hover {
            background: #111;
            color: var(--accent);
            border-color: #111;
        }
        .grid-produtos {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }
        .produto-card {
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: transform 0.2s;
        }
        .produto-card:hover { transform: translateY(-4px); }
        .produto-img-placeholder {
            height: 200px;
            background: #eaeaea;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            color: #666;
            font-weight: bold;
            letter-spacing: 2px;
        }
        .produto-info { padding: 15px; }
        .produto-preco { font-size: 1.2rem; font-weight: bold; color: #111; margin: 8px 0; }
        .tag-info { font-size: 0.75rem; background: #f0f0f0; padding: 3px 8px; border-radius: 4px; color: #555; }
    </style>
</head>
<body>
    <nav class="navbar">
        <a href="loja.php" class="brand">VOGTS</a>
        <div class="nav-links">
            <?php if (isset($_SESSION['cliente_id'])): ?>
                <span style="color: var(--accent); align-self: center; font-size: 0.9rem;">Olá, <?php echo htmlspecialchars($_SESSION['cliente_nome']); ?></span>
                <a href="loja.php?sair_cliente=1" style="background: #333;">Sair</a>
            <?php else: ?>
                <a href="cadastro_cliente.php" style="background: var(--accent); color: #111; font-weight: bold;">Entrar / Registrar-se</a>
            <?php endif; ?>
            <a href="index.php" style="font-size: 0.8rem; opacity: 0.7;">Painel Loja</a>
        </div>
    </nav>

    <div class="container">
        <?php echo $mensagem; ?>

        <div class="banner-loja">
            <h1>NOVA COLEÇÃO VOGTS</h1>
            <p style="margin-top: 8px; color: #ddd;">Moda contemporânea com entrega rápida. Escolha suas peças abaixo.</p>
        </div>

        <!-- Filtro de Categorias -->
        <div class="categorias-bar">
            <a href="loja.php" class="cat-pill <?php echo $cat === '' ? 'active' : ''; ?>">Todas as Peças</a>
            <a href="loja.php?cat=Camisetas" class="cat-pill <?php echo $cat === 'Camisetas' ? 'active' : ''; ?>">Camisetas</a>
            <a href="loja.php?cat=Calças" class="cat-pill <?php echo $cat === 'Calças' ? 'active' : ''; ?>">Calças</a>
            <a href="loja.php?cat=Vestidos" class="cat-pill <?php echo $cat === 'Vestidos' ? 'active' : ''; ?>">Vestidos</a>
            <a href="loja.php?cat=Casacos" class="cat-pill <?php echo $cat === 'Casacos' ? 'active' : ''; ?>">Casacos</a>
            <a href="loja.php?cat=Moda Íntima" class="cat-pill <?php echo $cat === 'Moda Íntima' ? 'active' : ''; ?>">Moda Íntima</a>
            <a href="loja.php?cat=Acessórios" class="cat-pill <?php echo $cat === 'Acessórios' ? 'active' : ''; ?>">Acessórios</a>
        </div>

        <div style="display: grid; grid-template-columns: 3fr 1fr; gap: 25px; align-items: start;">
                        <!-- Vitrine de Roupas -->
            <div class="grid-produtos">
                <?php if (count($produtos) > 0): ?>
                    <?php foreach ($produtos as $p): ?>
                        <div class="produto-card">
                            <div class="produto-img-placeholder" style="position: relative;">
                                <span style="position: absolute; top: 10px; left: 10px; background: #111; color: var(--accent, #fff); font-size: 0.7rem; padding: 3px 8px; border-radius: 4px; letter-spacing: 0;">
                                    <?php echo htmlspecialchars($p['categoria']); ?>
                                </span>
                                <span style="position: absolute; top: 10px; right: 10px; background: #fff; color: #111; font-size: 0.75rem; padding: 3px 8px; border-radius: 4px; letter-spacing: 0;">
                                    Tam: <?php echo htmlspecialchars($p['tamanho']); ?>
                                </span>
                                <span style="font-size: 2.5rem; margin-bottom: 5px;">👕</span>
                                <span>VOGTS</span>
                            </div>

                            <div class="produto-info">
                                <h3 style="font-size: 1rem; margin: 0 0 6px 0; color: #111;">
                                    <?php echo htmlspecialchars($p['nome']); ?>
                                </h3>
                                
                                <div style="display: flex; gap: 5px; flex-wrap: wrap; margin-bottom: 8px;">
                                    <span class="tag-info">Cor: <?php echo htmlspecialchars($p['cor']); ?></span>
                                    <span class="tag-info">Restam: <?php echo $p['estoque']; ?></span>
                                </div>

                                <div class="produto-preco">
                                    R$ <?php echo number_format($p['preco'], 2, ',', '.'); ?>
                                </div>

                                <form method="POST">
                                    <input type="hidden" name="produto_id" value="<?php echo $p['id']; ?>">
                                    <button type="submit" name="add_sacola" class="btn btn-primary" style="width: 100%; background: #111; color: var(--accent, #fff); border: none; padding: 10px; border-radius: 5px; font-weight: bold; cursor: pointer;">
                                        + Adicionar à Sacola
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                        <h3>Nenhuma peça disponível no momento</h3>
                        <p style="color: #666; margin-top: 8px;">Cadastre produtos com estoque maior que zero no painel do gerente para vê-los aqui.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Resumo da Sacola Lateral -->
            <div class="card" style="position: sticky; top: 20px;">
                <h3 style="margin-bottom: 15px; border-bottom: 1px solid #eee; padding-bottom: 10px;">
                    🛍️ Sua Sacola (<?php echo $qtdItensSacola; ?>)
                </h3>

                <?php if ($qtdItensSacola > 0): ?>
                    <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 15px;">
                        <?php foreach ($_SESSION['sacola'] as $item): ?>
                            <div style="font-size: 0.85rem; border-bottom: 1px dashed #eee; padding-bottom: 8px;">
                                <strong><?php echo htmlspecialchars($item['nome']); ?></strong><br>
                                <span style="color: #666;">Tam: <?php echo $item['tamanho']; ?> | Qtd: <?php echo $item['qtd']; ?></span>
                                <div style="font-weight: bold; margin-top: 2px;">
                                    R$ <?php echo number_format($item['preco'] * $item['qtd'], 2, ',', '.'); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div style="font-size: 1.1rem; font-weight: bold; margin-bottom: 15px;">
                        Total: R$ <?php echo number_format($totalSacola, 2, ',', '.'); ?>
                    </div>

                    <form method="POST">
                        <label style="font-size: 0.85rem; display: block; margin-bottom: 5px;">Forma de Pagamento</label>
                        <select name="forma_pagamento" required style="width: 100%; padding: 8px; margin-bottom: 12px; border: 1px solid #ccc; border-radius: 5px;">
                            <option value="PIX">PIX</option>
                            <option value="Cartão de Crédito">Cartão de Crédito</option>
                            <option value="Cartão de Débito">Cartão de Débito</option>
                        </select>
                        <button type="submit" name="finalizar_online" class="btn btn-primary" style="width: 100%; margin-bottom: 8px;">
                            Finalizar Pedido
                        </button>
                    </form>
                    <a href="loja.php?limpar_sacola=1" style="display: block; text-align: center; font-size: 0.8rem; color: #d9534f; text-decoration: none;">
                        Esvaziar Sacola
                    </a>
                <?php else: ?>
                    <p style="font-size: 0.9rem; color: #777;">Sua sacola está vazia. Clique em "Adicionar à Sacola" nas peças ao lado.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
                    