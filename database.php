<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'u989636511_whispalove');
define('DB_PASS', '2025__GUIgui@@');
define('DB_NAME', 'u989636511_whispalove');

// Inclui o arquivo de configuração do RisePay
require_once __DIR__ . '/risepay.php';

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        )
    );
} catch(PDOException $e) {
    error_log("Erro na conexão com o banco de dados: " . $e->getMessage());
    die("Desculpe, ocorreu um erro ao conectar com o banco de dados. Por favor, tente novamente mais tarde.");
}
?> 