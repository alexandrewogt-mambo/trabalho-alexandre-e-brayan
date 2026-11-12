<?php
session_start();
require 'config/conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

if (!isset($_SESSION['carrinho'])) {
    $_SESSION['carrinho'] = [];
}

$erro = '';

if (isset($_POST['adicionar_item'])) {
    $codigo = trim($_POST['codigo']);
    $qtd = (int)$_POST['quantidade'];

    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE codigo = ?");
    $stmt->execute([$codigo]);
    $produto = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$produto) {
        $erro = "Produto com código '$codigo' não encontrado.";
    } elseif ($produto['estoque'] < $qtd) {
        $erro = "Estoque insuficiente. Disponível: " . $produto['estoque'] . " un.";
    } else {
        $id = $produto['id'];
        if (isset($_SESSION['carrinho'][$id])) {
            $_SESSION['carrinho'][$id]['qtd'] += $qtd;
        } else {
            $_SESSION['carrinho'][$id] = [
                'id' => $produto['id'],
                'codigo' => $produto['codigo'],
                'nome' => $produto['nome'],
                'preco' => $produto['preco'],
                'qtd' => $qtd
            ];
        }
    }
}

if (isset($_POST['cancelar_carrinho'])) {
    $_SESSION['carrinho'] = [];
    header("Location: pdv.php");
    exit;
}

