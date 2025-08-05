<?php
require_once 'config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validações básicas
    $errors = [];
    if (strlen($password) < 6) {
        $errors[] = "A senha deve ter pelo menos 6 caracteres";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email inválido";
    }

    // Verifica se email já existe
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = "Este email já está cadastrado";
    }

    if (empty($errors)) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        try {
            $stmt->execute([$name, $email, $hashedPassword]);
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['user_name'] = $name;
            
            // REGISTRO AUTOMÁTICO DE ASSINATURA SE VIER DO QUIZ
            if (isset($_SESSION['quiz_data']['plan'])) {
                $planName = $_SESSION['quiz_data']['plan'];
                // Mapeamento para o nome correto do plano
                $planMap = [
                    'basic' => 'Basic',
                    'intermediate' => 'Intermediate',
                    'vip' => 'Advanced',
                ];
                $planDbName = $planMap[strtolower($planName)] ?? null;
                if ($planDbName) {
                    $stmtPlan = $pdo->prepare("SELECT * FROM subscription_plans WHERE name = ?");
                    $stmtPlan->execute([$planDbName]);
                    $plan = $stmtPlan->fetch();
                    if ($plan) {
                        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
                        $stmtSub = $pdo->prepare("
                            INSERT INTO user_subscriptions (
                                user_id, plan_id, plan, status, stripe_subscription_id, started_at, expires_at, created_at
                            ) VALUES (?, ?, ?, 'active', ?, NOW(), ?, NOW())
                        ");
                        $stmtSub->execute([
                            $_SESSION['user_id'],
                            $plan['id'],
                            $plan['name'],
                            null,
                            $expiresAt
                        ]);
                    }
                }
                unset($_SESSION['quiz_data']['plan']);
            }
            // Se houver um avatar_id, atualiza o created_by do avatar
            if (isset($_GET['avatar_id'])) {
                $avatarId = intval($_GET['avatar_id']);
                $updateStmt = $pdo->prepare("UPDATE avatars SET created_by = ? WHERE id = ? AND (created_by IS NULL OR created_by = 0)");
                $updateStmt->execute([$_SESSION['user_id'], $avatarId]);
                header('Location: meus_avatars.php');
                exit;
            } else {
                header('Location: index.php');
                exit;
            }
        } catch (PDOException $e) {
            $errors[] = "Erro ao cadastrar usuário";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DesireChat - Cadastro</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d1a1a 100%);
            color: #fff;
        }
        .register-form {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center">
    <div class="container mx-auto px-4">
        <div class="max-w-md mx-auto">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-red-500">DesireChat</h1>
                <p class="text-gray-300 mt-2">Crie sua conta</p>
            </div>

            <?php if(!empty($errors)): ?>
                <div class="bg-red-500 text-white p-4 rounded mb-4">
                    <ul class="list-disc list-inside">
                        <?php foreach($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="register-form rounded-lg p-8">
                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="name" class="block text-gray-300 mb-2">Nome</label>
                        <input type="text" id="name" name="name" required
                            class="w-full px-4 py-2 rounded bg-gray-700 text-white border border-gray-600 focus:border-red-500 focus:outline-none">
                    </div>

                    <div class="mb-4">
                        <label for="email" class="block text-gray-300 mb-2">Email</label>
                        <input type="email" id="email" name="email" required
                            class="w-full px-4 py-2 rounded bg-gray-700 text-white border border-gray-600 focus:border-red-500 focus:outline-none">
                    </div>

                    <div class="mb-4">
                        <label for="password" class="block text-gray-300 mb-2">Senha</label>
                        <input type="password" id="password" name="password" required
                            class="w-full px-4 py-2 rounded bg-gray-700 text-white border border-gray-600 focus:border-red-500 focus:outline-none">
                    </div>

                    <button type="submit" class="w-full bg-red-500 text-white py-2 rounded hover:bg-red-600 transition">
                        Cadastrar
                    </button>
                </form>

                <div class="mt-4 text-center">
                    <p class="text-gray-300">
                        Já tem uma conta? 
                        <a href="login.php" class="text-red-500 hover:text-red-400">Faça login</a>
                    </p>
                </div>
            </div>
        </div>
    </div>
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
fbq('init', '1372939697326355');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=1372939697326355&ev=PageView&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->