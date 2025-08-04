<?php
require_once '../config/database.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autorizado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$plan = $data['plan'] ?? null;

if (!$plan || $plan !== 'premium') {
    echo json_encode(['success' => false, 'error' => 'Plano inválido']);
    exit;
}

try {
    // Verifica se já existe uma assinatura ativa
    $stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? AND status = 'active'");
    $stmt->execute([$_SESSION['user_id']]);
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Você já possui uma assinatura ativa']);
        exit;
    }

    // TODO: Integrar com Stripe para processar pagamento
    // Por enquanto, vamos simular uma assinatura bem-sucedida

    $start_date = date('Y-m-d H:i:s');
    $end_date = date('Y-m-d H:i:s', strtotime('+1 month'));

    $stmt = $pdo->prepare("INSERT INTO subscriptions (user_id, status, start_date, end_date) VALUES (?, 'active', ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $start_date, $end_date]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro ao processar assinatura']);
}
?> 