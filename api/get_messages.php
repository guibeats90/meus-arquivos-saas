<?php
require_once __DIR__ . '/../config/database.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
    exit;
}

$avatarId = $_GET['avatar_id'] ?? null;
if (!$avatarId) {
    echo json_encode(['success' => false, 'error' => 'Avatar não especificado']);
    exit;
}

try {
    // Busca o ID da conversa
    $stmt = $pdo->prepare("
        SELECT id FROM conversations 
        WHERE user_id = ? AND avatar_id = ?
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id'], $avatarId]);
    $conversation = $stmt->fetch();
    
    if ($conversation) {
        // Busca as mensagens da conversa
        $stmt = $pdo->prepare("
            SELECT content, sender_type, DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') as formatted_date 
            FROM messages 
            WHERE conversation_id = ? 
            ORDER BY created_at ASC
        ");
        $stmt->execute([$conversation['id']]);
        $messages = $stmt->fetchAll();
        
        // Formata as mensagens para o frontend
        $formattedMessages = array_map(function($msg) {
            return [
                'message' => $msg['content'],
                'is_user' => $msg['sender_type'] === 'user',
                'date' => $msg['formatted_date']
            ];
        }, $messages);
        
        echo json_encode($formattedMessages);
    } else {
        // Se não houver conversa, retorna array vazio
        echo json_encode([]);
    }
    
} catch (Exception $e) {
    error_log("Erro ao buscar mensagens: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao buscar mensagens']);
} 