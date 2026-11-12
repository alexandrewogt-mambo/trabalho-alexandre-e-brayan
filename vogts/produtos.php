<?php
session_start();
require 'config/conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$mensagem = '';

// Cadastrar Novo Produto (RF01)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cadastrar'])) {
    $codigo    = trim($_POST['codigo']);
    $nome      = trim($_POST['nome']);
    $categoria = $_POST['categoria'];
    $tamanho   = $_POST['tamanho'];
    $cor       = trim($_POST['cor']);
    $preco     = str_replace(',', '.', $_POST['preco']);
    $estoque   = (int)$_POST['estoque'];

    try {
        $stmt = $pdo->prepare("INSERT INTO produtos (codigo, nome, categoria, tamanho, cor, preco, estoque) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$codigo, $nome, $categoria, $tamanho, $cor, $preco, $estoque]);
        $mensagem = "<div class='alert alert-success'>Produto cadastrado com sucesso!</div>";
    } catch (PDOException $e) {
        $mensagem = "<div class='alert alert-danger'>Erro: Código já cadastrado ou dados inválidos.</div>";
    }
}

// Consulta de Estoque (RF09)
$busca = $_GET['busca'] ?? '';
$sql = "SELECT * FROM produtos WHERE nome LIKE ? OR codigo LIKE ? OR categoria LIKE ? OR tamanho LIKE ? ORDER BY nome ASC";
$stmt = $pdo->prepare($sql);
$termo = "%$busca%";
$stmt->execute([$termo, $termo, $termo, $termo]);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VOGTS - Produtos e Estoque</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <nav class="navbar">
        <a href="dashboard.php" class="brand">VOGTS</a>
        <div class="nav-links">
            <a href="dashboard.php">Início</a>
            <a href="pdv.php">Nova Venda (PDV)</a>
            <a href="produtos.php" class="active">Produtos / Estoque</a>
            <a href="clientes.php">Clientes</a>
            <a href="logout.php" style="background: #d9534f;">Sair</a>
        </div>
    </nav>

    <div class="container">
        <?= $mensagem ?>

        <!-- Formulário de Cadastro (RF01) -->
        <div class="card">
            <h2>Cadastrar Nova Peça</h2>
            <form method="POST">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Código / SKU</label>
                        <input type="text" name="codigo" required placeholder="Ex: VGT-001">
                    </div>
                    <div class="form-group">
                        <label>Nome da Peça</label>
                        <input type="text" name="nome" required placeholder="Ex: Camiseta Básica">
                    </div>
                    <div class="form-group">
                        <label>Categoria (RN05)</label>
                        <select name="categoria" required>
                            <option value="Camisetas">Camisetas</option>
                            <option value="Calças">Calças</option>
                            <option value="Vestidos">Vestidos</option>
                            <option value="Casacos">Casacos</option>
                            <option value="Moda Íntima">Moda Íntima</option>
                            <option value="Acessórios">Acessórios</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tamanho</label>
                        <select name="tamanho" required>
                            <option value="PP">PP</option>
                            <option value="P">P</option>
                            <option value="M">M</option>
                            <option value="G">G</option>
                            <option value="GG">GG</option>
                            <option value="Único">Único</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Cor</label>
                        <input type="text" name="cor" required placeholder="Ex: Preto">
                    </div>
                    <div class="form-group">
                        <label>Preço (R$)</label>
                        <input type="number" step="0.01" name="preco" required placeholder="89.90">
                    </div>
                    <div class="form-group">
                        <label>Qtd. Estoque</label>
                        <input type="number" name="estoque" required value="1" min="0">
                    </div>
                </div>
                <button type="submit" name="cadastrar" class="btn btn-primary" style="margin-top: 15px;">Salvar Produto</button>
            </form>
        </div>

        <!-- Consulta de Estoque Responsiva (RF09, RNF03) -->
        <div class="card">
            <h2>Consulta de Estoque</h2>
            <form method="GET" style="display: flex; gap: 10px; margin-bottom: 15px;">
                <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar por nome, código, categoria ou tamanho..." style="flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                <button type="submit" class="btn btn-primary">Buscar</button>
                <?php if($busca): ?><a href="produtos.php" class="btn btn-danger">Limpar</a><?php endif; ?>
            </form>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Produto</th>
                            <th>Categoria</th>
                            <th>Tam.</th>
                            <th>Cor</th>
                            <th>Preço</th>
                            <th>Estoque</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos as $p): ?>
                            <tr class="<?= $p['estoque'] < 2 ? 'estoque-baixo' : '' ?>">
                                <td><?= htmlspecialchars($p['codigo']) ?></td>
                                <td><?= htmlspecialchars($p['nome']) ?></td>
                                <td><?= htmlspecialchars($p['categoria']) ?></td>
                                <td><?= htmlspecialchars($p['tamanho']) ?></td>
                                <td><?= htmlspecialchars($p['cor']) ?></td>
                                <td>R$ <?= number_format($p['preco'], 2, ',', '.') ?></td>
                                <td>
                                    <?= $p['estoque'] ?>
                                    <?php if ($p['estoque'] < 2): ?>
                                        <span class="badge-alerta">BAIXO</span>
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
