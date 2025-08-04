<?php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$planId = $data['plan_id'] ?? null;
$paymentMethod = $data['payment_method'] ?? null;

if (!$planId || !$paymentMethod) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Busca informações do plano
    $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE id = ?");
    $stmt->execute([$planId]);
    $plan = $stmt->fetch();
    
    if (!$plan) {
        throw new Exception('Plano não encontrado');
    }
    
    // Aqui você implementaria a integração com o gateway de pagamento
    // Por enquanto, vamos simular um pagamento bem-sucedido
    
    // Calcula a data de expiração conforme o plano
    $duration = $plan['duration_days'] ?? 30;
    $expiresAt = date('Y-m-d H:i:s', strtotime("+$duration days"));
    
    // Insere a assinatura
    $stmt = $pdo->prepare("
        INSERT INTO user_subscriptions (
            user_id, plan_id, plan, status, stripe_subscription_id, started_at, expires_at, created_at
        ) VALUES (?, ?, ?, 'active', ?, NOW(), ?, NOW())
    ");
    $stmt->execute([
        $_SESSION['user_id'],
        $planId,
        $plan['name'],
        null, // stripe_subscription_id (NULL por padrão)
        $expiresAt
    ]);
    
    // Atualiza o campo vip_until do usuário
    $stmt = $pdo->prepare("UPDATE users SET vip_until = ? WHERE id = ?");
    $stmt->execute([$expiresAt, $_SESSION['user_id']]);
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Assinatura ativada com sucesso!',
        'subscription' => [
            'plan_name' => $plan['name'],
            'expires_at' => $expiresAt
        ]
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} 