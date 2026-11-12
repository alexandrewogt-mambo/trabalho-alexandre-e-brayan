<?php
$host = 'localhost';
$db   = 'vogts_db';
$user = 'root'; // Padrão do XAMPP
$pass = '';     // Padrão do XAMPP (vazio)

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erro na conexão com o banco de dados: " . $e->getMessage());
}
?>
    