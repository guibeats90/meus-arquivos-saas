<?php
require_once 'config/database.php';
session_start();

// Busca os planos disponíveis
$stmt = $pdo->query("SELECT * FROM subscription_plans ORDER BY price ASC");
$plans = $stmt->fetchAll();

// Verifica se o usuário tem uma assinatura ativa
$activeSubscription = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("
        SELECT s.*, p.name as plan_name, p.features 
        FROM user_subscriptions s
        JOIN subscription_plans p ON s.plan_id = p.id
        WHERE s.user_id = ? AND s.status = 'active' AND s.expires_at > NOW()
        ORDER BY s.created_at DESC LIMIT 1
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $activeSubscription = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DesireChat - Planos de Assinatura</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d1a1a 100%);
            color: #fff;
        }
        .plan-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            padding: 2rem;
        }
        .plan-card:hover {
            transform: translateY(-5px);
        }
        .plan-card .plan-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .plan-card .plan-price {
            margin: 1.5rem 0;
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
        .plan-card .plan-button {
            margin-top: auto;
            width: 100%;
            padding: 1rem;
            border-radius: 9999px;
            font-weight: bold;
            transition: all 0.3s ease;
        }
        .current-plan {
            border: 2px solid #ef4444;
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
        .register-btn {
            background: linear-gradient(45deg, #ff3366, #ff0000);
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 4px 15px rgba(255, 51, 102, 0.3);
        }
        .register-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 51, 102, 0.4);
            background: linear-gradient(45deg, #ff0000, #ff3366);
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
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="index.php" class="nav-link">Início</a>
                        <a href="meus_chats.php" class="nav-link">Meus Chats</a>
                        <a href="planos.php" class="nav-link">Planos</a>
                        <a href="quiz/index.php" class="nav-link">Criar Minha IA</a>
                        <a href="meus_avatars.php" class="nav-link">Meus Avatares</a>
                        <a href="logout.php" class="nav-link">Sair</a>
                    <?php else: ?>
                        <a href="planos.php" class="nav-link">Planos</a>
                        <a href="login.php" class="nav-link">Login</a>
                        <a href="register.php" class="register-btn">Cadastrar</a>
                    <?php endif; ?>
                </div>
            </div>
        </nav>
    </div>

    <main class="container mx-auto px-4 py-8 mt-20">
        <div class="text-center max-w-3xl mx-auto mb-12">
            <h1 class="text-4xl md:text-5xl font-bold mb-4 bg-gradient-to-r from-red-500 to-pink-500 bg-clip-text text-transparent">
                Encontre o Amor Perfeito com IA
            </h1>
            <p class="text-xl text-gray-300 mb-6">
                Descubra conexões únicas e conversas envolventes com avatares inteligentes criados especialmente para você
            </p>
            <div class="flex flex-wrap justify-center gap-4 mb-8">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>Mais de 1000+ usuários ativos</span>
                </div>
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>Avatares exclusivos</span>
                </div>
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span>Suporte VIP 24/7</span>
                </div>
            </div>
        </div>

        <?php if ($activeSubscription): ?>
            <div class="bg-gradient-to-r from-gray-800 to-gray-900 rounded-lg p-6 mb-12 max-w-3xl mx-auto border border-gray-700">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-2xl font-bold mb-2">Sua Assinatura VIP</h2>
                        <p class="text-xl mb-2 bg-gradient-to-r from-red-500 to-pink-500 bg-clip-text text-transparent font-bold">
                            <?php echo htmlspecialchars($activeSubscription['plan_name']); ?>
                        </p>
                        <p class="text-gray-300">
                            Aproveite até: <?php echo date('d/m/Y', strtotime($activeSubscription['expires_at'])); ?>
                        </p>
                    </div>
                    <button onclick="window.location.href='subscribe.php?plan_id=<?php echo $activeSubscription['plan_id']; ?>'" 
                            class="bg-gradient-to-r from-red-500 to-pink-500 text-white px-8 py-3 rounded-full hover:from-red-600 hover:to-pink-600 transition transform hover:scale-105 font-bold shadow-lg">
                        Renovar Agora
                    </button>
                </div>
            </div>
        <?php endif; ?>

        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">Escolha Seu Plano Ideal</h2>
            <p class="text-xl text-gray-300 max-w-2xl mx-auto">
                Comece com 10 mensagens grátis e explore um mundo de possibilidades. Upgrade a qualquer momento para desbloquear experiências ilimitadas.
            </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
            <?php foreach ($plans as $plan): ?>
                <div class="plan-card rounded-2xl <?php echo ($activeSubscription && $activeSubscription['plan_id'] == $plan['id']) ? 'current-plan transform scale-105 border-2 border-red-500' : ''; ?>">
                    <div class="plan-header">
                        <?php if (strpos(strtolower($plan['name']), 'básico') !== false): ?>
                            <div class="text-pink-500 text-sm uppercase tracking-wide">Plano Inicial</div>
                        <?php elseif (strpos(strtolower($plan['name']), 'intermediário') !== false): ?>
                            <div class="text-pink-500 text-sm uppercase tracking-wide">Mais Popular</div>
                        <?php elseif (strpos(strtolower($plan['name']), 'avançado') !== false): ?>
                            <div class="text-pink-500 text-sm uppercase tracking-wide">Plano VIP</div>
                        <?php endif; ?>
                        
                        <h3 class="text-2xl font-bold mt-2 mb-4"><?php echo htmlspecialchars($plan['name']); ?></h3>
                        <div class="plan-price flex justify-center items-baseline">
                            <span class="text-5xl font-bold">€<?php echo number_format($plan['price'], 2); ?></span>
                            <span class="text-xl text-gray-400 ml-2">/mês</span>
                        </div>
                    </div>
                    
                    <ul class="space-y-4 mb-8 text-left">
                        <?php
                        $basicBenefits = [
                            '100 mensagens por dia',
                            '5 avatares simultâneos',
                            'Histórico de 7 dias',
                            'Suporte por email'
                        ];

                        $intermediateBenefits = [
                            'Mensagens ilimitadas',
                            ['Receber fotos explícitas da sua avatar🔞', true],
                            '15 avatares simultâneos',
                            'Histórico completo',
                            'Suporte prioritário 24/7'
                        ];

                        $advancedBenefits = [
                            'Mensagens ilimitadas',
                            ['Receber fotos e vídeos explícitos da sua avatar🔞', true],
                            ['Receber áudios sem limites da sua avatar🔞', true],
                            'Avatares ilimitados',
                            'Histórico completo',
                            'Suporte VIP 24/7',
                            'Acesso exclusivo a avatares VIP',
                        ];

                        if (strpos(strtolower($plan['name']), 'básico') !== false) {
                            $benefits = $basicBenefits;
                        } elseif (strpos(strtolower($plan['name']), 'intermediário') !== false) {
                            $benefits = $intermediateBenefits;
                        } else {
                            $benefits = $advancedBenefits;
                        }

                        foreach ($benefits as $benefit):
                            $isHighlight = is_array($benefit);
                            $text = $isHighlight ? $benefit[0] : $benefit;
                        ?>
                            <li class="flex items-center">
                                <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span class="text-base ml-3 <?php echo $isHighlight ? 'font-bold text-purple-400' : ''; ?>"><?php echo $text; ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="<?php echo $plan['name'] === 'Básico' ? 'https://buy.stripe.com/3cscQ68Ul39acnK6oD' : 
                                      ($plan['name'] === 'Intermediário' ? 'https://buy.stripe.com/3cseYec6xdNOgE0cN2' : 
                                      'https://buy.stripe.com/aEUbM2c6xh0087u8wN'); ?>" 
                           target="_blank"
                           class="w-full bg-gradient-to-r from-red-500 to-pink-500 text-white py-4 rounded-full hover:from-red-600 hover:to-pink-600 transition transform hover:scale-105 font-bold text-lg shadow-lg text-center">
                            <?php echo ($activeSubscription && $activeSubscription['plan_id'] == $plan['id']) ? '🔄 Renovar Plano' : '🚀 Assinar Agora'; ?>
                        </a>
                    <?php else: ?>
                        <a href="login.php" 
                           class="w-full bg-gradient-to-r from-red-500 to-pink-500 text-white py-4 rounded-full hover:from-red-600 hover:to-pink-600 transition transform hover:scale-105 font-bold text-lg shadow-lg text-center">
                            👋 Fazer Login
                        </a>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-16 max-w-4xl mx-auto">
            <h3 class="text-2xl font-bold text-center mb-8">Perguntas Frequentes</h3>
            <div class="space-y-6">
                <div class="bg-gray-800 bg-opacity-50 rounded-lg p-6">
                    <h4 class="text-xl font-bold mb-2">Como funciona o período gratuito?</h4>
                    <p class="text-gray-300">Você começa com 10 mensagens gratuitas para experimentar nossa plataforma. Após isso, escolha o plano que melhor atende suas necessidades para continuar conversando.</p>
                </div>
                <div class="bg-gray-800 bg-opacity-50 rounded-lg p-6">
                    <h4 class="text-xl font-bold mb-2">Posso mudar de plano depois?</h4>
                    <p class="text-gray-300">Sim! Você pode fazer upgrade ou downgrade do seu plano a qualquer momento, aproveitando os benefícios do novo plano instantaneamente.</p>
                </div>
                <div class="bg-gray-800 bg-opacity-50 rounded-lg p-6">
                    <h4 class="text-xl font-bold mb-2">Como funciona o suporte VIP?</h4>
                    <p class="text-gray-300">Nosso suporte VIP oferece atendimento prioritário 24 horas por dia, 7 dias por semana, com tempo médio de resposta de menos de 30 minutos.</p>
                </div>
            </div>
        </div>

        <div class="mt-16 text-center">
            <h3 class="text-2xl font-bold mb-4">Ainda com Dúvidas?</h3>
            <p class="text-gray-300 mb-6">Nossa equipe está pronta para ajudar você a encontrar o plano perfeito</p>
            <a href="mailto:suporte@desirechat.com" class="inline-flex items-center text-red-500 hover:text-red-400 font-bold">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                </svg>
                Fale com o Suporte
            </a>
        </div>
    </main>
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