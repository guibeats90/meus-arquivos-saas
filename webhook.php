<?php
session_start();
require_once "../config/database.php";

// Log do webhook recebido
error_log('Webhook RisePay recebido: ' . file_get_contents('php://input'));

// Recebe os dados do webhook
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Dados inválidos']);
    exit;
}

// Verifica se é uma notificação válida
if (!isset($data['type']) || !isset($data['data'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Notificação inválida']);
    exit;
}

// Processa o evento
switch ($data['type']) {
    case 'pix.generated':
        // Pix gerado
        error_log('Pix gerado: ' . json_encode($data['data']));
        break;

    case 'transaction.approved':
        // Pagamento Pix aprovado
        $transaction_id = $data['data']['id'];
        $customer_email = $data['data']['customer']['email'] ?? null;
        $plan_name = $data['data']['description'] ?? null;
        
        if ($customer_email) {
            // Busca o usuário pelo email
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$customer_email]);
            $user = $stmt->fetch();
            if ($user) {
                // Busca o plano pelo nome (se possível)
                $plan_id = null;
                if ($plan_name) {
                    $stmtPlan = $pdo->prepare("SELECT id FROM subscription_plans WHERE name LIKE ? LIMIT 1");
                    $stmtPlan->execute(["%$plan_name%"]);
                    $plan = $stmtPlan->fetch();
                    if ($plan) {
                        $plan_id = $plan['id'];
                    }
                }
                // Atualiza ou cria a assinatura do usuário
                $stmtSub = $pdo->prepare("SELECT id FROM user_subscriptions WHERE user_id = ? ORDER BY id DESC LIMIT 1");
                $stmtSub->execute([$user['id']]);
                $subscription = $stmtSub->fetch();
                if ($subscription) {
                    // Atualiza assinatura existente
                    $stmtUpdate = $pdo->prepare("UPDATE user_subscriptions SET status = 'active', stripe_subscription_id = ? WHERE id = ?");
                    $stmtUpdate->execute([$transaction_id, $subscription['id']]);
                } else {
                    // Cria nova assinatura
                    $stmtInsert = $pdo->prepare("INSERT INTO user_subscriptions (user_id, plan_id, plan, stripe_subscription_id, status, started_at) VALUES (?, ?, ?, ?, 'active', NOW())");
                    $stmtInsert->execute([
                        $user['id'],
                        $plan_id ?? 1,
                        $plan_name ?? 'Pix',
                        $transaction_id
                    ]);
                }
            }
        }
        break;

    case 'transaction.refused':
        // Pagamento recusado
        // (Opcional: atualizar status para 'refused' se desejar)
        break;

    case 'subscription.approved':
        // Assinatura aprovada
        $subscription_id = $data['data']['id'];
        $status = 'active';
        
        try {
            $stmt = $pdo->prepare("UPDATE subscriptions SET status = ? WHERE subscription_id = ?");
            $stmt->execute([$status, $subscription_id]);
        } catch (PDOException $e) {
            error_log('Erro ao atualizar status da assinatura: ' . $e->getMessage());
        }
        break;

    case 'subscription.delayed':
        // Assinatura atrasada
        $subscription_id = $data['data']['id'];
        $status = 'delayed';
        
        try {
            $stmt = $pdo->prepare("UPDATE subscriptions SET status = ? WHERE subscription_id = ?");
            $stmt->execute([$status, $subscription_id]);
        } catch (PDOException $e) {
            error_log('Erro ao atualizar status da assinatura: ' . $e->getMessage());
        }
        break;

    case 'subscription.canceled':
        // Assinatura cancelada
        // (Opcional: atualizar status para 'canceled' se desejar)
        break;
}

// Responde com sucesso
http_response_code(200);
echo json_encode(['success' => true]); 