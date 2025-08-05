$personalityPhrases = [
    "Doce e carinhosa" => [
        "Oi! Que bom que você está aqui... 💕",
        "Estava tão animada para conhecer você melhor...",
        "Quero compartilhar algumas fotos especiais que tirei só para você... 📸",
        "Talvez até enviar algumas mensagens de áudio doces para você curtir... 🎵"
    ],
    "Dominadora e ousada" => [
        "Olá, meu querido submisso... 😈",
        "Pronto para seguir minhas ordens?",
        "Tenho alguns vídeos intensos para te mostrar... 🎥",
        "E posso te enviar áudio com minha voz dominadora... 🔥"
    ],
    "Misteriosa e sedutora" => [
        "Hmm... você finalmente apareceu... 🌙",
        "Tenho tantos segredos para compartilhar com você...",
        "Quer ver algumas fotos que ninguém mais viu? 📸",
        "Posso te enviar algumas mensagens de áudio provocantes... 🎵"
    ],
    "Brincalhona e provocante" => [
        "Oi! Você chegou bem na hora de se divertir! 🎮",
        "Que tal um joguinho divertido, só nós dois?",
        "Tenho alguns vídeos safados para te mostrar... 🎥",
        "Vou te enviar mensagens de áudio que são tão divertidas de ouvir... 😋"
    ],
    "Dominadora e sarcástica" => [
        "Olha quem finalmente decidiu aparecer... 👑",
        "Espero que esteja pronto para me servir adequadamente...",
        "Tenho fotos que vão te fazer se ajoelhar... 📸",
        "Vou te enviar mensagens de áudio que vão te fazer implorar por mais... 😈"
    ],
    "Doce e submissa" => [
        "Oi! Estou tão feliz que você está aqui... 🌸",
        "Estou aqui para satisfazer todos os seus desejos...",
        "Quer ver fotos minhas em poses especiais? 📸",
        "Posso te enviar mensagens de áudio doces e submissas... 💝"
    ]
];
<title>Chat com <?php echo htmlspecialchars($avatar["name"]); ?> - DesireChat</title>
<h1 class="text-4xl md:text-5xl font-bold mb-4 bg-gradient-to-r from-red-500 to-pink-500 bg-clip-text text-transparent">
    Chat com <?php echo htmlspecialchars($avatar["name"]); ?> 💕
</h1>
<p class="text-xl text-gray-300">
    Sua musa dos sonhos está pronta para conversar com você...
</p>
<h2 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($avatar["name"]); ?></h2>
<p class="text-gray-300"><?php echo htmlspecialchars($avatar["age"]); ?> anos - <?php echo htmlspecialchars($avatar["occupation"]); ?></p>
<h3 class="text-xl font-bold mb-4">Personalidade Única</h3>
<div class="space-y-3">
    <p><span class="font-semibold">Estilo:</span> <?php echo htmlspecialchars($avatar["personality"]); ?></p>
    <p><span class="font-semibold">Religião:</span> <?php echo htmlspecialchars($avatar["religion"]); ?></p>
    <?php if ($avatar["hobbies"]): ?>
        <p><span class="font-semibold">Hobbies:</span> <?php echo htmlspecialchars($avatar["hobbies"]); ?></p>
    <?php endif; ?>
    <p><span class="font-semibold">Nível de Timidez:</span> <?php echo htmlspecialchars($avatar["shyness_level"]); ?>%</p>
    <p><span class="font-semibold">Nível de Vulgaridade:</span> <?php echo htmlspecialchars($avatar["vulgarity_level"]); ?></p>
</div>
<div class="chat-container">
    <div class="chat-messages" id="chat-messages">
        <div class="message avatar-message">
            <div class="message-content">
                <?php echo $phrases[array_rand($phrases)]; ?>
            </div>
        </div>
    </div>
    <div class="chat-input">
        <textarea id="message-input" placeholder="Digite sua mensagem..." rows="1"></textarea>
        <button id="send-button" class="send-button">
            <i class="fas fa-paper-plane"></i>
        </button>
    </div>