if (isset($_POST['finalizar_venda']) && count($_SESSION['carrinho']) > 0) {
    $cliente_id = !empty($_POST['cliente_id']) ? $_POST['cliente_id'] : null;
    $forma_pagamento = $_POST['forma_pagamento'];
    $desconto_percentual = (float)$_POST['desconto_percentual'];
    $deseja_cpf = $_POST['deseja_cpf'];
    $cpf_nota = ($deseja_cpf === 'sim') ? trim($_POST['cpf_nota']) : null;

    $valor_bruto = 0;
    foreach ($_SESSION['carrinho'] as $item) {
        $valor_bruto += $item['preco'] * $item['qtd'];
    }

    $autorizado = true;

    if ($desconto_percentual > 10) {
        $gerente_login = trim($_POST['gerente_login'] ?? '');
        $gerente_senha = $_POST['gerente_senha'] ?? '';

        $stmtGer = $pdo->prepare("SELECT * FROM usuarios WHERE login = ? AND perfil = 'gerente'");
        $stmtGer->execute([$gerente_login]);
        $gerente = $stmtGer->fetch(PDO::FETCH_ASSOC);

        if (!$gerente || !password_verify($gerente_senha, $gerente['senha'])) {
            $autorizado = false;
            $erro = "Desconto acima de 10% negado! Credenciais do gerente inválidas.";
        }
    }

    if ($deseja_cpf === 'sim' && empty($cpf_nota)) {
        $autorizado = false;
        $erro = "Você selecionou incluir CPF na nota. Informe o número do CPF.";
    }

    if ($autorizado) {
        $valor_desconto = $valor_bruto * ($desconto_percentual / 100);
        $valor_final = $valor_bruto - $valor_desconto;

        try {
            $pdo->beginTransaction();

            $stmtVenda = $pdo->prepare("INSERT INTO vendas (cliente_id, usuario_id, cpf_nota, valor_bruto, desconto, valor_final, forma_pagamento) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmtVenda->execute([$cliente_id, $_SESSION['usuario_id'], $cpf_nota, $valor_bruto, $valor_desconto, $valor_final, $forma_pagamento]);
            $venda_id = $pdo->lastInsertId();

            $stmtItem = $pdo->prepare("INSERT INTO itens_venda (venda_id, produto_id, quantidade, preco_unitario) VALUES (?, ?, ?, ?)");
            $stmtEstoque = $pdo->prepare("UPDATE produtos SET estoque = estoque - ? WHERE id = ?");

            foreach ($_SESSION['carrinho'] as $item) {
                $stmtItem->execute([$venda_id, $item['id'], $item['qtd'], $item['preco']]);
                $stmtEstoque->execute([$item['qtd'], $item['id']]);
            }

            $pdo->commit();
            $_SESSION['carrinho'] = [];
            header("Location: cupom.php?id=" . $venda_id);
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $erro = "Erro ao registrar venda: " . $e->getMessage();
        }
    }
}

$clientes = $pdo->query("SELECT id, nome, cpf FROM clientes ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$subtotal = 0;
foreach ($_SESSION['carrinho'] as $item) {
    $subtotal += $item['preco'] * $item['qtd'];
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOGTS - PDV (Frente de Caixa)</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <nav class="navbar">
        <a href="dashboard.php" class="brand">VOGTS</a>
        <div class="nav-links">
            <a href="dashboard.php">Início</a>
            <a href="pdv.php" class="active">Nova Venda (PDV)</a>
            <a href="produtos.php">Produtos / Estoque</a>
            <a href="clientes.php">Clientes</a>
            <a href="logout.php" style="background: #d9534f;">Sair</a>
        </div>
    </nav>

    <div class="container">
        <?php if ($erro): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($erro); ?></div>
        <?php endif; ?>

        <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
            <div class="card">
                <h2>1. Adicionar Peças à Venda</h2>
                <form method="POST" style="display: flex; gap: 10px; margin-bottom: 20px;">
                    <input type="text" name="codigo" placeholder="Código da peça (Ex: VGT-001)" required autofocus style="flex: 2; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                    <input type="number" name="quantidade" value="1" min="1" required style="width: 80px; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                    <button type="submit" name="adicionar_item" class="btn btn-primary">Adicionar</button>
                </form>

                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Cód.</th>
                                <th>Peça</th>
                                <th>Qtd</th>
                                <th>Unitário</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($_SESSION['carrinho']) > 0): ?>
                                <?php foreach ($_SESSION['carrinho'] as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['codigo']); ?></td>
                                        <td><?php echo htmlspecialchars($item['nome']); ?></td>
                                        <td><?php echo $item['qtd']; ?></td>
                                        <td>R$ <?php echo number_format($item['preco'], 2, ',', '.'); ?></td>
                                        <td>R$ <?php echo number_format($item['preco'] * $item['qtd'], 2, ',', '.'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5" style="text-align: center; color: #777;">Nenhum item adicionado ainda.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (count($_SESSION['carrinho']) > 0): ?>
                    <form method="POST" style="margin-top: 20px;">
                        <button type="submit" name="cancelar_carrinho" class="btn btn-danger">Cancelar Venda Atual</button>
                    </form>
                <?php endif; ?>
            </div>

            <div class="card">
                <h2>2. Fechamento</h2>
                <div style="font-size: 1.5rem; font-weight: bold; margin-bottom: 15px; color: var(--primary);">
                    Subtotal: R$ <span id="subtotalDisplay"><?php echo number_format($subtotal, 2, ',', '.'); ?></span>
                </div>

                <form method="POST">
                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>Cliente (Opcional)</label>
                        <select name="cliente_id">
                            <option value="">Consumidor Final</option>
                            <?php foreach ($clientes as $c): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['nome']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>Forma de Pagamento (RF05)</label>
                        <select name="forma_pagamento" required>
                            <option value="pix">PIX</option>
                            <option value="credito">Cartão de Crédito</option>
                            <option value="debito">Cartão de Débito</option>
                            <option value="dinheiro">Dinheiro</option>
                        </select>
                    </div>

                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>Desconto (%)</label>
                        <input type="number" id="descontoInput" name="desconto_percentual" value="0" min="0" max="100" step="0.5" oninput="verificarDesconto()">
                    </div>

                    <div id="boxGerente" style="display: none; background: #fdecea; padding: 12px; border-radius: 5px; margin-bottom: 12px; border: 1px solid #d9534f;">
                        <p style="color: #a94442; font-size: 0.85rem; font-weight: bold; margin-bottom: 8px;">⚠️ Desconto acima de 10% exige senha do Gerente (RN03):</p>
                        <input type="text" name="gerente_login" placeholder="Login do Gerente" style="width: 100%; padding: 8px; margin-bottom: 6px; border: 1px solid #ccc;">
                        <input type="password" name="gerente_senha" placeholder="Senha do Gerente" style="width: 100%; padding: 8px; border: 1px solid #ccc;">
                    </div>

                    <div class="form-group" style="margin-bottom: 12px;">
                        <label>Deseja CPF na nota? (Obrigatório - RN04)</label>
                        <select name="deseja_cpf" id="desejaCpf" required onchange="toggleCpf()">
                            <option value="">-- Selecione --</option>
                            <option value="nao">Não informar CPF</option>
                            <option value="sim">Sim, informar CPF</option>
                        </select>
                        <input type="text" name="cpf_nota" id="campoCpf" placeholder="000.000.000-00" style="display: none; margin-top: 8px;">
                    </div>

                    <div style="font-size: 1.3rem; font-weight: bold; margin: 15px 0; color: var(--success);">
                        Total Final: R$ <span id="totalFinalDisplay"><?php echo number_format($subtotal, 2, ',', '.'); ?></span>
                    </div>

                    <button type="submit" name="finalizar_venda" class="btn btn-primary" style="width: 100%; font-size: 1.1rem;" <?php echo count($_SESSION['carrinho']) == 0 ? 'disabled' : ''; ?>>
                        Concluir Venda
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const subtotal = <?php echo $subtotal; ?>;

        function verificarDesconto() {
            const desc = parseFloat(document.getElementById('descontoInput').value) || 0;
            const boxGerente = document.getElementById('boxGerente');
            
            if (desc > 10) {
                boxGerente.style.display = 'block';
            } else {
                boxGerente.style.display = 'none';
            }

            const total = subtotal - (subtotal * (desc / 100));
            document.getElementById('totalFinalDisplay').innerText = total.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function toggleCpf() {
            const escolha = document.getElementById('desejaCpf').value;
            const campoCpf = document.getElementById('campoCpf');
            campoCpf.style.display = (escolha === 'sim') ? 'block' : 'none';
            campoCpf.required = (escolha === 'sim');
        }
    </script>
</body>
</html>
