<?php
session_start();
require 'config/conexao.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_perfil'] !== 'gerente') {
    header("Location: dashboard.php");
    exit;
}

// Gera o dump SQL das tabelas principais de vendas e estoque
$tabelas = ['usuarios', 'produtos', 'clientes', 'vendas', 'itens_venda', 'devolucoes_creditos'];
$sqlBackup = "-- BACKUP AUTOMÁTICO VOGTS - DATA: " . date('d/m/Y H:i:s') . "\n\n";

foreach ($tabelas as $tabela) {
    $rows = $pdo->query("SELECT * FROM $tabela")->fetchAll(PDO::FETCH_ASSOC);
    $sqlBackup .= "-- Dados da tabela: $tabela\n";
    foreach ($rows as $row) {
        $colunas = implode(", ", array_keys($row));
        $valores = implode(", ", array_map(function($val) use ($pdo) {
            return $val === null ? "NULL" : $pdo->quote($val);
        }, array_values($row)));
        $sqlBackup .= "INSERT INTO $tabela ($colunas) VALUES ($valores);\n";
    }
    $sqlBackup .= "\n";
}

// Salva uma cópia na pasta local 'backups/' (simulando envio para nuvem/pasta sincronizada)
if (!is_dir('backups')) {
    mkdir('backups', 0777, true);
}
$nomeArquivo = 'backups/backup_vogts_' . date('Y-m-d_H-i-s') . '.sql';
file_put_contents($nomeArquivo, $sqlBackup);

// Faz o download imediato para o computador do gerente
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . basename($nomeArquivo) . '"');
echo $sqlBackup;
exit;
?>
