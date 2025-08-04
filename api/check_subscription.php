<?php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
    exit;
}

try {
    // Busca a assinatura ativa do usuário
    $stmt = $pdo->prepare("
        SELECT s.*, p.name as plan_name, p.message_limit, p.features
        FROM user_subscriptions s
        JOIN subscription_plans p ON s.plan_id = p.id
        WHERE s.user_id = ? AND s.status = 'active' AND s.expires_at > NOW()
        ORDER BY s.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $subscription = $stmt->fetch();
    
    if ($subscription) {
        echo json_encode([
            'success' => true,
            'has_subscription' => true,
            'plan' => [
                'name' => $subscription['plan_name'],
                'message_limit' => $subscription['message_limit'],
                'features' => explode("\n", $subscription['features']),
                'expires_at' => $subscription['expires_at']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'has_subscription' => false
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erro ao verificar assinatura']);
} 