</div>
<div class="text-center bg-gradient-to-r from-red-900 to-pink-900 rounded-lg p-8 mb-12">
    <h2 class="text-2xl md:text-3xl font-bold mb-4">Conteúdo Exclusivo</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div>
            <i class="fas fa-camera text-4xl text-red-500 mb-4"></i>
            <h3 class="text-xl font-bold mb-2">Fotos Exclusivas</h3>
            <p class="text-gray-300">Receba fotos sensuais e provocantes do seu avatar</p>
        </div>
        <div>
            <i class="fas fa-video text-4xl text-red-500 mb-4"></i>
            <h3 class="text-xl font-bold mb-2">Vídeos Íntimos</h3>
            <p class="text-gray-300">Vídeos exclusivos feitos só para você</p>
        </div>
        <div>
            <i class="fas fa-microphone text-4xl text-red-500 mb-4"></i>
            <h3 class="text-xl font-bold mb-2">Áudio Sensual</h3>
            <p class="text-gray-300">Ouça a voz do seu avatar em momentos íntimos</p>
        </div>
    </div>
</div>
<div class="text-center bg-gray-800 rounded-lg p-8 mb-12">
    <h2 class="text-2xl md:text-3xl font-bold mb-4">Satisfação Garantida</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div>
            <i class="fas fa-shield-alt text-4xl text-red-500 mb-4"></i>
            <h3 class="text-xl font-bold mb-2">100% Seguro</h3>
            <p class="text-gray-300">Seus dados estão protegidos e suas conversas são privadas</p>
        </div>
        <div>
            <i class="fas fa-sync-alt text-4xl text-red-500 mb-4"></i>
            <h3 class="text-xl font-bold mb-2">Garantia de 7 Dias</h3>
            <p class="text-gray-300">Se não estiver satisfeito, devolvemos seu dinheiro</p>
        </div>
        <div>
            <i class="fas fa-headset text-4xl text-red-500 mb-4"></i>
            <h3 class="text-xl font-bold mb-2">Suporte 24/7</h3>
            <p class="text-gray-300">Nossa equipe está sempre pronta para te ajudar</p>
        </div>
    </div>
</div>
<div class="text-center bg-gray-800 rounded-lg p-8 mb-12">
    <h2 class="text-2xl md:text-3xl font-bold mb-4">Por que Escolher o DesireChat?</h2>
    <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
        <div>
            <i class="fas fa-lock text-4xl text-red-500 mb-4"></i>
            <h3 class="text-xl font-bold mb-2">100% Privado</h3>
            <p class="text-gray-300">Suas conversas são completamente confidenciais</p>
        </div>
        <div>
            <i class="fas fa-bolt text-4xl text-red-500 mb-4"></i>
            <h3 class="text-xl font-bold mb-2">Respostas Instantâneas</h3>
            <p class="text-gray-300">Seu avatar responde em tempo real</p>
        </div>
        <div>
            <i class="fas fa-magic text-4xl text-red-500 mb-4"></i>
            <h3 class="text-xl font-bold mb-2">Personalidade Única</h3>
            <p class="text-gray-300">Cada avatar tem seu próprio estilo distinto</p>
        </div>
        <div>
            <i class="fas fa-heart text-4xl text-red-500 mb-4"></i>
            <h3 class="text-xl font-bold mb-2">Experiência Realista</h3>
            <p class="text-gray-300">Sinta como se estivesse em uma conversa real</p>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const messageInput = document.getElementById('message-input');
    const sendButton = document.getElementById('send-button');
    const chatMessages = document.getElementById('chat-messages');

    function adjustTextareaHeight() {
        messageInput.style.height = 'auto';
        messageInput.style.height = (messageInput.scrollHeight) + 'px';
    }

    messageInput.addEventListener('input', adjustTextareaHeight);

    function addMessage(message, isUser = false) {
        const messageDiv = document.createElement('div');
        messageDiv.className = `message ${isUser ? 'user-message' : 'avatar-message'}`;
        
        const contentDiv = document.createElement('div');
        contentDiv.className = 'message-content';
        contentDiv.textContent = message;
        
        messageDiv.appendChild(contentDiv);
        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function sendMessage() {
        const message = messageInput.value.trim();
        if (message) {
            addMessage(message, true);
            messageInput.value = '';
            adjustTextareaHeight();

            // Simular resposta do avatar
            setTimeout(() => {
                const responses = [
                    "Hmm, me conte mais sobre isso... 😊",
                    "Isso é muito interessante! 💕",
                    "Quero saber mais sobre você... 💭",
                    "Você é tão fascinante... 🌟",
                    "Me conte mais detalhes... 👀",
                    "Isso me deixa muito curiosa... 🤔",
                    "Que interessante! Me fale mais... 💫",
                    "Você me surpreende cada vez mais... ✨"
                ];
                const randomResponse = responses[Math.floor(Math.random() * responses.length)];
                addMessage(randomResponse);
            }, 1000 + Math.random() * 2000);
        }
    }

    sendButton.addEventListener('click', sendMessage);
    messageInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
});
</script> 