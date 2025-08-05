<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/ai_handler.php';
session_start();

header('Content-Type: application/json');

// Verifica se veio do quiz
if (!isset($_SESSION['quiz_data'])) {
    echo json_encode(['success' => false, 'error' => 'Acesso inválido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$avatarId = $data['avatar_id'] ?? null;
$message = $data['message'] ?? '';

if (!$avatarId || !$message) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit;
}

try {
    // Busca os dados do avatar
    $stmt = $pdo->prepare("SELECT * FROM avatars WHERE id = ?");
    $stmt->execute([$avatarId]);
    $avatar = $stmt->fetch();
    
    if (!$avatar) {
        throw new Exception("Avatar não encontrado");
    }
    
    // Obtém a resposta da IA
    $response = getAIResponse($message, $avatar);
    
    // Retorna a resposta
    echo json_encode([
        'success' => true,
        'response' => $response
    ]);

} catch (Exception $e) {
    error_log("Erro ao enviar mensagem: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao enviar mensagem: ' . $e->getMessage()]);
} 