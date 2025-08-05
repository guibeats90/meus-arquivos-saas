<?php
require_once 'config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Busca status da assinatura do usuário
$stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
$stmt->execute([$_SESSION['user_id']]);
$subscription = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DesireChat - Assinatura</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d1a1a 100%);
            color: #fff;
        }
        .subscription-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="min-h-screen">
    <nav class="bg-black bg-opacity-50 p-4">
        <div class="container mx-auto flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold text-red-500">DesireChat</a>
            <div class="flex items-center space-x-4">
                <a href="index.php" class="text-white hover:text-red-500">Voltar</a>
                <a href="logout.php" class="text-white hover:text-red-500">Sair</a>
            </div>
        </div>
    </nav>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold mb-4">Assinatura DesireChat</h1>
                <p class="text-gray-300">Desbloqueie conversas ilimitadas com nossos avatares</p>
            </div>

            <div class="subscription-card rounded-lg p-8 mb-8">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold mb-2">Plano Premium</h2>
                    <p class="text-4xl font-bold text-red-500 mb-4">€25<span class="text-lg text-gray-300">/mês</span></p>
                    <p class="text-gray-300">20 mensagens grátis para testar</p>
                </div>

                <ul class="space-y-4 mb-8">
                    <li class="flex items-center">
                        <svg class="w-6 h-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Conversas ilimitadas
                    </li>
                    <li class="flex items-center">
                        <svg class="w-6 h-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Acesso a todos os avatares
                    </li>
                    <li class="flex items-center">
                        <svg class="w-6 h-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Histórico de conversas
                    </li>
                    <li class="flex items-center">
                        <svg class="w-6 h-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Cancelamento a qualquer momento
                    </li>
                </ul>

                <?php if($subscription && $subscription['status'] === 'active'): ?>
                    <div class="text-center">
                        <p class="text-green-500 mb-4">Sua assinatura está ativa!</p>
                        <p class="text-gray-300">Próximo pagamento: <?php echo date('d/m/Y', strtotime($subscription['end_date'])); ?></p>
                    </div>
                <?php else: ?>
                    <button id="subscribe-button" class="w-full bg-red-500 text-white py-3 rounded-lg hover:bg-red-600 transition">
                        Assinar Agora
                    </button>
                <?php endif; ?>
            </div>

            <div class="text-center text-gray-300">
                <p>Pagamento seguro via Stripe</p>
                <p class="text-sm mt-2">Ao assinar, você concorda com nossos <a href="#" class="text-red-500 hover:text-red-400">Termos de Serviço</a></p>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('subscribe-button')?.addEventListener('click', async () => {
            try {
                // TODO: Integrar com Stripe
                // Por enquanto, vamos simular o processo
                const response = await fetch('api/create_subscription.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        plan: 'premium'
                    })
                });

                const data = await response.json();
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Erro ao processar assinatura: ' + data.error);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao processar assinatura');
            }
        });
    </script>
</body>
</html> 