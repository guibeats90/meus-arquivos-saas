<?php
require_once '../config/database.php';
session_start();

// Verificação básica de autenticação admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// TODO: Implementar verificação de permissões de admin
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DesireChat - Painel Administrativo</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #f3f4f6;
        }
    </style>
</head>
<body>
    <div class="min-h-screen">
        <!-- Sidebar -->
        <div class="fixed inset-y-0 left-0 w-64 bg-gray-800 text-white">
            <div class="p-4">
                <h1 class="text-2xl font-bold">DesireChat Admin</h1>
            </div>
            <nav class="mt-8">
                <a href="index.php" class="block px-4 py-2 bg-gray-700">Dashboard</a>
                <a href="users.php" class="block px-4 py-2 hover:bg-gray-700">Usuários</a>
                <a href="avatars.php" class="block px-4 py-2 hover:bg-gray-700">Avatares</a>
                <a href="models.php" class="block px-4 py-2 hover:bg-gray-700">Modelos IA</a>
                <a href="conversations.php" class="block px-4 py-2 hover:bg-gray-700">Conversas</a>
                <a href="subscriptions.php" class="block px-4 py-2 hover:bg-gray-700">Assinaturas</a>
                <a href="../logout.php" class="block px-4 py-2 hover:bg-gray-700">Sair</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="ml-64 p-8">
            <h2 class="text-2xl font-bold mb-8">Dashboard</h2>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total de Usuários -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-gray-500 text-sm font-medium">Total de Usuários</h3>
                    <p class="text-3xl font-bold mt-2">
                        <?php
                        $stmt = $pdo->query("SELECT COUNT(*) FROM users");
                        echo $stmt->fetchColumn();
                        ?>
                    </p>
                </div>

                <!-- Total de Avatares -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-gray-500 text-sm font-medium">Total de Avatares</h3>
                    <p class="text-3xl font-bold mt-2">
                        <?php
                        $stmt = $pdo->query("SELECT COUNT(*) FROM avatars");
                        echo $stmt->fetchColumn();
                        ?>
                    </p>
                </div>

                <!-- Total de Conversas -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-gray-500 text-sm font-medium">Total de Conversas</h3>
                    <p class="text-3xl font-bold mt-2">
                        <?php
                        $stmt = $pdo->query("SELECT COUNT(*) FROM conversations");
                        echo $stmt->fetchColumn();
                        ?>
                    </p>
                </div>

                <!-- Assinaturas Ativas -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-gray-500 text-sm font-medium">Assinaturas Ativas</h3>
                    <p class="text-3xl font-bold mt-2">
                        <?php
                        $stmt = $pdo->query("SELECT COUNT(*) FROM subscriptions WHERE status = 'active'");
                        echo $stmt->fetchColumn();
                        ?>
                    </p>
                </div>
            </div>

            <!-- Últimas Conversas -->
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium mb-4">Últimas Conversas</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="text-left">
                                <th class="px-4 py-2">Usuário</th>
                                <th class="px-4 py-2">Avatar</th>
                                <th class="px-4 py-2">Data</th>
                                <th class="px-4 py-2">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $pdo->query("
                                SELECT c.*, u.name as user_name, a.name as avatar_name
                                FROM conversations c
                                JOIN users u ON c.user_id = u.id
                                JOIN avatars a ON c.avatar_id = a.id
                                ORDER BY c.created_at DESC
                                LIMIT 5
                            ");
                            while($conversation = $stmt->fetch()):
                            ?>
                            <tr class="border-t">
                                <td class="px-4 py-2"><?php echo htmlspecialchars($conversation['user_name']); ?></td>
                                <td class="px-4 py-2"><?php echo htmlspecialchars($conversation['avatar_name']); ?></td>
                                <td class="px-4 py-2"><?php echo date('d/m/Y H:i', strtotime($conversation['created_at'])); ?></td>
                                <td class="px-4 py-2">
                                    <a href="conversation.php?id=<?php echo $conversation['id']; ?>" 
                                       class="text-blue-500 hover:text-blue-700">Ver</a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 