<?php
session_start();
require_once "../config/database.php";

// Recebe os dados do POST
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

// Valida os dados obrigatórios
$required_fields = ['amount', 'customer'];
foreach ($required_fields as $field) {
    if (!isset($data[$field])) {
        echo json_encode(['success' => false, 'message' => 'Campo obrigatório ausente: ' . $field]);
        exit;
    }
}

// Valida os dados do cliente
$customer_fields = ['name', 'email', 'cpf', 'phone'];
foreach ($customer_fields as $field) {
    if (!isset($data['customer'][$field]) || empty($data['customer'][$field])) {
        echo json_encode(['success' => false, 'message' => 'Campo obrigatório do cliente ausente: ' . $field]);
        exit;
    }
}

// Valida o CPF
if (!validateCPF($data['customer']['cpf'])) {
    echo json_encode(['success' => false, 'message' => 'CPF inválido']);
    exit;
}

// Valida o email
if (!filter_var($data['customer']['email'], FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email inválido']);
    exit;
}

// Valida o telefone
if (!validatePhone($data['customer']['phone'])) {
    echo json_encode(['success' => false, 'message' => 'Telefone inválido']);
    exit;
}

// Gera o Pix
$pix_data = generatePix($data['amount'], $data['customer']);

if (!$pix_data || !isset($pix_data['success']) || !$pix_data['success']) {
    echo json_encode([
        'success' => false,
        'message' => isset($pix_data['message']) ? $pix_data['message'] : 'Erro ao gerar Pix'
    ]);
    exit;
}

// Salva os dados do cliente na sessão para uso posterior
$_SESSION['quiz_data']['customer'] = $data['customer'];

// Retorna os dados do Pix
echo json_encode([
    'success' => true,
    'qrcode' => $pix_data['qrcode'],
    'pixCode' => $pix_data['pixCode'],
    'transactionId' => $pix_data['transactionId']
]);

// Função para validar CPF
function validateCPF($cpf) {
    $cpf = preg_replace('/[^0-9]/', '', $cpf);
    
    if (strlen($cpf) != 11) {
        return false;
    }
    
    if (preg_match('/(\d)\1{10}/', $cpf)) {
        return false;
    }
    
    for ($t = 9; $t < 11; $t++) {
        for ($d = 0, $c = 0; $c < $t; $c++) {
            $d += $cpf[$c] * (($t + 1) - $c);
        }
        $d = ((10 * $d) % 11) % 10;
        if ($cpf[$c] != $d) {
            return false;
        }
    }
    
    return true;
}

// Função para validar telefone
function validatePhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) >= 10 && strlen($phone) <= 11;
}

// Função para gerar o Pix
function generatePix($amount, $customer) {
    $api_url = RISEPAY_API_URL;
    
    $data = [
        'amount' => floatval($amount),
        'payment' => [
            'method' => 'pix'
        ],
        'customer' => [
            'name' => $customer['name'],
            'email' => $customer['email'],
            'cpf' => preg_replace('/[^0-9]/', '', $customer['cpf']),
            'phone' => $customer['phone']
        ]
    ];

    $payload = json_encode($data);
    
    $ch = curl_init($api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: ' . RISEPAY_PRIVATE_KEY
    ]);

    // Log detalhado da requisição
    error_log('Requisição RisePay - URL: ' . $api_url);
    error_log('Requisição RisePay - Headers: ' . json_encode([
        'Content-Type: application/json',
        'Authorization: ' . RISEPAY_PRIVATE_KEY
    ]));
    error_log('Requisição RisePay - Payload: ' . $payload);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    // Log de erros cURL se houver
    if (curl_errno($ch)) {
        error_log('Erro cURL: ' . curl_error($ch));
    }
    
    curl_close($ch);

    // Log detalhado da resposta
    error_log('Resposta RisePay - HTTP Code: ' . $http_code);
    error_log('Resposta RisePay - Response: ' . $response);

    if ($http_code !== 200) {
        return [
            'success' => false,
            'message' => 'Erro ao processar pagamento',
            'raw_response' => $response
        ];
    }

    $result = json_decode($response, true);
    
    // Log da estrutura da resposta
    error_log('Estrutura da resposta RisePay: ' . json_encode($result, JSON_PRETTY_PRINT));

    if (!$result || !isset($result['success']) || !$result['success']) {
        return [
            'success' => false,
            'message' => isset($result['message']) ? $result['message'] : 'Erro ao gerar Pix',
            'raw_response' => $response
        ];
    }

    // Verifica se o código Pix está presente na resposta
    if (!isset($result['object']['pix']['qrCode'])) {
        error_log('Código Pix não encontrado na resposta da RisePay');
        return [
            'success' => false,
            'message' => 'Código Pix não encontrado na resposta',
            'raw_response' => $response
        ];
    }

    // Gera o QR Code usando a API do QR Server
    $pixCode = $result['object']['pix']['qrCode'];
    $qrCodeUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=' . urlencode($pixCode);
    
    // Obtém a imagem do QR Code
    $ch = curl_init($qrCodeUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $qrCodeImage = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || !$qrCodeImage) {
        error_log('Erro ao gerar QR Code com QR Server - HTTP Code: ' . $http_code);
        return [
            'success' => false,
            'message' => 'Erro ao gerar QR Code',
            'raw_response' => $response
        ];
    }

    // Converte a imagem para base64
    $qrCodeBase64 = base64_encode($qrCodeImage);

    // Retorna os dados do Pix
    $response_data = [
        'success' => true,
        'qrcode' => $qrCodeBase64,
        'pixCode' => $pixCode,
        'transactionId' => $result['object']['identifier'] ?? ''
    ];

    // Log dos dados que serão retornados
    error_log('Dados retornados para o frontend: ' . json_encode($response_data));

    return $response_data;
} 