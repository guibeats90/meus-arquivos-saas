<?php
require_once '../config/database.php';
session_start();

// Verificação de admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_model':
            $name = $_POST['name'] ?? '';
            $provider = $_POST['provider'] ?? '';
            $model_id = $_POST['model_id'] ?? '';
            
            if ($name && $provider && $model_id) {
                $stmt = $pdo->prepare("INSERT INTO ai_models (name, provider, model_id) VALUES (?, ?, ?)");
                $stmt->execute([$name, $provider, $model_id]);
            }
            break;
            
        case 'update_model':
            $id = $_POST['id'] ?? 0;
            $name = $_POST['name'] ?? '';
            $provider = $_POST['provider'] ?? '';
            $model_id = $_POST['model_id'] ?? '';
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            if ($id && $name && $provider && $model_id) {
                $stmt = $pdo->prepare("UPDATE ai_models SET name = ?, provider = ?, model_id = ?, is_active = ? WHERE id = ?");
                $stmt->execute([$name, $provider, $model_id, $is_active, $id]);
            }
            break;
            
        case 'delete_model':
            $id = $_POST['id'] ?? 0;
            if ($id) {
                $stmt = $pdo->prepare("DELETE FROM ai_models WHERE id = ?");
                $stmt->execute([$id]);
            }
            break;
    }
    
    header('Location: models.php');
    exit;
}

// Buscar modelos
$stmt = $pdo->query("SELECT * FROM ai_models ORDER BY provider, name");
$models = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DesireChat - Gerenciar Modelos de IA</title>
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
                <a href="index.php" class="block px-4 py-2 hover:bg-gray-700">Dashboard</a>
                <a href="users.php" class="block px-4 py-2 hover:bg-gray-700">Usuários</a>
                <a href="avatars.php" class="block px-4 py-2 hover:bg-gray-700">Avatares</a>
                <a href="models.php" class="block px-4 py-2 bg-gray-700">Modelos IA</a>
                <a href="conversations.php" class="block px-4 py-2 hover:bg-gray-700">Conversas</a>
                <a href="subscriptions.php" class="block px-4 py-2 hover:bg-gray-700">Assinaturas</a>
                <a href="../logout.php" class="block px-4 py-2 hover:bg-gray-700">Sair</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="ml-64 p-8">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-2xl font-bold">Gerenciar Modelos de IA</h2>
                <button onclick="showAddModal()" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                    Adicionar Modelo
                </button>
            </div>

            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nome</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Provedor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID do Modelo</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($models as $model): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($model['name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($model['provider']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap"><?php echo htmlspecialchars($model['model_id']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $model['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo $model['is_active'] ? 'Ativo' : 'Inativo'; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="showEditModal(<?php echo htmlspecialchars(json_encode($model)); ?>)" class="text-blue-600 hover:text-blue-900 mr-3">Editar</button>
                                <button onclick="deleteModel(<?php echo $model['id']; ?>)" class="text-red-600 hover:text-red-900">Excluir</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal Adicionar/Editar -->
    <div id="modelModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen">
            <div class="bg-white rounded-lg p-8 max-w-md w-full">
                <h3 id="modalTitle" class="text-xl font-bold mb-4">Adicionar Modelo</h3>
                <form id="modelForm" method="POST">
                    <input type="hidden" name="action" id="formAction" value="add_model">
                    <input type="hidden" name="id" id="modelId">
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="name">Nome</label>
                        <input type="text" id="name" name="name" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="provider">Provedor</label>
                        <input type="text" id="provider" name="provider" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2" for="model_id">ID do Modelo</label>
                        <input type="text" id="model_id" name="model_id" required
                            class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                    </div>
                    
                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" id="is_active" name="is_active" class="form-checkbox h-5 w-5 text-red-600">
                            <span class="ml-2 text-gray-700">Ativo</span>
                        </label>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="button" onclick="hideModal()" class="bg-gray-500 text-white px-4 py-2 rounded mr-2 hover:bg-gray-600">Cancelar</button>
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">Salvar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Adicionar Modelo';
            document.getElementById('formAction').value = 'add_model';
            document.getElementById('modelId').value = '';
            document.getElementById('name').value = '';
            document.getElementById('provider').value = '';
            document.getElementById('model_id').value = '';
            document.getElementById('is_active').checked = true;
            document.getElementById('modelModal').classList.remove('hidden');
        }

        function showEditModal(model) {
            document.getElementById('modalTitle').textContent = 'Editar Modelo';
            document.getElementById('formAction').value = 'update_model';
            document.getElementById('modelId').value = model.id;
            document.getElementById('name').value = model.name;
            document.getElementById('provider').value = model.provider;
            document.getElementById('model_id').value = model.model_id;
            document.getElementById('is_active').checked = model.is_active == 1;
            document.getElementById('modelModal').classList.remove('hidden');
        }

        function hideModal() {
            document.getElementById('modelModal').classList.add('hidden');
        }

        function deleteModel(id) {
            if (confirm('Tem certeza que deseja excluir este modelo?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_model">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html> 