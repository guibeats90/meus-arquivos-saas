<?php
require_once 'config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Busca apenas as conversas ativas do usuário com informações do avatar e última mensagem
$stmt = $pdo->prepare("
    SELECT 
        c.id as conversation_id,
        a.id as avatar_id,
        a.name as avatar_name,
        a.image_url as avatar_image,
        a.personality as avatar_personality,
        m.content as last_message,
        m.created_at as last_message_date,
        m.sender_type as last_message_sender
    FROM conversations c
    JOIN avatars a ON c.avatar_id = a.id
    LEFT JOIN (
        SELECT conversation_id, content, created_at, sender_type
        FROM messages
        WHERE (conversation_id, created_at) IN (
            SELECT conversation_id, MAX(created_at)
            FROM messages
            GROUP BY conversation_id
        )
    ) m ON c.id = m.conversation_id
    WHERE c.user_id = ?
    AND EXISTS (
        SELECT 1 FROM messages m2 
        WHERE m2.conversation_id = c.id
    )
    ORDER BY m.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$conversations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DesireChat - Meus Chats</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d1a1a 100%);
            color: #fff;
        }
        .chat-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            transition: transform 0.2s;
        }
        .chat-card:hover {
            transform: translateY(-2px);
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

    <div class="container mx-auto px-4 py-8 mt-20">
        <h1 class="text-3xl font-bold mb-8">Meus Chats</h1>
        
        <?php if (empty($conversations)): ?>
            <div class="text-center py-12">
                <p class="text-gray-300 text-lg mb-4">Você ainda não iniciou nenhuma conversa.</p>
                <a href="index.php" class="bg-red-500 text-white px-6 py-2 rounded hover:bg-red-600 transition">
                    Conhecer Avatares
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($conversations as $chat): ?>
                    <a href="chat.php?avatar_id=<?php echo $chat['avatar_id']; ?>" class="chat-card rounded-lg overflow-hidden">
                        <div class="p-4">
                            <div class="flex items-center space-x-4 mb-4">
                                <img src="<?php echo htmlspecialchars($chat['avatar_image']); ?>" 
                                     alt="<?php echo htmlspecialchars($chat['avatar_name']); ?>" 
                                     class="w-12 h-12 rounded-full object-cover">
                                <div>
                                    <h3 class="text-xl font-bold"><?php echo htmlspecialchars($chat['avatar_name']); ?></h3>
                                    <p class="text-gray-300 text-sm"><?php echo htmlspecialchars($chat['avatar_personality']); ?></p>
                                </div>
                            </div>
                            
                            <?php if ($chat['last_message']): ?>
                                <div class="border-t border-gray-700 pt-4">
                                    <p class="text-gray-300 text-sm mb-1">
                                        <?php echo $chat['last_message_sender'] === 'user' ? 'Você' : htmlspecialchars($chat['avatar_name']); ?>:
                                    </p>
                                    <p class="text-gray-400 text-sm truncate">
                                        <?php echo htmlspecialchars($chat['last_message']); ?>
                                    </p>
                                    <p class="text-gray-500 text-xs mt-2">
                                        <?php echo date('d/m/Y H:i', strtotime($chat['last_message_date'])); ?>
                                    </p>
                                </div>
                            <?php else: ?>
                                <div class="border-t border-gray-700 pt-4">
                                    <p class="text-gray-400 text-sm">Nenhuma mensagem ainda</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
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