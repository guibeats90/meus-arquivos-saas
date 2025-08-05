<?php
session_start();
require_once "../config/database.php";

// Verifica se o usuário veio do quiz
if (!isset($_SESSION['quiz_data'])) {
    header('Location: index.php');
    exit;
}

// Verifica se o ID do avatar foi fornecido
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$avatarId = $_GET['id'];

// Busca os dados do avatar
$stmt = $pdo->prepare("SELECT * FROM avatars WHERE id = ?");
$stmt->execute([$avatarId]);
$avatarData = $stmt->fetch();

if (!$avatarData) {
    header('Location: index.php');
    exit;
}

// Define o limite de mensagens
$messageLimit = 100;
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat com <?php echo htmlspecialchars($avatarData['name']); ?> - DesireChat</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d1a1a 100%);
            color: #fff;
            height: 100vh;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
        }

        .chat-container {
            flex: 1;
            overflow-y: auto;
            padding: 1rem;
            margin-top: 7rem;
            margin-bottom: 5rem;
            scroll-behavior: smooth;
            -webkit-overflow-scrolling: touch;
        }

        #chatMessages {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            padding-top: 2rem; /* Espaço extra no topo para garantir que nada fique sob o cabeçalho */
        }

        .chat-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 100;
            padding: 0.75rem 1rem;
            background: linear-gradient(135deg, rgba(31, 41, 55, 0.95) 0%, rgba(17, 24, 39, 0.98) 100%);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .header-content {
            display: flex;
            align-items: center;
            justify-content: space-between;
            max-width: 1200px;
            margin: 0 auto;
            gap: 1rem;
        }

        .avatar-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            min-width: 0;
        }

        .avatar-info img {
            width: 3.75rem;
            height: 3.75rem;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid rgba(255, 255, 255, 0.1);
            flex-shrink: 0;
        }

        .avatar-details {
            min-width: 0;
        }

        .avatar-name {
            font-size: 1.1rem;
            font-weight: bold;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            background: linear-gradient(45deg, #ff3366, #ff0000);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-shrink: 0;
        }

        .message-counter {
            background: rgba(31, 41, 55, 0.8);
            padding: 0.4rem 0.8rem;
            border-radius: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            font-size: 0.9rem;
            white-space: nowrap;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .message-counter:hover {
            background: rgba(31, 41, 55, 0.95);
            border-color: rgba(255, 255, 255, 0.2);
        }

        .call-icon {
            font-size: 1.2rem;
            color: #9ca3af;
            transition: all 0.3s ease;
            padding: 0.4rem;
            border-radius: 50%;
            background: rgba(31, 41, 55, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            flex-shrink: 0;
        }

        .message-form {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1rem;
            background: rgba(17, 24, 39, 0.95);
            backdrop-filter: blur(5px);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            z-index: 100;
            transform: translateZ(0); /* Força GPU acceleration */
        }

        .message-input-container {
            display: flex;
            gap: 0.5rem;
            max-width: 1200px;
            margin: 0 auto;
            align-items: center;
        }

        .media-buttons {
            display: flex;
            gap: 0.5rem;
            padding-bottom: 0;
            margin-bottom: 0.25rem;
        }

        .media-button {
            color: #9ca3af;
            font-size: 1.25rem;
            padding: 0.5rem;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            background: transparent;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 2.5rem;
            width: 2.5rem;
        }

        .media-button:hover {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
        }

        .message-input {
            flex: 1;
            min-height: 2.5rem;
            max-height: 120px;
            resize: none;
            overflow-y: auto;
            line-height: 1.5;
            padding: 0.5rem 1rem;
            background: rgba(31, 41, 55, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 1.5rem;
            color: white;
        }

        .send-button {
            padding: 0.5rem 1.5rem;
            background: #ef4444;
            color: white;
            border-radius: 1.5rem;
            transition: all 0.3s ease;
            flex-shrink: 0;
            height: 2.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .send-button:hover {
            background: #dc2626;
        }

        @media (max-width: 640px) {
            .chat-container {
                margin-top: 6rem;
                margin-bottom: 4rem;
                padding: 0.5rem;
                height: calc(100vh - 10rem);
            }

            .header-content {
                padding: 0 0.5rem;
            }

            .avatar-info img {
                width: 3.125rem;
                height: 3.125rem;
            }

            .avatar-name {
                font-size: 1rem;
            }

            .message-counter {
                font-size: 0.8rem;
                padding: 0.3rem 0.6rem;
            }

            .call-icon {
                font-size: 1rem;
                padding: 0.3rem;
            }

            .message-form {
                padding: 0.75rem;
                /* Ajuste para evitar que o teclado empurre o formulário */
                position: fixed;
                bottom: env(safe-area-inset-bottom, 0);
            }

            /* Ajuste para quando o teclado estiver aberto */
            .keyboard-open .chat-container {
                height: calc(100vh - 8rem - var(--keyboard-height, 0px));
            }

            .keyboard-open .message-form {
                position: fixed;
                bottom: env(safe-area-inset-bottom, 0);
            }

            .first-message {
                margin-top: 1.5rem !important; /* Espaçamento menor em mobile */
            }
        }

        /* Ajustes para iOS */
        @supports (-webkit-touch-callout: none) {
            .chat-container {
                height: -webkit-fill-available;
            }
        }

        .message {
            max-width: 70%;
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
        .message-input {
            min-height: 40px;
            max-height: 120px;
            resize: none;
            overflow-y: auto;
            line-height: 1.5;
            padding-top: 0.5rem;
            padding-bottom: 0.5rem;
        }
        .message-counter .count {
            font-weight: bold;
            color: #10b981;
        }
        .message-counter .count.low {
            color: #ef4444;
        }
        #limitModal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .modal-content {
            background: rgba(31, 41, 55, 0.95);
            padding: 2rem;
            border-radius: 1rem;
            text-align: center;
            max-width: 90%;
            width: 400px;
        }
        .countdown {
            font-size: 2rem;
            font-weight: bold;
            color: #ef4444;
            margin: 1rem 0;
        }
        .message-status {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 0.25rem;
            text-align: right;
        }
        
        /* Estilo para o pop-up inicial */
        .welcome-popup {
            position: fixed;
            bottom: 5rem;
            left: 50%;
            transform: translateX(-50%);
            background: rgba(31, 41, 55, 0.95);
            padding: 1rem;
            border-radius: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            z-index: 1000;
            animation: slideUp 0.5s ease-out;
            max-width: 90%;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        @keyframes slideUp {
            from { transform: translate(-50%, 100%); opacity: 0; }
            to { transform: translate(-50%, 0); opacity: 1; }
        }

        /* Estilo para o modal de ligação */
        .call-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .call-modal-content {
            background: rgba(31, 41, 55, 0.95);
            padding: 2rem;
            border-radius: 1rem;
            text-align: center;
            max-width: 90%;
            width: 400px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Estilo para o modal de imagem */
        .image-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .image-modal img {
            max-width: 95%;
            max-height: 95vh;
            object-fit: contain;
        }

        .typing-indicator {
            margin-top: 0.75rem;
            margin-bottom: 0.75rem;
            opacity: 0;
            transform: translateY(10px);
            animation: fadeInUp 0.3s ease forwards;
        }

        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Estilo para a primeira mensagem */
        .first-message {
            margin-top: 2rem !important; /* Espaçamento extra para a primeira mensagem */
        }

        /* Estilo para o modal de informações */
        .info-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .info-modal-content {
            background: rgba(31, 41, 55, 0.95);
            padding: 2rem;
            border-radius: 1rem;
            text-align: center;
            max-width: 90%;
            width: 400px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .info-modal-content h3 {
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: #ef4444;
        }

        .info-modal-content p {
            color: #e5e7eb;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .info-modal-content .highlight {
            color: #ef4444;
            font-weight: 500;
        }

        /* Estilo para o modal de mídia */
        .media-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.8);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }

        .media-modal-content {
            background: rgba(31, 41, 55, 0.95);
            padding: 2rem;
            border-radius: 1rem;
            text-align: center;
            max-width: 90%;
            width: 400px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .media-modal-content .icon {
            font-size: 2.5rem;
            color: #ef4444;
            margin-bottom: 1rem;
        }

        .media-modal-content h3 {
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 1rem;
            color: #ef4444;
        }

        .media-modal-content p {
            color: #e5e7eb;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .media-modal-content .highlight {
            color: #ef4444;
            font-weight: 500;
        }

        .highlight-green {
            color: #10b981 !important;
            font-weight: 500 !important;
        }

        .media-modal-content .highlight-green,
        .info-modal-content .highlight-green,
        .call-modal-content .highlight-green {
            color: #10b981 !important;
            font-weight: 500 !important;
        }
    </style>
</head>
<body>
    <div class="chat-header">
        <div class="header-content">
            <div class="avatar-info">
                <img src="<?php echo htmlspecialchars($avatarData['image_url']); ?>" 
                     alt="<?php echo htmlspecialchars($avatarData['name']); ?>" 
                     onclick="openImageModal(this.src)">
                <div class="avatar-details">
                    <h2 class="avatar-name"><?php echo htmlspecialchars($avatarData['name']); ?></h2>
                    <p class="text-sm text-gray-300"><?php echo htmlspecialchars($avatarData['age']); ?> anos</p>
                </div>
            </div>
            <div class="header-controls">
                <div class="message-counter">
                    Mensagens restantes: <span class="count"><?php echo $messageLimit; ?></span>
                </div>
                <i class="fas fa-phone call-icon" onclick="showCallModal()"></i>
            </div>
        </div>
    </div>

    <div class="chat-container">
        <div id="chatMessages" class="space-y-4">
            <!-- Mensagens serão adicionadas aqui via JavaScript -->
        </div>
        <div id="typingIndicator" class="hidden typing-indicator">
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

    <form id="messageForm" class="message-form">
        <div class="message-input-container">
            <div class="media-buttons">
                <button type="button" class="media-button" onclick="showMediaModal('image')">
                    <i class="fas fa-image"></i>
                </button>
                <button type="button" class="media-button" onclick="showMediaModal('audio')">
                    <i class="fas fa-microphone"></i>
                </button>
            </div>
            <textarea id="messageInput" 
                      class="message-input"
                      placeholder="Digite sua mensagem..."
                      rows="1"></textarea>
            <button type="submit" class="send-button">
                Enviar
            </button>
        </div>
    </form>

    <!-- Modal de limite de mensagens -->
    <div id="limitModal" class="fixed inset-0 hidden">
        <div class="modal-content">
            <h2 class="text-xl font-bold mb-4">Suas mensagens gratuitas acabaram</h2>
            <p class="text-gray-300 mb-4">Em instantes, você será redirecionado para conhecer os planos disponíveis para continuar conversando com sua companheira IA.</p>
            <div class="countdown">5</div>
        </div>
    </div>

    <!-- Modal de ligação -->
    <div id="callModal" class="call-modal">
        <div class="call-modal-content">
            <i class="fas fa-phone-slash text-4xl text-red-500 mb-4"></i>
            <h3 class="text-xl font-bold mb-2">Ligação indisponível</h3>
            <p class="text-gray-300 mb-4">Ligações de voz estão disponíveis apenas para assinantes dos planos pagos.</p>
            <p class="highlight-green mb-4">Complete suas 5 mensagens gratuitas com a avatar para conhecer nossos planos e desbloquear este recurso!</p>
            <button onclick="closeCallModal()" class="bg-red-500 text-white px-6 py-2 rounded-lg hover:bg-red-600 transition">
                Entendi
            </button>
        </div>
    </div>

    <!-- Modal de imagem -->
    <div id="imageModal" class="image-modal" onclick="closeImageModal()">
        <img id="modalImage" src="" alt="Imagem em tela cheia">
    </div>

    <!-- Pop-up de boas-vindas -->
    <div id="welcomePopup" class="welcome-popup">
        <p class="text-lg mb-2">Mande uma mensagem para sua musa...</p>
        <p class="text-sm text-gray-400">Ela está ansiosa para conversar com você!</p>
    </div>

    <!-- Modal de informações -->
    <div id="infoModal" class="info-modal">
        <div class="info-modal-content">
            <h3>Prévia do Chat</h3>
            <p>Esta é uma prévia do chat real com sua companheira IA. Você tem <span class="highlight">5 mensagens gratuitas</span> para experimentar a interação.</p>
            <p>Após usar todas as mensagens, você será redirecionado para conhecer nossos planos e continuar a conversa com recursos exclusivos como:</p>
            <ul class="text-left text-gray-300 mb-4">
                <li>• Chat ilimitado</li>
                <li>• Receber fotos, vídeos e áudios (+18, se preferir)</li>
                <li>• Chamadas de voz (sim, você pode falar em tempo real com ela)</li>
                <li>• Criar quantas avatares quiser</li>
            </ul>
            <p class="highlight-green mb-4">Complete suas 5 mensagens gratuitas com a avatar para desbloquear todos estes recursos e muito mais!</p>
            <button onclick="closeInfoModal()" class="bg-red-500 text-white px-6 py-2 rounded-lg hover:bg-red-600 transition">
                Entendi
            </button>
        </div>
    </div>

    <!-- Modal de mídia -->
    <div id="mediaModal" class="media-modal">
        <div class="media-modal-content">
            <div class="icon">
                <i class="fas fa-lock"></i>
            </div>
            <h3>Recurso Premium</h3>
            <p>O envio e recebimento de <span class="highlight" id="mediaType">mídia</span> é um recurso exclusivo para assinantes dos planos pagos.</p>
            <p>Assine agora para desbloquear:</p>
            <ul class="text-left text-gray-300 mb-4">
                <li>• Envio de fotos e vídeos</li>
                <li>• Mensagens de áudio</li>
                <li>• Recebimento de conteúdo exclusivo</li>
                <li>• E muito mais!</li>
            </ul>
            <p class="highlight-green mb-4">Complete suas 5 mensagens gratuitas com a avatar para conhecer nossos planos e desbloquear todos estes recursos!</p>
            <button onclick="closeMediaModal()" class="bg-red-500 text-white px-6 py-2 rounded-lg hover:bg-red-600 transition">
                Entendi
            </button>
        </div>
    </div>

    <script>
        const chatMessages = document.getElementById('chatMessages');
        const messageForm = document.getElementById('messageForm');
        const messageInput = document.getElementById('messageInput');
        const typingIndicator = document.getElementById('typingIndicator');
        const limitModal = document.getElementById('limitModal');
        let remainingMessages = <?php echo $messageLimit; ?>;
        let countdown = 5;

        // Função para rolar para a última mensagem
        function scrollToBottom() {
            const chatContainer = document.querySelector('.chat-container');
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }

        // Função para capitalizar a primeira letra de cada frase
        function capitalizeFirstLetter(string) {
            // Divide a string em frases usando múltiplos delimitadores
            const sentences = string.split(/([.!?]+\s+)/);
            let result = '';
            
            for (let i = 0; i < sentences.length; i++) {
                if (sentences[i].trim()) {
                    // Se for um delimitador (pontuação + espaço), mantém como está
                    if (/^[.!?]+\s+$/.test(sentences[i])) {
                        result += sentences[i];
                    } else {
                        // Capitaliza a primeira letra da frase
                        result += sentences[i].charAt(0).toUpperCase() + sentences[i].slice(1);
                    }
                }
            }
            
            return result;
        }

        // Função para adicionar uma mensagem ao chat
        function addMessage(message, isUser) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message p-4 ${isUser ? 'user-message' : 'avatar-message'}`;
            
            const messageContent = document.createElement('p');
            messageContent.textContent = isUser ? message : capitalizeFirstLetter(message);
            messageDiv.appendChild(messageContent);
            
            if (isUser) {
                const statusSpan = document.createElement('span');
                statusSpan.className = 'message-status';
                statusSpan.textContent = 'Enviado';
                messageDiv.appendChild(statusSpan);
            }
            
            chatMessages.appendChild(messageDiv);
            
            // Se for a primeira mensagem, força o scroll para o topo
            if (chatMessages.children.length === 1) {
                const chatContainer = document.querySelector('.chat-container');
                chatContainer.scrollTop = 0;
            } else {
                // Para mensagens subsequentes, rola para o final
                const chatContainer = document.querySelector('.chat-container');
                chatContainer.scrollTop = chatContainer.scrollHeight;
            }
            
            return messageDiv;
        }

        // Função para atualizar o status de uma mensagem
        function updateMessageStatus(messageDiv, status) {
            const statusSpan = messageDiv.querySelector('.message-status');
            if (statusSpan) {
                statusSpan.textContent = status;
            }
        }

        // Função para gerar um número aleatório entre min e max
        function getRandomTime(min, max) {
            return Math.floor(Math.random() * (max - min + 1) + min) * 1000;
        }

        // Função para mostrar/ocultar o indicador de digitação
        function showTypingIndicator(show) {
            const indicator = document.getElementById('typingIndicator');
            if (show) {
                indicator.classList.remove('hidden');
                const chatContainer = document.querySelector('.chat-container');
                chatContainer.scrollTop = chatContainer.scrollHeight;
            } else {
                indicator.classList.add('hidden');
            }
        }

        // Função para atualizar o contador de mensagens
        function updateMessageCounter(count) {
            const counter = document.querySelector('.count');
            counter.textContent = count;
            counter.className = `count ${count <= 1 ? 'low' : ''}`;
        }

        // Função para mostrar o modal de limite
        function showLimitModal() {
            limitModal.style.display = 'flex';
            const countdownElement = document.querySelector('.countdown');
            
            const timer = setInterval(() => {
                countdown--;
                countdownElement.textContent = countdown;
                
                if (countdown <= 0) {
                    clearInterval(timer);
                    window.location.href = `success.php?id=<?php echo $avatarId; ?>`;
                }
            }, 1000);
        }

        // Função para enviar mensagem
        async function sendMessage() {
            const message = messageInput.value.trim();
            if (!message || remainingMessages <= 0) return;

            try {
                messageInput.value = '';
                messageInput.style.height = 'auto';
                
                // Adiciona mensagem do usuário
                const userMessageDiv = addMessage(message, true);
                
                // Atualiza contador
                remainingMessages--;
                updateMessageCounter(remainingMessages);
                
                // Simula delay de entrega
                setTimeout(() => {
                    updateMessageStatus(userMessageDiv, 'Entregue');
                    
                    // Simula delay de visualização
                    const seenDelay = getRandomTime(2, 7);
                    setTimeout(() => {
                        updateMessageStatus(userMessageDiv, 'Visto');
                        showTypingIndicator(true);
                        
                        // Simula resposta da IA
                        fetch('api/send_message.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({
                                avatar_id: <?php echo json_encode($avatarData['id'] ?? null); ?>,
                                message: message
                            })
                        })
                        .then(response => response.json())
                        .then(data => {
                            // Simula tempo de digitação
                            const typingTime = getRandomTime(2, 5);
                            setTimeout(() => {
                                showTypingIndicator(false);
                                addMessage(data.response || "Olá! Como posso ajudar?", false);
                                
                                // Se acabaram as mensagens, mostra o modal
                                if (remainingMessages <= 0) {
                                    showLimitModal();
                                }
                            }, typingTime);
                        })
                        .catch(error => {
                            console.error('Erro:', error);
                            showTypingIndicator(false);
                            addMessage("Desculpe, ocorreu um erro. Tente novamente.", false);
                        });
                    }, seenDelay);
                }, 500);
                
            } catch (error) {
                console.error('Erro:', error);
                showTypingIndicator(false);
                addMessage("Desculpe, ocorreu um erro. Tente novamente.", false);
            }
        }

        // Event listeners
        messageForm.addEventListener('submit', (e) => {
            e.preventDefault();
            sendMessage();
        });

        // Auto-resize da textarea
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
            
            if (this.scrollHeight > 120) {
                this.style.height = '120px';
                this.style.overflowY = 'auto';
            } else {
                this.style.overflowY = 'hidden';
            }
        });

        // Envia mensagem com Enter (sem Shift)
        messageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                if (this.value.trim()) {
                    sendMessage();
                }
            }
        });

        // Funções para o modal de ligação
        function showCallModal() {
            document.getElementById('callModal').style.display = 'flex';
        }

        function closeCallModal() {
            document.getElementById('callModal').style.display = 'none';
        }

        // Funções para o modal de imagem
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

        // Função para rolar para a caixa de texto
        function scrollToMessageInput() {
            const messageForm = document.querySelector('.message-form');
            if (messageForm) {
                messageForm.scrollIntoView({ behavior: 'smooth', block: 'end' });
            }
        }

        // Função para ajustar o layout quando o teclado estiver aberto
        function adjustForKeyboard() {
            const isMobile = window.innerWidth <= 640;
            if (!isMobile) return;

            const vh = window.innerHeight * 0.01;
            document.documentElement.style.setProperty('--vh', `${vh}px`);

            // Detecta se o teclado está aberto
            const isKeyboardOpen = window.innerHeight < window.outerHeight;
            document.body.classList.toggle('keyboard-open', isKeyboardOpen);

            if (isKeyboardOpen) {
                // Calcula a altura do teclado
                const keyboardHeight = window.outerHeight - window.innerHeight;
                document.documentElement.style.setProperty('--keyboard-height', `${keyboardHeight}px`);
            } else {
                document.documentElement.style.setProperty('--keyboard-height', '0px');
            }

            // Ajusta o scroll para a última mensagem
            scrollToBottom();
        }

        // Event listeners para ajustes do teclado
        window.addEventListener('resize', adjustForKeyboard);
        window.addEventListener('orientationchange', adjustForKeyboard);
        window.addEventListener('focusin', (e) => {
            if (e.target.tagName === 'TEXTAREA') {
                adjustForKeyboard();
            }
        });
        window.addEventListener('focusout', (e) => {
            if (e.target.tagName === 'TEXTAREA') {
                adjustForKeyboard();
            }
        });

        // Ajuste inicial
        window.addEventListener('load', () => {
            adjustForKeyboard();
            showInfoModal(); // Mostra o modal de informações primeiro
            scrollToMessageInput();
        });

        // Função para o modal de informações
        function showInfoModal() {
            document.getElementById('infoModal').style.display = 'flex';
        }

        function closeInfoModal() {
            document.getElementById('infoModal').style.display = 'none';
            // Mostra o pop-up de boas-vindas após fechar o modal
            showWelcomePopup();
        }

        // Função para o pop-up de boas-vindas
        function showWelcomePopup() {
            const popup = document.getElementById('welcomePopup');
            popup.style.display = 'block';
            setTimeout(() => {
                popup.style.opacity = '0';
                setTimeout(() => {
                    popup.style.display = 'none';
                }, 500);
            }, 5000);
        }

        // Remove o evento de clique do contador de mensagens já que agora ele abre automaticamente
        document.addEventListener('DOMContentLoaded', function() {
            const messageCounter = document.querySelector('.message-counter');
            if (messageCounter) {
                messageCounter.removeEventListener('click', showInfoModal);
            }
        });

        // Funções para o modal de mídia
        function showMediaModal(type) {
            const modal = document.getElementById('mediaModal');
            const mediaType = document.getElementById('mediaType');
            
            // Atualiza o texto baseado no tipo de mídia
            switch(type) {
                case 'image':
                    mediaType.textContent = 'fotos e vídeos';
                    break;
                case 'audio':
                    mediaType.textContent = 'mensagens de áudio';
                    break;
                default:
                    mediaType.textContent = 'mídia';
            }
            
            modal.style.display = 'flex';
        }

        function closeMediaModal() {
            document.getElementById('mediaModal').style.display = 'none';
        }

        // Adiciona evento de scroll para detectar quando o usuário está próximo do final
        document.querySelector('.chat-container').addEventListener('scroll', function() {
            const chatContainer = this;
            const isNearBottom = chatContainer.scrollHeight - chatContainer.scrollTop - chatContainer.clientHeight < 100;
            
            // Adiciona ou remove classe para controlar o comportamento de auto-scroll
            chatContainer.classList.toggle('auto-scroll', isNearBottom);
        });
    </script>
</body>
</html> 