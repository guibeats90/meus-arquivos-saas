<?php
require_once 'config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Se houver um avatar_id específico na URL, associa ele primeiro
if (isset($_GET['avatar_id'])) {
    $avatarId = $_GET['avatar_id'];
    
    // Verifica se o avatar existe
    $stmt = $pdo->prepare("SELECT id FROM avatars WHERE id = ?");
    $stmt->execute([$avatarId]);
    $avatar = $stmt->fetch();
    
    if ($avatar) {
        // Associa o avatar ao usuário, mesmo que ele já tenha um created_by
        $stmt = $pdo->prepare("UPDATE avatars SET created_by = ? WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $avatarId]);
    }
}

// Busca outros avatares sem dono que foram criados recentemente (últimas 24 horas)
$stmt = $pdo->prepare("
    SELECT id FROM avatars 
    WHERE created_by IS NULL 
    AND is_custom = 1 
    AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY created_at DESC
");

$stmt->execute();
$avatars = $stmt->fetchAll();

// Associa os avatares ao usuário atual
if (!empty($avatars)) {
    $stmt = $pdo->prepare("UPDATE avatars SET created_by = ? WHERE id = ?");
    foreach ($avatars as $avatar) {
        $stmt->execute([$_SESSION['user_id'], $avatar['id']]);
    }
}

// Redireciona para a página de meus avatares
header('Location: meus_avatars.php');
exit; 