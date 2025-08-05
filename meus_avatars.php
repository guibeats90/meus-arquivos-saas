<?php
require_once 'config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DesireChat - Meus Avatares</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d1a1a 100%);
            color: #fff;
        }
        .avatar-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
            width: 100%;
            max-width: 336px;
            height: auto;
            margin: 0 auto;
        }
        @media (min-width: 768px) {
            .avatar-card {
                height: 520px;
            }
        }
        .avatar-card:hover {
            transform: translateY(-5px);
        }
        .avatar-image-container {
            position: relative;
            width: 100%;
            padding-top: 150%; /* Proporção 2:3 */
            overflow: hidden;
        }
        @media (min-width: 768px) {
            .avatar-image-container {
                height: 394px;
            }
        }
        .avatar-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            background-color: rgba(0, 0, 0, 0.1);
        }
        .expand-icon {
            position: absolute;
            top: 10px;
            right: 10px;
            background-color: rgba(0, 0, 0, 0.5);
            border-radius: 50%;
            padding: 5px;
            width: 25px;
            height: 25px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 2;
            opacity: 0.7;
            transition: opacity 0.3s ease;
        }
        .avatar-image-container:hover .expand-icon {
            opacity: 1;
        }
        .avatar-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 0.5rem;
            height: auto;
            min-height: 120px;
            justify-content: space-between;
        }
        @media (min-width: 768px) {
            .avatar-content {
                height: auto;
                min-height: 126px;
            }
            .avatar-personality {
                -webkit-line-clamp: 1;
                min-height: 1.2em;
                margin-bottom: 0.25rem;
            }
            .avatar-button {
                margin-top: 0.5rem;
                padding: 0.5rem;
            }
        }
        .avatar-name {
            font-size: 1rem;
            font-weight: bold;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            color: #fff;
            margin-bottom: 0.25rem;
        }
        .avatar-age {
            font-size: 0.8rem;
            margin-bottom: 0.25rem;
            color: #d1d5db;
        }
        .avatar-personality {
            font-size: 0.8rem;
            color: #d1d5db;
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
            line-height: 1.2;
            margin-bottom: 0.5rem;
            white-space: nowrap;
        }
        .avatar-button {
            padding: 0.4rem;
            font-size: 0.9rem;
            margin-top: auto;
        }
        .modal {
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
            z-index: 2000;
        }
        .image-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            z-index: 2000;
            cursor: pointer;
        }
        .image-modal.active {
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .image-modal img {
            max-height: 90vh;
            width: auto;
            object-fit: contain;
            position: relative;
            transform: none;
            top: auto;
            left: auto;
        }
        .image-modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            z-index: 2001;
            background: rgba(0, 0, 0, 0.5);
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .image-modal-close:hover {
            background: rgba(255, 51, 102, 0.5);
            transform: scale(1.1);
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
    </style>
</head>
<body class="min-h-screen">
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

    <main class="container mx-auto px-4 py-8 mt-20">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-3xl font-bold">Meus Avatares</h2>
            <a href="quiz/index.php" class="bg-gradient-to-r from-red-500 to-pink-500 text-white px-6 py-2 rounded-full hover:from-red-600 hover:to-pink-600 transition transform hover:scale-105 shadow-lg">
                Criar Novo Avatar
            </a>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 px-2 justify-items-center">
            <?php
            $stmt = $pdo->prepare("SELECT * FROM avatars WHERE is_active = 1 AND is_custom = 1 AND created_by = ?");
            $stmt->execute([$_SESSION['user_id']]);
            while($avatar = $stmt->fetch()):
            ?>
            <div class="avatar-card rounded-lg overflow-hidden shadow-lg">
                <div class="avatar-image-container cursor-pointer" onclick="showFullImage('<?php echo htmlspecialchars($avatar['image_url']); ?>')">
                    <div class="expand-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="white" class="w-4 h-4">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </div>
                    <img src="<?php echo htmlspecialchars($avatar['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($avatar['name']); ?>" 
                         class="avatar-image">
                </div>
                <div class="avatar-content">
                    <div class="flex flex-col">
                        <h3 class="avatar-name text-lg font-bold text-white mb-1"><?php echo htmlspecialchars($avatar['name']); ?></h3>
                        <p class="avatar-age text-gray-300"><?php echo htmlspecialchars($avatar['age']); ?> anos</p>
                        <p class="avatar-personality text-gray-300"><?php echo htmlspecialchars($avatar['personality']); ?></p>
                    </div>
                    <button onclick="showAvatarDetails(<?php echo htmlspecialchars(json_encode($avatar)); ?>)" 
                            class="avatar-button w-full bg-gradient-to-r from-red-500 to-pink-500 text-white py-2 rounded-full hover:from-red-600 hover:to-pink-600 transition transform hover:scale-105 mt-2">
                        Conhecer
                    </button>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </main>

    <!-- Modal de Detalhes do Avatar -->
    <div id="avatarModal" class="fixed inset-0 hidden modal">
        <div class="flex items-center justify-center min-h-screen p-4 pt-20">
            <div class="bg-gray-800 rounded-lg max-w-4xl w-full p-6 relative">
                <button onclick="closeModal()" class="absolute top-4 right-4 text-white hover:text-red-500 text-2xl">✕</button>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="relative aspect-[2/3] rounded-lg overflow-hidden">
                        <img id="modalImage" src="" alt="" class="absolute inset-0 w-full h-full object-cover">
                    </div>
                    <div class="flex flex-col space-y-4">
                        <h2 id="modalName" class="text-2xl font-bold mb-2"></h2>
                        <div class="space-y-3 text-gray-300">
                            <p id="modalAge" class="flex items-center">
                                <i class="fas fa-birthday-cake mr-2 text-red-500"></i>
                                <strong class="mr-2">Idade:</strong> <span></span>
                            </p>
                            <p id="modalPersonality" class="flex items-center">
                                <i class="fas fa-heart mr-2 text-red-500"></i>
                                <strong class="mr-2">Personalidade:</strong> <span></span>
                            </p>
                            <p id="modalDescription" class="flex items-center">
                                <i class="fas fa-star mr-2 text-red-500"></i>
                                <strong class="mr-2">Tipo:</strong> <span class="capitalize"></span>
                            </p>
                            <p id="modalReligion" class="flex items-center">
                                <i class="fas fa-pray mr-2 text-red-500"></i>
                                <strong class="mr-2">Crença:</strong> <span></span>
                            </p>
                            <p id="modalOccupation" class="flex items-center">
                                <i class="fas fa-briefcase mr-2 text-red-500"></i>
                                <strong class="mr-2">Profissão:</strong> <span></span>
                            </p>
                            <p id="modalRelationship" class="flex items-center">
                                <i class="fas fa-user-friends mr-2 text-red-500"></i>
                                <strong class="mr-2">Relação com você:</strong> <span></span>
                            </p>
                            <p id="modalHobbies" class="flex items-start">
                                <i class="fas fa-smile mr-2 text-red-500 mt-1"></i>
                                <span class="flex-1">
                                    <strong class="mr-2">Hobbies:</strong>
                                    <span class="block mt-1 text-sm"></span>
                                </span>
                            </p>
                            <p id="modalConversationStyle" class="flex items-center">
                                <i class="fas fa-comments mr-2 text-red-500"></i>
                                <strong class="mr-2">Estilo de conversa:</strong> <span></span>
                            </p>
                            <div class="border-t border-gray-700 my-4"></div>
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div id="modalVulgarityLevel" class="bg-gray-700 p-3 rounded-lg">
                                    <div class="flex items-center mb-2">
                                        <i class="fas fa-fire mr-2 text-red-500"></i>
                                        <strong>Nível de Vulgaridade</strong>
                                    </div>
                                    <span class="block text-center font-medium"></span>
                                </div>
                                <div id="modalShynessLevel" class="bg-gray-700 p-3 rounded-lg">
                                    <div class="flex items-center mb-2">
                                        <i class="fas fa-mask mr-2 text-red-500"></i>
                                        <strong>Nível de Timidez</strong>
                                    </div>
                                    <span class="block text-center font-medium"></span>
                                </div>
                            </div>
                        </div>
                        <button id="startChatBtn" class="mt-auto w-full bg-gradient-to-r from-red-500 to-pink-500 text-white py-3 rounded-full hover:from-red-600 hover:to-pink-600 transition transform hover:scale-105 font-bold flex items-center justify-center">
                            <i class="fas fa-comment-dots mr-2"></i>
                            Iniciar Chat
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Visualização de Imagem -->
    <div id="imageModal" class="image-modal">
        <span class="image-modal-close" onclick="closeFullImage()">×</span>
        <div class="relative w-full h-full flex items-center justify-center">
            <img id="fullImage" src="" alt="Imagem em tamanho completo" class="max-h-[90vh] w-auto object-contain">
        </div>
    </div>

    <script>
        function showAvatarDetails(avatar) {
            document.getElementById('modalName').textContent = avatar.name;
            document.getElementById('modalImage').src = avatar.image_url;
            document.getElementById('modalAge').querySelector('span').textContent = `${avatar.age} anos`;
            document.getElementById('modalPersonality').querySelector('span').textContent = avatar.personality || 'Não informada';
            document.getElementById('modalDescription').querySelector('span').textContent = avatar.description || 'Não informado';
            document.getElementById('modalReligion').querySelector('span').textContent = avatar.religion || 'Não informada';
            document.getElementById('modalOccupation').querySelector('span').textContent = avatar.occupation || 'Não informada';
            document.getElementById('modalRelationship').querySelector('span').textContent = avatar.relationship_status || 'Não informado';
            document.getElementById('modalHobbies').querySelector('span:last-child').textContent = 
                avatar.hobbies ? avatar.hobbies : 'Ainda não compartilhou seus hobbies';
            document.getElementById('modalConversationStyle').querySelector('span').textContent = 
                avatar.conversation_style || 'Não informado';
            
            // Configura os níveis com descrições mais detalhadas
            const vulgarityDescriptions = {
                'muito_alto': 'Extremamente Ousada',
                'alto': 'Bastante Ousada',
                'equilibrado': 'Moderada',
                'baixo': 'Recatada',
                'muito_baixo': 'Muito Recatada'
            };
            
            document.getElementById('modalVulgarityLevel').querySelector('span').textContent = 
                vulgarityDescriptions[avatar.vulgarity_level] || 'Moderada';
            
            const shynessValue = avatar.shyness_level;
            let shynessDescription;
            if (shynessValue > 75) {
                shynessDescription = 'Extremamente Tímida';
            } else if (shynessValue > 50) {
                shynessDescription = 'Tímida';
            } else if (shynessValue > 25) {
                shynessDescription = 'Moderadamente Sociável';
            } else {
                shynessDescription = 'Muito Sociável';
            }
            
            document.getElementById('modalShynessLevel').querySelector('span').textContent = shynessDescription;
            
            const startChatBtn = document.getElementById('startChatBtn');
            startChatBtn.onclick = () => window.location.href = `chat.php?avatar_id=${avatar.id}`;
            
            document.getElementById('avatarModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('avatarModal').classList.add('hidden');
        }

        function showFullImage(imageUrl) {
            const modal = document.getElementById('imageModal');
            const fullImage = document.getElementById('fullImage');
            fullImage.src = imageUrl;
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }

        function closeFullImage() {
            const modal = document.getElementById('imageModal');
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }

        // Fecha o modal se pressionar ESC
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeFullImage();
                closeModal();
            }
        });

        // Fecha o modal de imagem ao clicar fora dela
        document.getElementById('imageModal').addEventListener('click', function(event) {
            if (event.target === this) {
                closeFullImage();
            }
        });
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
    </script>
</body>
</html> 