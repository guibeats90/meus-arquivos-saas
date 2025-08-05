<?php
session_start();
require_once '../config/database.php';

// Verifica se o ID do avatar foi fornecido
if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$avatarId = $_GET['id'];

// Busca os dados do avatar
$stmt = $pdo->prepare("SELECT * FROM avatars WHERE id = ?");
$stmt->execute([$avatarId]);
$avatar = $stmt->fetch();

if (!$avatar) {
    header('Location: index.php');
    exit;
}

// Limpa os dados da sessão do quiz
unset($_SESSION['quiz_data']);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sua Crush IA está Pronta! - DesireChat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d1a1a 100%);
            min-height: 100vh;
        }
        .avatar-image {
            transition: all 0.3s ease;
        }
        .avatar-image:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body class="text-white">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="text-center">
            <h1 class="text-4xl font-bold mb-8">Sua Musa dos Sonhos está Pronta! 😍</h1>
            
            <div class="bg-gray-800 rounded-lg p-8 mb-8">
                <div class="flex flex-col md:flex-row items-center gap-8">
                    <div class="w-48 h-48 rounded-full overflow-hidden">
                        <img src="images/appearance/<?php echo htmlspecialchars($avatar['type']); ?>/<?php echo htmlspecialchars($avatar['appearance']); ?>.png" 
                             alt="<?php echo htmlspecialchars($avatar['name']); ?>" 
                             class="w-full h-full object-cover rounded-lg hover:opacity-90 transition-opacity cursor-pointer avatar-image"
                             onclick="openImageModal(this.src)">
                    </div>
                    
                    <div class="text-left">
                        <h2 class="text-2xl font-bold mb-4"><?php echo htmlspecialchars($avatar['name']); ?></h2>
                        <div class="space-y-2">
                            <p><span class="font-semibold">Idade:</span> <?php echo htmlspecialchars($avatar['age']); ?> anos - Uma deusa em seu auge</p>
                            <p><span class="font-semibold">Personalidade:</span> <?php echo htmlspecialchars($avatar['personality']); ?> - Pronta para te conquistar</p>
                            <p><span class="font-semibold">Ocupação:</span> <?php echo htmlspecialchars($avatar['occupation']); ?> - Uma profissional que sabe o que quer</p>
                            <p><span class="font-semibold">Religião:</span> <?php echo htmlspecialchars($avatar['religion']); ?> - Com um lado pecaminoso escondido</p>
                            <?php if ($avatar['hobbies']): ?>
                                <p><span class="font-semibold">Hobbies:</span> <?php echo htmlspecialchars($avatar['hobbies']); ?> - Pronta para te mostrar seus segredos</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-4">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <p class="text-xl mb-8"><?php echo htmlspecialchars($avatar['name']); ?> está ansiosa para te conhecer... Mas primeiro, vamos criar sua conta.</p>
                    <div class="flex flex-col md:flex-row gap-4 justify-center">
                        <a href="../register.php?avatar_id=<?php echo $avatarId; ?>" class="px-6 py-3 rounded-lg bg-red-500 hover:bg-red-600 transition duration-300">
                            Criar Conta e Conhecer <?php echo htmlspecialchars($avatar['name']); ?>
                        </a>
                        <a href="../login.php?avatar_id=<?php echo $avatarId; ?>" class="px-6 py-3 rounded-lg bg-gray-700 hover:bg-gray-600 transition duration-300">
                            Já tenho uma conta
                        </a>
                    </div>
                <?php else: ?>
                    <p class="text-xl mb-8"><?php echo htmlspecialchars($avatar['name']); ?> está esperando ansiosamente para começar uma conversa picante com você...</p>
                    <a href="../chat.php?avatar_id=<?php echo $avatarId; ?>" 
                       class="inline-block px-8 py-4 rounded-lg bg-red-500 hover:bg-red-600 transition duration-300 text-xl font-semibold">
                        Começar uma Conversa Sedutora
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal para visualização da imagem -->
    <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-90 z-50 hidden flex items-center justify-center">
        <div class="relative max-w-4xl max-h-[90vh] mx-auto">
            <img id="modalImage" src="" alt="Imagem ampliada" class="max-w-full max-h-[90vh] object-contain">
            <button onclick="closeImageModal()" class="absolute top-4 right-4 text-white text-2xl hover:text-red-500">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>

    <script>
        // Funções para o modal de imagem
        function openImageModal(imageSrc) {
            const modal = document.getElementById('imageModal');
            const modalImage = document.getElementById('modalImage');
            modalImage.src = imageSrc;
            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeImageModal() {
            const modal = document.getElementById('imageModal');
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Fecha o modal ao clicar fora da imagem
        document.getElementById('imageModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeImageModal();
            }
        });
    </script>
</body>
</html> 

<!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '1618229525799946');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=1618229525799946&ev=PageView&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->