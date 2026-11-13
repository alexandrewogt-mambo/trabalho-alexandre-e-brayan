<?php
require 'config/conexao.php';

// Gera a criptografia exata da senha 123456 no seu PHP
$senhaCriptografada = password_hash('123456', PASSWORD_DEFAULT);

// Atualiza a senha se os usuários já existirem
$stmt = $pdo->prepare("UPDATE usuarios SET senha = ? WHERE login IN ('gerente', 'vendedor')");
$stmt->execute([$senhaCriptografada]);

// Se a tabela estiver vazia, cria os dois usuários agora
if ($stmt->rowCount() === 0) {
    $insert = $pdo->prepare("INSERT INTO usuarios (nome, login, senha, perfil) VALUES (?, ?, ?, ?)");
    $insert->execute(['Administrador Vogts', 'gerente', $senhaCriptografada, 'gerente']);
    $insert->execute(['Vendedor Loja', 'vendedor', $senhaCriptografada, 'vendedor']);
}

echo "<h2>Sucesso! Senha '123456' configurada para 'gerente' e 'vendedor'.</h2>";
echo "<a href='index.php'>Clique aqui para ir para o Login</a>";
?>
