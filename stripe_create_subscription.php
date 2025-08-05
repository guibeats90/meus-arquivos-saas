<?php
// Ativar exibição de erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Garantir que a resposta seja sempre JSON
header('Content-Type: application/json');

session_start();

// Log do input recebido
$raw_input = file_get_contents('php://input');
error_log("Input recebido: " . $raw_input);

$data = json_decode($raw_input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    error_log("Erro ao decodificar JSON: " . json_last_error_msg());
    echo json_encode(['error' => 'Erro ao processar dados recebidos']);
    exit;
}

$payment_method = $data['payment_method'] ?? null;
$plan = $data['plan'] ?? 'basic';
$email = $data['email'] ?? null;

error_log("Dados processados - Payment Method: $payment_method, Plan: $plan, Email: $email");

// Defina os product_ids dos planos criados na Stripe
$stripe_product_ids = [
  'basic' => 'prod_SG3ZtIV2Fzp6ov',
  'intermediate' => 'prod_SG3lQXCztGwqiL',
  'vip' => 'prod_SG3o9AIk0BBBcu',
  'test' => 'prod_SImQh3qluMBzw9'
];

if (!$payment_method || !$email || !isset($stripe_product_ids[$plan])) {
    error_log("Dados inválidos - Payment Method: $payment_method, Plan: $plan, Email: $email");
    echo json_encode(['error' => 'Dados inválidos.']);
    exit;
}

// Chave secreta do Stripe
$stripe_secret_key = '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';

try {
    // Criar cliente
    $ch = curl_init('https://api.stripe.com/v1/customers');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_USERPWD, $stripe_secret_key . ':');
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'payment_method' => $payment_method,
        'email' => $email,
        'invoice_settings' => ['default_payment_method' => $payment_method]
    ]));
    
    $response = curl_exec($ch);
    $customer = json_decode($response, true);
    
    if (curl_errno($ch)) {
        throw new Exception('Erro ao criar cliente: ' . curl_error($ch));
    }
    
    if (isset($customer['error'])) {
        throw new Exception('Erro do Stripe: ' . $customer['error']['message']);
    }
    
    error_log("Cliente criado: " . $customer['id']);
    
    // Criar assinatura
    $ch = curl_init('https://api.stripe.com/v1/subscriptions');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_USERPWD, $stripe_secret_key . ':');
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'customer' => $customer['id'],
        'items' => [[
            'price_data' => [
                'product' => $stripe_product_ids[$plan],
                'unit_amount' => ($plan === 'test' ? 100 : ($plan === 'basic' ? 1990 : ($plan === 'intermediate' ? 2990 : 4990))),
                'currency' => ($plan === 'test' ? 'brl' : 'eur'),
                'recurring' => [
                    'interval' => ($plan === 'test' ? 'day' : 'month')
                ]
            ]
        ]],
        'expand' => ['latest_invoice.payment_intent']
    ]));
    
    $response = curl_exec($ch);
    $subscription = json_decode($response, true);
    
    if (curl_errno($ch)) {
        throw new Exception('Erro ao criar assinatura: ' . curl_error($ch));
    }
    
    if (isset($subscription['error'])) {
        throw new Exception('Erro do Stripe: ' . $subscription['error']['message']);
    }
    
    error_log("Assinatura criada: " . $subscription['id']);
    
    echo json_encode([
        'client_secret' => $subscription['latest_invoice']['payment_intent']['client_secret']
    ]);
    
} catch (Exception $e) {
    error_log("Erro: " . $e->getMessage());
    echo json_encode(['error' => $e->getMessage()]);
} 