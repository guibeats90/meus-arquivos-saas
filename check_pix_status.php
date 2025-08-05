<?php
session_start();
require_once "../config/database.php";
require_once "../config/risepay.php";

// Recebe o ID da transação
$data = json_decode(file_get_contents('php://input'), true);
$transaction_id = $data['transaction_id'] ?? $_POST['transaction_id'] ?? $_GET['transaction_id'] ?? '';

if (empty($transaction_id)) {
    echo json_encode(['success' => false, 'message' => 'ID da transação não fornecido']);
    exit;
}

// Consulta o status na RisePay
$api_url = 'https://api.risepay.com.br/api/External/Transactions/' . $transaction_id;

$ch = curl_init($api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: ' . RISEPAY_PRIVATE_KEY
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Log da resposta
error_log('Verificação de status RisePay - Transaction ID: ' . $transaction_id);
error_log('Resposta RisePay - HTTP Code: ' . $http_code);
error_log('Resposta RisePay - Response: ' . $response);

if ($http_code !== 200) {
    echo json_encode(['success' => false, 'message' => 'Erro ao verificar status do pagamento']);
    exit;
}

$result = json_decode($response, true);

if (!$result || !isset($result['object']['status'])) {
    echo json_encode(['success' => false, 'message' => 'Resposta inválida da API']);
    exit;
}

// Mapeia os status da RisePay
$status_map = [
    'Waiting Payment' => 'pending',
    'Paid' => 'paid',
    'Expired' => 'expired',
    'Canceled' => 'canceled'
];

$status = $status_map[$result['object']['status']] ?? 'unknown';

echo json_encode([
    'success' => true,
    'status' => $status,
    'raw_status' => $result['object']['status']
]); 