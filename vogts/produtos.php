<?php
session_start();
require 'config/conexao.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: index.php");
    exit;
}

$mensagem = '';
$produtoEditar = null;

if (isset($_GET['editar'])) {
    $stmtEd = $pdo->prepare("SELECT * FROM produtos WHERE id = ?");
    $stmtEd->execute([(int)$_GET['editar']]);
    $produtoEditar = $stmtEd->fetch(PDO::FETCH_ASSOC);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['atualizar'])) {
        try {
            $stmt = $pdo->prepare("UPDATE produtos SET codigo = ?, nome = ?, categoria = ?, tamanho = ?, cor = ?, preco = ?, estoque = ? WHERE id = ?");
            $stmt->execute([trim($_POST['codigo']), trim($_POST['nome']), $_POST['categoria'], $_POST['tamanho'], trim($_POST['cor']), str_replace(',', '.', $_POST['preco']), (int)$_POST['estoque'], (int)$_POST['produto_id']]);
            $mensagem = "<div class='alert alert-success'>Produto atualizado com sucesso!</div>";
        } catch (PDOException $e) {
            $mensagem = "<div class='alert alert-danger'>Erro ao atualizar: Código já pertence a outra peça.</div>";
        }
    } elseif (isset($_POST['excluir_produto'])) {
        try {
            $stmtDel = $pdo->prepare("DELETE FROM produtos WHERE id = ?");
            $stmtDel->execute([(int)$_POST['produto_id']]);
            $mensagem = "<div class='alert alert-success'>Produto excluído com sucesso!</div>";
        } catch (PDOException $e) {
            $mensagem = "<div class='alert alert-danger'>Não é possível excluir: peça vinculada a vendas ou trocas.</div>";
        }
    } elseif (isset($_POST['cadastrar'])) {
        try {
            $stmt = $pdo->prepare("INSERT INTO produtos (codigo, nome, categoria, tamanho, cor, preco, estoque) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([trim($_POST['codigo']), trim($_POST['nome']), $_POST['categoria'], $_POST['tamanho'], trim($_POST['cor']), str_replace(',', '.', $_POST['preco']), (int)$_POST['estoque']]);
            $mensagem = "<div class='alert alert-success'>Produto cadastrado com sucesso!</div>";
        } catch (PDOException $e) {
            $mensagem = "<div class='alert alert-danger'>Erro: Código já cadastrado ou dados inválidos.</div>";
        }
    }
}

$busca = $_GET['busca'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM produtos WHERE nome LIKE ? OR codigo LIKE ? OR categoria LIKE ? OR tamanho LIKE ? ORDER BY nome ASC");
$termo = "%$busca%";
$stmt->execute([$termo, $termo, $termo, $termo]);
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$categorias = ['Camisetas', 'Calças', 'Vestidos', 'Casacos', 'Moda Íntima', 'Acessórios'];
$tamanhos   = ['PP', 'P', 'M', 'G', 'GG', 'Único'];
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
        <?php echo $mensagem; ?>

        <div class="card" style="<?php echo $produtoEditar ? 'border-left: 5px solid var(--accent, #c59d5f);' : ''; ?>">
            <h2><?php echo $produtoEditar ? 'Editar Peça: ' . htmlspecialchars($produtoEditar['nome']) : 'Cadastrar Nova Peça'; ?></h2>
            <form method="POST" action="produtos.php">
                <?php if ($produtoEditar): ?>
                    <input type="hidden" name="produto_id" value="<?php echo $produtoEditar['id']; ?>">
                <?php endif; ?>

                <div class="form-grid">
                    <div class="form-group">
                        <label>Código / SKU</label>
                        <input type="text" name="codigo" required value="<?php echo $produtoEditar ? htmlspecialchars($produtoEditar['codigo']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Nome da Peça</label>
                        <input type="text" name="nome" required value="<?php echo $produtoEditar ? htmlspecialchars($produtoEditar['nome']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Categoria (RN05)</label>
                        <select name="categoria" required>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo ($produtoEditar && $produtoEditar['categoria'] === $cat) ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tamanho</label>
                        <select name="tamanho" required>
                            <?php foreach ($tamanhos as $tam): ?>
                                <option value="<?php echo $tam; ?>" <?php echo ($produtoEditar && $produtoEditar['tamanho'] === $tam) ? 'selected' : ''; ?>><?php echo $tam; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Cor</label>
                        <input type="text" name="cor" required value="<?php echo $produtoEditar ? htmlspecialchars($produtoEditar['cor']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Preço (R$)</label>
                        <input type="number" step="0.01" name="preco" required value="<?php echo $produtoEditar ? $produtoEditar['preco'] : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label>Qtd. Estoque</label>
                        <input type="number" name="estoque" required min="0" value="<?php echo $produtoEditar ? $produtoEditar['estoque'] : '1'; ?>">
                    </div>
                </div>

                <div style="margin-top: 15px; display: flex; gap: 10px;">
                    <?php if ($produtoEditar): ?>
                        <button type="submit" name="atualizar" class="btn btn-primary" style="background: var(--accent, #c59d5f); color: #111; font-weight: bold;">Atualizar Produto</button>
                        <a href="produtos.php" class="btn btn-danger">Cancelar</a>
                    <?php else: ?>
                        <button type="submit" name="cadastrar" class="btn btn-primary">Salvar Produto</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="card">
            <h2>Consulta de Estoque</h2>
            <form method="GET" style="display: flex; gap: 10px; margin-bottom: 15px;">
                <input type="text" name="busca" value="<?php echo htmlspecialchars($busca); ?>" placeholder="Buscar por nome, código, categoria ou tamanho..." style="flex: 1; padding: 10px; border: 1px solid #ccc; border-radius: 5px;">
                <button type="submit" class="btn btn-primary">Buscar</button>
                <?php if ($busca): ?><a href="produtos.php" class="btn btn-danger">Limpar</a><?php endif; ?>
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
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($produtos as $p): ?>
                            <tr class="<?php echo $p['estoque'] < 2 ? 'estoque-baixo' : ''; ?>">
                                <td><?php echo htmlspecialchars($p['codigo']); ?></td>
                                <td><?php echo htmlspecialchars($p['nome']); ?></td>
                                <td><?php echo htmlspecialchars($p['categoria']); ?></td>
                                <td><?php echo htmlspecialchars($p['tamanho']); ?></td>
                                <td><?php echo htmlspecialchars($p['cor']); ?></td>
                                <td>R$ <?php echo number_format($p['preco'], 2, ',', '.'); ?></td>
                                <td><?php echo $p['estoque']; ?><?php if ($p['estoque'] < 2): ?> <span class="badge-alerta">BAIXO</span><?php endif; ?></td>
                                <td style="display: flex; gap: 5px;">
                                    <a href="produtos.php?editar=<?php echo $p['id']; ?>" class="btn btn-primary" style="padding: 5px 10px; font-size: 0.8rem; background: var(--accent, #c59d5f); color: #111;">Editar</a>
                                    <form method="POST" onsubmit="return confirm('Excluir esta peça?');" style="margin: 0;">
                                        <input type="hidden" name="produto_id" value="<?php echo $p['id']; ?>">
                                        <button type="submit" name="excluir_produto" class="btn btn-danger" style="padding: 5px 10px; font-size: 0.8rem;">Excluir</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
