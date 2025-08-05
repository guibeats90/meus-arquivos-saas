<?php
require_once 'config/database.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$planId = $_GET['plan_id'] ?? null;
if (!$planId) {
    header('Location: planos.php');
    exit;
}

// Busca informações do plano
$stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE id = ?");
$stmt->execute([$planId]);
$plan = $stmt->fetch();

if (!$plan) {
    header('Location: planos.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DesireChat - Assinar <?php echo htmlspecialchars($plan['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d1a1a 100%);
            color: #fff;
        }
    </style>
</head>
<body class="min-h-screen">
    <nav class="bg-black bg-opacity-50 p-4">
        <div class="container mx-auto flex justify-between items-center">
            <h1 class="text-2xl font-bold text-red-500">DesireChat</h1>
            <div class="space-x-4">
                <a href="planos.php" class="text-white hover:text-red-500">Voltar aos Planos</a>
            </div>
        </div>
    </nav>

    <main class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <h2 class="text-3xl font-bold mb-8">Assinar <?php echo htmlspecialchars($plan['name']); ?></h2>
            
            <div class="bg-gray-800 rounded-lg p-6 mb-8">
                <h3 class="text-xl font-bold mb-4">Detalhes do Plano</h3>
                <p class="text-3xl font-bold mb-4">
                    €<?php echo number_format($plan['price'], 2); ?>
                    <span class="text-sm text-gray-400">/mês</span>
                </p>
                <ul class="space-y-2 mb-6">
                    <?php foreach (explode("\n", $plan['features']) as $feature): ?>
                        <li class="flex items-center">
                            <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            <?php echo htmlspecialchars(trim($feature)); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="bg-gray-800 rounded-lg p-6">
                <h3 class="text-xl font-bold mb-4">Informações de Pagamento</h3>
                <form id="paymentForm" class="space-y-4">
                    <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                    
                    <div>
                        <label class="block text-sm font-medium mb-2">Método de Pagamento</label>
                        <select name="payment_method" class="w-full bg-gray-700 text-white rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500">
                            <option value="credit_card">Cartão de Crédito</option>
                            <option value="pix">PIX</option>
                            <option value="bank_transfer">Transferência Bancária</option>
                        </select>
                    </div>

                    <div id="creditCardFields" class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium mb-2">Número do Cartão</label>
                            <input type="text" name="card_number" class="w-full bg-gray-700 text-white rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500" placeholder="1234 5678 9012 3456">
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium mb-2">Validade</label>
                                <input type="text" name="expiry" class="w-full bg-gray-700 text-white rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500" placeholder="MM/AA">
                            </div>
                            <div>
                                <label class="block text-sm font-medium mb-2">CVV</label>
                                <input type="text" name="cvv" class="w-full bg-gray-700 text-white rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500" placeholder="123">
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium mb-2">Nome no Cartão</label>
                            <input type="text" name="card_name" class="w-full bg-gray-700 text-white rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-red-500" placeholder="NOME COMO ESTÁ NO CARTÃO">
                        </div>
                    </div>

                    <button type="submit" class="w-full bg-red-500 text-white py-3 rounded hover:bg-red-600 transition">
                        Pagar €<?php echo number_format($plan['price'], 2); ?>
                    </button>
                </form>
            </div>
        </div>
    </main>

    <script>
        const paymentForm = document.getElementById('paymentForm');
        const paymentMethod = document.querySelector('select[name="payment_method"]');
        const creditCardFields = document.getElementById('creditCardFields');

        // Mostra/esconde campos do cartão de crédito
        paymentMethod.addEventListener('change', function() {
            creditCardFields.style.display = this.value === 'credit_card' ? 'block' : 'none';
        });

        // Processa o pagamento
        paymentForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            try {
                const response = await fetch('api/process_subscription.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('Assinatura realizada com sucesso!');
                    window.location.href = 'planos.php';
                } else {
                    alert('Erro ao processar pagamento: ' + result.error);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao processar pagamento. Tente novamente.');
            }
        });
    </script>
</body>
</html> 