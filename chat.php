<?php
// Ativar exibição de erros para debug
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'includes/message_counter.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$avatarId = $_GET['avatar_id'] ?? null;
if (!$avatarId) {
    header('Location: index.php');
    exit;
}

try {
    // Busca os dados do avatar
$stmt = $pdo->prepare("SELECT * FROM avatars WHERE id = ?");
    $stmt->execute([$avatarId]);
$avatar = $stmt->fetch();

if (!$avatar) {
    header('Location: index.php');
    exit;
}

    // Verifica o número de mensagens restantes sem incrementar o contador
    $remainingMessages = getRemainingMessages($_SESSION['user_id'], $avatarId);

    // Busca assinatura ativa do usuário
    $stmt = $pdo->prepare("
        SELECT s.expires_at, p.message_limit
        FROM user_subscriptions s
        JOIN subscription_plans p ON s.plan_id = p.id
        WHERE s.user_id = ? AND s.status = 'active' AND s.expires_at > NOW()
        ORDER BY s.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $sub = $stmt->fetch();
    $isUnlimited = ($sub && is_null($sub['message_limit']));
    $vipExpiresAt = $sub ? $sub['expires_at'] : null;
    $diasRestantes = null;
    if ($vipExpiresAt) {
        $agora = new DateTime();
        $expira = new DateTime($vipExpiresAt);
        $intervalo = $agora->diff($expira);
        $diasRestantes = $intervalo->days;
    }
} catch (Exception $e) {
    die("Erro ao carregar dados: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat com <?php echo htmlspecialchars($avatar['name']); ?> - DesireChat</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d1a1a 100%);
            color: #fff;
        }
        .nav-wrapper {
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        .nav-wrapper.scrolled {
            background: rgba(0, 0, 0, 0.9);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
        }
        .logo {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(45deg, #ff3366, #ff0000);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease;
        }
        @media (max-width: 768px) {
            .logo {
                font-size: 1.75rem;
            }
        }
        .logo:hover {
            transform: scale(1.05);
        }
        .nav-menu {
            display: none;
        }
        .nav-menu.active {
            display: flex;
            background: rgba(0, 0, 0, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 0 0 10px 10px;
            padding: 1rem;
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.3);
        }
        @media (min-width: 768px) {
            .nav-menu {
                display: flex;
                background: none;
                backdrop-filter: none;
                box-shadow: none;
                padding: 0;
            }
            .mobile-menu-btn {
                display: none;
            }
        }
        .nav-link {
            position: relative;
            color: white;
            text-decoration: none;
            padding: 0.5rem 1rem;
            transition: color 0.3s ease;
        }
        .nav-link::after {
            content: '';
            position: absolute;
            width: 0;
            height: 2px;
            bottom: 0;
            left: 50%;
            background: linear-gradient(45deg, #ff3366, #ff0000);
            transition: all 0.3s ease;
            transform: translateX(-50%);
        }
        .nav-link:hover {
            color: #ff3366;
        }
        .nav-link:hover::after {
            width: 80%;
        }
        .chat-container {
            height: calc(100vh - 200px);
            overflow-y: auto;
            scroll-behavior: smooth;
            padding-bottom: 0.5rem;
            margin-top: 6rem;
        }
        .message {
            max-width: 70%;
            margin-bottom: 1rem;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeIn 0.3s ease forwards;
        }
        .user-message {
            margin-left: auto;
            background: #ef4444;
            border-radius: 1rem 1rem 0 1rem;
        }
        .avatar-message {
            margin-right: auto;
            background: #374151;
            border-radius: 1rem 1rem 1rem 0;
        }
        .message-status {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 0.25rem;
            text-align: right;
        }
        .typing-dots {
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .typing-dots span {
            width: 8px;
            height: 8px;
            background: #9ca3af;
            border-radius: 50%;
            animation: typing 1.4s infinite ease-in-out;
        }
        .typing-dots span:nth-child(1) { animation-delay: 0s; }
        .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
        .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typing {
            0%, 60%, 100% { transform: translateY(0); }
            30% { transform: translateY(-4px); }
        }
        @keyframes fadeIn {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        @media (max-width: 640px) {
            .chat-container {
                height: calc(100vh - 160px);
                padding-bottom: 3rem;
            }
            .message {
                max-width: 85%;
            }
            .message-form {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                padding: 1rem;
                background: rgba(17, 24, 39, 0.95);
                backdrop-filter: blur(5px);
                z-index: 10;
            }
            .message-input {
                width: calc(100% - 80px);
            }
            .avatar-info {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
            .avatar-info img {
                width: 3rem;
                height: 3rem;
            }
            .avatar-info h2 {
                font-size: 1.25rem;
            }
            .avatar-info p {
                font-size: 0.875rem;
            }
            .messages-info {
                margin-top: 0.5rem;
                text-align: left;
            }
            .messages-info p {
                display: flex;
                align-items: center;
                gap: 0.5rem;
            }
            .get-credits-btn {
                padding: 0.25rem 0.75rem;
                font-size: 0.75rem;
            }
        }
        /* Estilos específicos para os cards de plano no modal */
        .plan-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            padding: 2rem;
        }
        .plan-card ul {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            margin: 0;
            padding: 0;
            list-style: none;
            margin-bottom: 2.5rem;
        }
        .plan-card ul li {
            display: flex;
            align-items: flex-start;
            padding: 0.625rem 0;
            margin: 0;
        }
        .plan-card ul li svg {
            flex-shrink: 0;
            width: 1.25rem;
            height: 1.25rem;
            margin-right: 1rem;
            margin-top: 0.2rem;
            color: #10B981;
        }
        .plan-card ul li span {
            flex-grow: 1;
            line-height: 1.5;
        }
        .plan-card ul li span.highlight {
            font-weight: bold;
            color: #A78BFA;
            text-shadow: 0 0 10px rgba(167, 139, 250, 0.3);
        }
        /* Estilos para o modal de imagem */
        .image-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }
        
        .image-modal img {
            max-width: 95%;
            max-height: 95vh;
            object-fit: contain;
        }
        
        /* Melhorias na caixa de texto */
        .message-input {
            min-height: 40px;
            max-height: 120px;
            resize: none;
            overflow-y: auto;
            line-height: 1.5;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }
        
        /* Ajustes para mobile */
        @media (max-width: 640px) {
            .message-form {
                padding: 0.75rem;
                background: rgba(17, 24, 39, 0.95);
                backdrop-filter: blur(5px);
                border-top: 1px solid rgba(255, 255, 255, 0.1);
            }
            
            .message-input {
                font-size: 16px; /* Evita zoom automático no iOS */
            }
            
            .avatar-info img {
                cursor: pointer;
                transition: transform 0.2s;
            }
            
            .avatar-info img:hover {
                transform: scale(1.05);
            }
        }
        
        /* Estilos melhorados para o cabeçalho */
        .avatar-info {
            background: linear-gradient(135deg, rgba(31, 41, 55, 0.9) 0%, rgba(17, 24, 39, 0.95) 100%);
            backdrop-filter: blur(10px);
            border-radius: 1rem;
            padding: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .avatar-info img {
            border: 2px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }
        
        .avatar-info img:hover {
            transform: scale(1.05);
            border-color: rgba(239, 68, 68, 0.5);
            box-shadow: 0 0 20px rgba(239, 68, 68, 0.2);
        }
        
        .avatar-name {
            background: linear-gradient(45deg, #ff3366, #ff0000);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }
        
        .messages-counter {
            background: rgba(31, 41, 55, 0.8);
            padding: 0.2rem 0.6rem;
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(5px);
            transition: all 0.3s ease;
            font-size: 0.9rem;
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            max-width: 260px;
            white-space: nowrap;
            z-index: 2;
        }
        @media (max-width: 640px) {
            .messages-counter {
                position: static;
                right: auto;
                top: auto;
                transform: none;
                margin-top: 0.3rem;
                margin-bottom: 0.1rem;
                font-size: 0.78rem;
                padding: 0.15rem 0.5rem;
                max-width: 90vw;
                white-space: normal;
                text-align: center;
                display: block;
                border-radius: 0.7rem;
            }
            .avatar-info {
                flex-direction: column !important;
                align-items: flex-start !important;
            }
            .avatar-info .messages-counter {
                margin-left: 0;
                margin-right: 0;
                width: 100%;
                max-width: 90vw;
            }
        }
        
        .messages-counter .count {
            font-weight: bold;
            font-size: 1.1em;
            transition: all 0.3s ease;
        }
        
        .messages-counter .count.low {
            color: #ef4444;
            text-shadow: 0 0 10px rgba(239, 68, 68, 0.3);
        }
        
        .messages-counter .count.normal {
            color: #10b981;
            text-shadow: 0 0 10px rgba(16, 185, 129, 0.3);
        }
        
        /* Ajustes no cabeçalho fixo */
        .chat-header {
            position: fixed;
            top: 4rem;
            left: 0;
            right: 0;
            z-index: 100;
            padding: 0 1rem;
            background: linear-gradient(135deg, rgba(31, 41, 55, 0.95) 0%, rgba(17, 24, 39, 0.98) 100%);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            height: 4.5rem;
            display: flex;
            align-items: center;
        }
        
        .avatar-info {
            background: transparent;
            backdrop-filter: none;
            border-radius: 0;
            padding: 0;
            box-shadow: none;
            border: none;
            width: 100%;
        }
        
        .avatar-info .flex {
            align-items: center;
            padding-top: 0.35rem;
        }
        
        .avatar-info img {
            width: 3.5rem;
            height: 3.5rem;
            border: 2px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            margin-top: 0.15rem;
        }
        
        .messages-counter {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(31, 41, 55, 0.8);
            padding: 0.4rem 0.8rem;
            border-radius: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
            transition: all 0.3s ease;
            font-size: 0.9rem;
        }
        
        /* Ajuste no container principal para compensar o cabeçalho fixo */
        .chat-container {
            margin-top: 5rem; /* Voltando para 5rem para manter o alinhamento correto */
        }
        
        /* Estilos para imagens no chat */
        .message img {
            max-width: 300px;
            max-height: 300px;
            border-radius: 8px;
            margin: 8px 0;
            cursor: pointer;
            transition: transform 0.2s ease;
        }
        
        .message img:hover {
            transform: scale(1.02);
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- POP-UP DE MANUTENÇÃO -->
    <div id="popup-manutencao" style="position: fixed; bottom: 2rem; right: 2rem; z-index: 99999; background: rgba(31,41,55,0.98); color: #fff; padding: 1.2rem; border-radius: 1.2rem; box-shadow: 0 4px 24px rgba(0,0,0,0.18); font-size: 1rem; max-width: 340px; min-width: 220px; width: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center;">
        <button onclick="document.getElementById('popup-manutencao').style.display='none'" style="background: none; border: none; color: #fff; font-size: 1.4rem; cursor: pointer; position: absolute; top: 0.7rem; right: 1rem; line-height: 1;">&times;</button>
        <span style="display: block; margin-bottom: 1.1rem;">🛠️<br>Sistema de imagens, vídeos e áudios em manutenção.<br><b>Não se preocupe, você ganhará 1 mês grátis de assinatura enquanto resolvemos o problema.</b><br>Enquanto isso, você pode conversar normalmente com sua Avatar.</span>
        <button id="nao-mostrar-popup" style="margin-top: 0.5rem; background: #222; border: none; color: #bbb; font-size: 0.97rem; cursor: pointer; border-radius: 0.7rem; padding: 0.4rem 1.1rem; transition: background 0.2s;">Não mostrar novamente</button>
    </div>
    <style>
    #popup-manutencao { min-width: unset !important; width: 100% !important; max-width: 340px; }
    @media (max-width: 640px) {
        #popup-manutencao {
            position: fixed;
            top: 50%;
            left: 50%;
            right: auto;
            bottom: auto;
            transform: translate(-50%, -50%);
            max-width: 90vw;
            width: 90vw !important;
            min-width: unset !important;
            font-size: 1rem;
            padding: 1.2rem;
            border-radius: 1.2rem;
            box-shadow: 0 4px 24px rgba(0,0,0,0.18);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        #popup-manutencao button#nao-mostrar-popup {
            font-size: 1rem;
            width: 100%;
            margin-top: 1.1rem;
            padding: 0.5rem 0;
        }
        #popup-manutencao span {
            margin-bottom: 1.1rem;
        }
    }
    #popup-manutencao button#nao-mostrar-popup:hover {
        background: #333;
        color: #fff;
    }
    </style>
    <div class="nav-wrapper">
        <nav class="container mx-auto">
            <div class="flex justify-between items-center py-4 px-6">
                <a href="index.php" class="logo">DesireChat</a>
                <button class="mobile-menu-btn text-white md:hidden" onclick="toggleMenu()">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                    </svg>
                </button>
                <div class="nav-menu flex-col md:flex-row md:items-center md:justify-end mt-4 md:mt-0 space-y-2 md:space-y-0 md:space-x-6">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="index.php" class="nav-link">Início</a>
                        <a href="meus_chats.php" class="nav-link">Meus Chats</a>
                        <a href="meus_avatars.php" class="nav-link">Meus Avatares</a>
                        <a href="logout.php" class="nav-link">Sair</a>
                    <?php else: ?>
                        <a href="login.php" class="nav-link">Login</a>
                        <a href="planos.php" class="nav-link">Planos</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
        </div>

    <div class="chat-header">
        <div class="container mx-auto">
            <div class="flex items-center justify-between avatar-info">
                <div class="flex items-center space-x-4 flex-wrap">
                    <img src="<?php echo htmlspecialchars($avatar['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($avatar['name']); ?>" 
                         class="w-14 h-14 rounded-full object-cover cursor-pointer"
                         onclick="openImageModal(this.src)">
                    <div>
                        <h2 class="text-xl font-bold avatar-name"><?php echo htmlspecialchars($avatar['name']); ?></h2>
                        <p class="text-sm text-gray-300"><?php echo htmlspecialchars($avatar['age']); ?> anos</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <main class="container mx-auto px-4 py-8">
        <div class="bg-gray-800 rounded-lg p-4 chat-container mb-4">
            <div id="chatMessages" class="space-y-4">
                <!-- As mensagens serão carregadas aqui via JavaScript -->
            </div>
            <div id="typingIndicator" class="hidden">
                <div class="message p-4 avatar-message">
                    <div class="flex items-center space-x-2">
                        <div class="typing-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <span class="text-gray-300">digitando...</span>
                        </div>
                    </div>
                </div>
            </div>

        <form id="messageForm" class="flex space-x-4 message-form">
            <textarea id="messageInput" 
                   class="flex-1 bg-gray-700 text-white rounded-lg px-4 py-2 focus:outline-none focus:ring-2 focus:ring-red-500 message-input"
                   placeholder="Digite sua mensagem..."
                   rows="1"></textarea>
                    <button type="submit" 
                    class="bg-red-500 text-white px-6 py-2 rounded-lg hover:bg-red-600 transition">
                        Enviar
                    </button>
            </form>
    </main>

    <!-- Modal de Planos -->
    <div id="plansModal" class="fixed inset-0 hidden" style="z-index: 999999;">
        <div class="fixed inset-0 bg-black bg-opacity-50"></div>
        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex items-start justify-center min-h-screen p-2 pt-20">
                <div class="bg-gray-800 rounded-lg w-[150%] md:w-[80%] lg:w-[70%] max-w-2xl relative my-4">
                    <button onclick="closePlansModal()" class="absolute top-2 right-2 text-white hover:text-red-500 text-lg">✕</button>
                    
                    <div class="p-4">
                        <div class="text-center mb-4">
                            <h2 class="text-xl font-bold">Suas mensagens acabaram!</h2>
                            <p class="text-sm text-gray-300 mt-1">Escolha um plano para continuar conversando:</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <?php
                            $plans = [
                                'Básico' => [
                                    'price' => 19.90,
                                    'benefits' => [
                                        '100 mensagens por dia',
                                        '5 avatares simultâneos',
                                        'Histórico de 7 dias',
                                        'Suporte por email'
                                    ],
                                    'checkout_link' => 'https://buy.stripe.com/3cscQ68Ul39acnK6oD'
                                ],
                                'Intermediário' => [
                                    'price' => 29.90,
                                    'benefits' => [
                                        'Mensagens ilimitadas',
                                        ['Receber fotos explícitas da sua avatar🔞', true],
                                        '15 avatares simultâneos',
                                        'Histórico completo',
                                        'Suporte prioritário 24/7'
                                    ],
                                    'checkout_link' => 'https://buy.stripe.com/3cseYec6xdNOgE0cN2'
                                ],
                                'Avançado' => [
                                    'price' => 49.90,
                                    'benefits' => [
                                        'Mensagens ilimitadas',
                                        ['Receber fotos e vídeos explícitos da sua avatar🔞', true],
                                        ['Receber áudios sem limites da sua avatar🔞', true],
                                        'Avatares ilimitados',
                                        'Histórico completo',
                                        'Suporte VIP 24/7',
                                        'Acesso exclusivo a avatares VIP',
                                        'Personalização de avatares'
                                    ],
                                    'checkout_link' => 'https://buy.stripe.com/aEUbM2c6xh0087u8wN'
                                ]
                            ];

                            foreach ($plans as $name => $plan):
                            ?>
                                <div class="bg-gray-700 rounded-lg p-3 flex flex-col">
                                    <div class="text-center mb-2">
                                        <div class="text-pink-500 text-xs uppercase tracking-wide">
                                            <?php 
                                            echo $name === 'Básico' ? 'Plano Inicial' : 
                                                 ($name === 'Intermediário' ? 'Mais Popular' : 'Plano VIP');
                                            ?>
                                        </div>
                                        <h3 class="text-lg font-bold mt-1"><?php echo $name; ?></h3>
                                        <div class="mt-1">
                                            <span class="text-2xl font-bold">€<?php echo number_format($plan['price'], 2); ?></span>
                                            <span class="text-sm text-gray-400">/mês</span>
                                        </div>
                                    </div>
                                    
                                    <ul class="text-xs space-y-1 mb-3 flex-grow">
                                        <?php foreach ($plan['benefits'] as $benefit): ?>
                                            <li class="flex items-start">
                                                <svg class="w-3 h-3 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                <?php
                                                if (is_array($benefit)) {
                                                    echo '<span class="ml-1 font-medium text-purple-400">' . $benefit[0] . '</span>';
                                                } else {
                                                    echo '<span class="ml-1">' . $benefit . '</span>';
                                                }
                                                ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                    
                                    <a href="<?php echo $plan['checkout_link']; ?>" 
                                       target="_blank"
                                       class="w-full bg-gradient-to-r from-red-500 to-pink-500 text-white py-1.5 rounded-full hover:from-red-600 hover:to-pink-600 transition text-sm font-medium text-center">
                                        🚀 Assinar Agora
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para visualização de imagem -->
    <div id="imageModal" class="image-modal" onclick="closeImageModal()">
        <img id="modalImage" src="" alt="Imagem em tela cheia">
    </div>

    <script>
        const chatMessages = document.getElementById('chatMessages');
        const messageForm = document.getElementById('messageForm');
        const messageInput = document.getElementById('messageInput');
        const typingIndicator = document.getElementById('typingIndicator');
        let remainingMessages = <?php echo $remainingMessages; ?>;

        // Função para gerar um número aleatório entre min e max
        function getRandomTime(min, max) {
            return Math.floor(Math.random() * (max - min + 1) + min) * 1000;
        }

        // Função para rolar para a última mensagem
        function scrollToBottom() {
            const chatContainer = document.querySelector('.chat-container');
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        // Função para adicionar uma mensagem ao chat
        function addMessage(message, isUser, date, status = null) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message p-4 ${isUser ? 'user-message' : 'avatar-message'}`;
            
            const messageContent = document.createElement('p');
            // Verifica se a mensagem contém uma tag img
            if (message.includes('<img')) {
                messageContent.innerHTML = message;
            } else {
            messageContent.textContent = message;
            }
            messageDiv.appendChild(messageContent);
            
            if (date) {
                const dateSpan = document.createElement('span');
                dateSpan.className = 'text-xs text-gray-400 block mt-1';
                dateSpan.textContent = date;
                messageDiv.appendChild(dateSpan);
            }

            if (status && isUser) {
                const statusSpan = document.createElement('span');
                statusSpan.className = 'message-status';
                statusSpan.textContent = status;
                messageDiv.appendChild(statusSpan);
            }
            
            chatMessages.appendChild(messageDiv);
            scrollToBottom();
            return messageDiv;
        }

        // Função para atualizar o status de uma mensagem
        function updateMessageStatus(messageDiv, status) {
            const statusSpan = messageDiv.querySelector('.message-status');
            if (statusSpan) {
                statusSpan.textContent = status;
            }
        }

        // Função para mostrar/ocultar o indicador de digitação
        function showTypingIndicator(show) {
            typingIndicator.classList.toggle('hidden', !show);
            if (show) {
                chatMessages.scrollTop = chatMessages.scrollHeight;
            }
        }

        // Função para enviar mensagem
        async function sendMessage() {
            const message = messageInput.value.trim();
            if (!message) return;

            try {
                messageInput.value = '';
                messageInput.style.height = 'auto';

                const now = new Date().toLocaleString('pt-BR', { 
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                });
                
                const userMessageDiv = addMessage(message, true, now, 'Enviado');

                setTimeout(() => {
                    updateMessageStatus(userMessageDiv, 'Entregue');
                    
                    const seenDelay = getRandomTime(2, 7);
                    setTimeout(() => {
                        updateMessageStatus(userMessageDiv, 'Visto');
                        showTypingIndicator(true);
                        
                        fetch('api/send_message.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                                avatar_id: <?php echo $avatarId; ?>,
                        message: message
                    })
                        })
                        .then(response => response.json())
                        .then(data => {
                if (data.success) {
                                const typingTime = getRandomTime(2, 5);
                                setTimeout(() => {
                                    showTypingIndicator(false);
                                    addMessage(data.response, false, now);
                                    
                                    if (data.remaining_messages !== undefined) {
                                        updateMessageCounter(data.remaining_messages);
                                    }
                                }, typingTime);
                            } else {
                                showTypingIndicator(false);
                                if (data.error === 'Limite de mensagens atingido') {
                                    showPlansModal();
                                } else {
                                alert('Erro ao enviar mensagem: ' + data.error);
                                }
                            }
                        })
                        .catch(error => {
                            showTypingIndicator(false);
                            console.error('Erro:', error);
                            alert('Erro ao enviar mensagem. Tente novamente.');
                        });
                    }, seenDelay);
                }, 500);
            } catch (error) {
                showTypingIndicator(false);
                console.error('Erro:', error);
                alert('Erro ao enviar mensagem. Tente novamente.');
            }
        }

        // Event listeners
        messageForm.addEventListener('submit', (e) => {
            e.preventDefault();
            sendMessage();
        });

        // Carrega o histórico de mensagens
        async function loadMessages() {
            try {
                const response = await fetch(`api/get_messages.php?avatar_id=<?php echo $avatarId; ?>`);
                const messages = await response.json();
                
                messages.forEach(msg => {
                    addMessage(msg.message, msg.is_user, msg.date);
                });
                
                scrollToBottom();
                // Foca no input de mensagem
                messageInput.focus();
            } catch (error) {
                console.error('Erro ao carregar mensagens:', error);
            }
            }

        // Carrega as mensagens quando a página carregar
        loadMessages();

        // Adiciona event listener para imagens do chat
        document.addEventListener('click', function(e) {
            if (e.target.tagName === 'IMG' && e.target.closest('.message')) {
                openImageModal(e.target.src);
            }
        });

        // Função para abrir imagem em tela cheia
        function openImageModal(src) {
            const modal = document.getElementById('imageModal');
            const modalImg = document.getElementById('modalImage');
            modal.style.display = 'flex';
            modalImg.src = src;
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        // Auto-resize da textarea
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
            
            // Limita a altura máxima
            if (this.scrollHeight > 120) {
                this.style.height = '120px';
                this.style.overflowY = 'auto';
            } else {
                this.style.overflowY = 'hidden';
            }
        });

        // Ajusta a textarea quando a página carrega
        window.addEventListener('load', function() {
            messageInput.style.height = 'auto';
        });

        // Melhorias na textarea
        messageInput.addEventListener('keydown', function(e) {
            // Se for Enter sem Shift, envia a mensagem
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (this.value.trim()) {
                    sendMessage();
                }
            }
            // Se for Enter com Shift, quebra linha
            else if (e.key === 'Enter' && e.shiftKey) {
                // Permite o comportamento padrão (quebra de linha)
            }
            // Se for o botão de enviar do teclado virtual
            else if (e.key === 'Enter' && e.isComposing) {
                // Permite o comportamento padrão (quebra de linha)
            }
        });

        // Atualiza o contador de mensagens com animação
        function updateMessageCounter(count) {
            const counter = document.querySelector('.count');
            if (!counter) return;
            counter.textContent = count;
            counter.className = `count ml-1 ${count <= 1 ? 'low' : 'normal'}`;
            // Adiciona uma animação suave
            counter.style.transform = 'scale(1.2)';
            setTimeout(() => {
                counter.style.transform = 'scale(1)';
            }, 200);
        }
    </script>
    <script>
        function toggleMenu() {
            const menu = document.querySelector('.nav-menu');
            menu.classList.toggle('active');
        }

        // Fecha o menu quando clicar em um link em mobile
        document.querySelectorAll('.nav-menu a').forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 768) {
                    document.querySelector('.nav-menu').classList.remove('active');
                }
            });
        });

        // Fecha o menu quando redimensionar a tela para desktop
        window.addEventListener('resize', () => {
            if (window.innerWidth >= 768) {
                document.querySelector('.nav-menu').classList.add('active');
            }
        });

        // Efeito de scroll no header
        window.addEventListener('scroll', () => {
            const nav = document.querySelector('.nav-wrapper');
            if (window.scrollY > 50) {
                nav.classList.add('scrolled');
            } else {
                nav.classList.remove('scrolled');
            }
        });

        function showPlansModal() {
            document.getElementById('plansModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            // Rola para o topo do modal
            window.scrollTo(0, 0);
        }

        function closePlansModal() {
            document.getElementById('plansModal').classList.add('hidden');
            document.body.style.overflow = '';
        }
    </script>
    <script>
    // Não mostrar novamente
    document.addEventListener('DOMContentLoaded', function() {
        var popup = document.getElementById('popup-manutencao');
        var btnNaoMostrar = document.getElementById('nao-mostrar-popup');
        if (localStorage.getItem('hidePopupManutencao') === '1') {
            if (popup) popup.style.display = 'none';
        }
        if (btnNaoMostrar) {
            btnNaoMostrar.onclick = function() {
                if (popup) popup.style.display = 'none';
                localStorage.setItem('hidePopupManutencao', '1');
            };
        }
    });
    </script>
</body>
</html> 