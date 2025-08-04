<?php
// Ativar exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/message_counter.php';
require_once __DIR__ . '/../includes/ai_handler.php';
require_once __DIR__ . '/../includes/ImageGenerator.php';
session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$avatarId = $data['avatar_id'] ?? null;
$message = $data['message'] ?? '';

if (!$avatarId || !$message) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit;
}

// Verifica se o usuário pode enviar mensagens
if (!checkMessageLimit($_SESSION['user_id'], $avatarId)) {
    echo json_encode(['success' => false, 'error' => 'Limite de mensagens atingido']);
    exit;
}

try {
    $pdo->beginTransaction();
    
    // Verifica se já existe uma conversa entre o usuário e o avatar
    $stmt = $pdo->prepare("
        SELECT id FROM conversations 
        WHERE user_id = ? AND avatar_id = ?
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id'], $avatarId]);
    $conversation = $stmt->fetch();

    // Se não existir conversa, cria uma nova
    if (!$conversation) {
        $stmt = $pdo->prepare("
            INSERT INTO conversations (user_id, avatar_id)
            VALUES (?, ?)
        ");
        $stmt->execute([$_SESSION['user_id'], $avatarId]);
        $conversationId = $pdo->lastInsertId();
    } else {
        $conversationId = $conversation['id'];
    }
    
    // Insere a mensagem do usuário
    $stmt = $pdo->prepare("
        INSERT INTO messages (conversation_id, sender_type, content)
        VALUES (?, 'user', ?)
    ");
    $stmt->execute([$conversationId, $message]);
    
    // Busca os dados do avatar
    $stmt = $pdo->prepare("SELECT * FROM avatars WHERE id = ?");
    $stmt->execute([$avatarId]);
    $avatar = $stmt->fetch();
    
    if (!$avatar) {
        throw new Exception("Avatar não encontrado");
    }
    
    // --- INTEGRAÇÃO DE IMAGEM ---
    if (isImageRequest($message)) {
        $imageCount = getTodayImageCount($pdo, $_SESSION['user_id']);
        if ($imageCount >= 5) {
            $response = "Limite diário de 5 imagens atingido. Tente novamente amanhã.";
        } else {
            $imageGen = new ImageGenerator();
            // Usa o prompt visual salvo no avatar
            $promptBase = $avatar['appearance_prompt'] ?? null;
            // Fallback genérico
            $genericos = [
                'humana' => 'uma mulher jovem, atraente, expressão sedutora, fotorrealista',
                'elfa' => 'uma elfa mágica, cabelos longos, orelhas pontudas, roupa mística, ultra realista',
                'android' => 'uma androide feminina, corpo metálico, olhos brilhantes, cenário futurista'
            ];
            if (!$promptBase) {
                $promptBase = $genericos[$avatar['appearance_type']] ?? 'avatar feminina, fotorrealista';
            }
            // Opcional: extrair ação do pedido do usuário
            $acao = '';
            if (preg_match('/(em |de |fazendo |posando |usando |com |na |no |em cima de |sentada em |deitada em )(.{3,50})/i', $message, $matches)) {
                $acao = $matches[2];
            }
            $prompt = $acao ? "$promptBase, $acao" : $promptBase;
            try {
                $filename = 'chatimg_' . time() . '_' . uniqid() . '.png';
                $outputPath = __DIR__ . '/../uploads/chat_images/' . $filename;
                if (!is_dir(__DIR__ . '/../uploads/chat_images/')) {
                    mkdir(__DIR__ . '/../uploads/chat_images/', 0777, true);
                }
                $imageGen->generateImage($prompt, $outputPath);
                $imageUrl = 'uploads/chat_images/' . $filename;
                // Salva no banco o registro da imagem gerada
                $stmt = $pdo->prepare("INSERT INTO user_generated_images (user_id, image_url, prompt) VALUES (?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $imageUrl, $prompt]);
                // Mensagem do avatar com a imagem
                $response = '<img src="' . $imageUrl . '" alt="Imagem gerada" class="max-w-xs rounded shadow">';
            } catch (Exception $e) {
                error_log("Erro ao gerar imagem: " . $e->getMessage());
                $response = "Desculpe, tive um problema ao gerar a imagem. Por favor, tente novamente.";
            }
        }
    } else {
        // Obtém a resposta da IA normalmente
    $response = getAIResponse($message, $avatar);
    }
    // --- FIM INTEGRAÇÃO DE IMAGEM ---
    
    // Insere a resposta do avatar
    $stmt = $pdo->prepare("
        INSERT INTO messages (conversation_id, sender_type, content)
        VALUES (?, 'avatar', ?)
    ");
    $stmt->execute([$conversationId, $response]);
    
    $pdo->commit();
    
    // Retorna a resposta e o número de mensagens restantes
    echo json_encode([
        'success' => true,
        'response' => $response,
        'remaining_messages' => getRemainingMessages($_SESSION['user_id'], $avatarId)
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log("Erro ao enviar mensagem: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao enviar mensagem: ' . $e->getMessage()]);
}

// Função para verificar se a mensagem é um pedido de imagem
function isImageRequest($message) {
    $triggers = [
        '/imagem', '/image', 'quero uma imagem', 'me envie uma imagem', 'gera uma imagem', 'me mostre uma imagem', 'me mostra uma imagem', 'desenhe', 'desenha', 'draw', 'picture', 'foto', 'picture of', 'imagem de', 'foto de', 'crie uma imagem', 'cria uma imagem'
    ];
    $msg = mb_strtolower($message);
    foreach ($triggers as $trigger) {
        if (strpos($msg, $trigger) !== false) {
            return true;
        }
    }
    return false;
}

// Função para contar imagens geradas hoje
function getTodayImageCount($pdo, $userId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM user_generated_images WHERE user_id = ? AND DATE(created_at) = CURDATE()");
    $stmt->execute([$userId]);
    return (int)$stmt->fetchColumn();
}
?> 