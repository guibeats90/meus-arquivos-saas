<?php
require_once 'config/database.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        
        // Se houver um avatar_id, redireciona para associate_avatar.php
        if (isset($_GET['avatar_id'])) {
            header('Location: associate_avatar.php');
        } else {
        header('Location: index.php');
        }
        exit;
    } else {
        $error = "Email ou senha incorretos";
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DesireChat - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d1a1a 100%);
            color: #fff;
        }
        .login-form {
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
                <p class="text-gray-300 mt-2">Entre na sua conta</p>
            </div>

            <?php if(isset($error)): ?>
                <div class="bg-red-500 text-white p-4 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="login-form rounded-lg p-8">
                <form method="POST" action="">
                    <div class="mb-4">
                        <label for="email" class="block text-gray-300 mb-2">Email</label>
                        <input type="email" id="email" name="email" required
                            class="w-full px-4 py-2 rounded bg-gray-700 text-white border border-gray-600 focus:border-red-500 focus:outline-none">
                    </div>

                    <div class="mb-6">
                        <label for="password" class="block text-gray-300 mb-2">Senha</label>
                        <input type="password" id="password" name="password" required
                            class="w-full px-4 py-2 rounded bg-gray-700 text-white border border-gray-600 focus:border-red-500 focus:outline-none">
                    </div>

                    <button type="submit" class="w-full bg-red-500 text-white py-2 rounded hover:bg-red-600 transition">
                        Entrar
                    </button>
                </form>
                <p class="text-center mt-4 text-gray-300">Quer começar a conversar com a sua Avatar? Clique no botão abaixo, crie sua musa e sua conta e dê vida as suas fantasias.</p>
                <a href="../quiz-br/index.php" class="block w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600 transition text-center mt-4">
                    Crie sua Avatar
                </a>
            </div>
        </div>
    </div>
</body>
</html> 