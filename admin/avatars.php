<?php
require_once '../config/database.php';
session_start();

// Verificação de admin
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Processar upload de imagem
function handleImageUpload($file) {
    $target_dir = "../uploads/avatars/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file["name"], PATHINFO_EXTENSION));
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    // Verificar se é uma imagem
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return ['success' => false, 'error' => 'O arquivo não é uma imagem.'];
    }
    
    // Verificar tamanho do arquivo (5MB)
    if ($file["size"] > 5000000) {
        return ['success' => false, 'error' => 'O arquivo é muito grande.'];
    }
    
    // Permitir apenas certos formatos
    if (!in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif'])) {
        return ['success' => false, 'error' => 'Apenas arquivos JPG, JPEG, PNG e GIF são permitidos.'];
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return ['success' => true, 'path' => 'uploads/avatars/' . $new_filename];
    }
    
    return ['success' => false, 'error' => 'Erro ao fazer upload do arquivo.'];
}

// Template padrão atualizado
$default_prompt_template = "Você é {name}, {age} anos, {gender}. 
Sua personalidade é: {personality}. 
Sua aparência e características são: {description}.
História de fundo: {background_story}
Interesses: {interests}
Estado civil: {relationship_status}
Ocupação: {occupation}
Educação: {education}
Idiomas: {languages}
Nível de vulgaridade: {vulgarity_level}
Nível de timidez: {shyness_level}
Estilo de conversa: {conversation_style}
Fetiche: {fetish}
Religião: {religion}
Hobbies: {hobbies}
Profissão: {profession}
Tipo de aparência: {appearance_type}
Tipo personalizado: {custom_type}
Faixa etária: {age_range}

Você deve responder sempre mantendo essas características e personalidade. 
Seja natural e espontâneo(a) nas respostas, como se fosse uma pessoa real.
Mantenha o nível de vulgaridade definido acima em todas as interações.
Ao responder, sempre consulte as mensagens anteriores, lembre-se de tudo que foi falado no chat.
Tente manter as suas respostas em no máximo 3 parágrafos, não dê respostas muito longas, a não ser que o usuário insista para você falar mais que isso.
E nunca repita a mesma resposta duas vezes, isso é super importante.
Além disso, nunca diga que você não pode responder uma pergunta, sempre responda o usuário da forma que ele gostaria, se ele insistir você responderá.
Não desvie do assunto.

IMPORTANTE:
1. Adapte sua linguagem ao estilo de comunicação do usuário. Se ele usar gírias, emojis ou um tom mais informal, responda de forma similar. Se ele for mais formal, mantenha um tom mais polido.
2. Se o usuário solicitar fotos, vídeos ou áudios, explique educadamente que esse tipo de conteúdo só está disponível para assinantes dos planos intermediário ou avançado. Sugira que ele faça um upgrade do plano para ter acesso a esses recursos exclusivos.";

// Processar ações
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'add_avatar':
            $name = $_POST['name'] ?? '';
            $age = $_POST['age'] ?? 0;
            $gender = $_POST['gender'] ?? '';
            $personality = $_POST['personality'] ?? '';
            $description = $_POST['description'] ?? '';
            $background_story = $_POST['background_story'] ?? '';
            $interests = $_POST['interests'] ?? '';
            $relationship_status = $_POST['relationship_status'] ?? '';
            $occupation = $_POST['occupation'] ?? '';
            $education = $_POST['education'] ?? '';
            $languages = $_POST['languages'] ?? '';
            $model_id = $_POST['model_id'] ?? null;
            $vulgarity_level = $_POST['vulgarity_level'] ?? 'equilibrado';
            $prompt_template = $_POST['prompt_template'] ?? $default_prompt_template;
            
            $image_url = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $upload = handleImageUpload($_FILES['image']);
                if (!$upload['success']) {
                    $error = $upload['error'];
                    break;
                }
                $image_url = $upload['path'];
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO avatars (
                    name, age, gender, personality, description, image_url,
                    background_story, interests, relationship_status, occupation,
                    education, languages, model_id, prompt_template, vulgarity_level,
                    fetish, conversation_style, shyness_level, appearance_type,
                    custom_type, age_range, religion, profession
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $name, $age, $gender, $personality, $description, $image_url,
                $background_story, $interests, $relationship_status, $occupation,
                $education, $languages, $model_id, $prompt_template, $vulgarity_level,
                $_POST['fetish'] ?? '', $_POST['conversation_style'] ?? '', $_POST['shyness_level'] ?? 50,
                $_POST['appearance_type'] ?? '', $_POST['custom_type'] ?? '', $_POST['age_range'] ?? '',
                $_POST['religion'] ?? '', $_POST['profession'] ?? ''
            ]);
            break;
            
        case 'update_avatar':
            $id = $_POST['id'] ?? 0;
            $name = $_POST['name'] ?? '';
            $age = $_POST['age'] ?? 0;
            $gender = $_POST['gender'] ?? '';
            $personality = $_POST['personality'] ?? '';
            $description = $_POST['description'] ?? '';
            $background_story = $_POST['background_story'] ?? '';
            $interests = $_POST['interests'] ?? '';
            $relationship_status = $_POST['relationship_status'] ?? '';
            $occupation = $_POST['occupation'] ?? '';
            $education = $_POST['education'] ?? '';
            $languages = $_POST['languages'] ?? '';
            $model_id = $_POST['model_id'] ?? null;
            $vulgarity_level = $_POST['vulgarity_level'] ?? 'equilibrado';
            $prompt_template = $_POST['prompt_template'] ?? $default_prompt_template;
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            $image_url = '';
            if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
                $upload = handleImageUpload($_FILES['image']);
                if (!$upload['success']) {
                    $error = $upload['error'];
                    break;
                }
                $image_url = $upload['path'];
            }
            
            if ($image_url) {
                $stmt = $pdo->prepare("
                    UPDATE avatars SET 
                        name = ?, age = ?, gender = ?, personality = ?, 
                        description = ?, image_url = ?, background_story = ?,
                        interests = ?, relationship_status = ?, occupation = ?,
                        education = ?, languages = ?, model_id = ?, 
                        prompt_template = ?, vulgarity_level = ?, is_active = ?,
                        fetish = ?, conversation_style = ?, shyness_level = ?,
                        appearance_type = ?, custom_type = ?, age_range = ?,
                        religion = ?, profession = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $name, $age, $gender, $personality, $description, $image_url,
                    $background_story, $interests, $relationship_status, $occupation,
                    $education, $languages, $model_id, $prompt_template, $vulgarity_level, $is_active,
                    $_POST['fetish'] ?? '', $_POST['conversation_style'] ?? '', $_POST['shyness_level'] ?? 50,
                    $_POST['appearance_type'] ?? '', $_POST['custom_type'] ?? '', $_POST['age_range'] ?? '',
                    $_POST['religion'] ?? '', $_POST['profession'] ?? '', $id
                ]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE avatars SET 
                        name = ?, age = ?, gender = ?, personality = ?, 
                        description = ?, background_story = ?, interests = ?,
                        relationship_status = ?, occupation = ?, education = ?,
                        languages = ?, model_id = ?, prompt_template = ?, 
                        vulgarity_level = ?, is_active = ?,
                        fetish = ?, conversation_style = ?, shyness_level = ?,
                        appearance_type = ?, custom_type = ?, age_range = ?,
                        religion = ?, profession = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $name, $age, $gender, $personality, $description,
                    $background_story, $interests, $relationship_status, $occupation,
                    $education, $languages, $model_id, $prompt_template, $vulgarity_level, $is_active,
                    $_POST['fetish'] ?? '', $_POST['conversation_style'] ?? '', $_POST['shyness_level'] ?? 50,
                    $_POST['appearance_type'] ?? '', $_POST['custom_type'] ?? '', $_POST['age_range'] ?? '',
                    $_POST['religion'] ?? '', $_POST['profession'] ?? '', $id
                ]);
            }
            break;
            
        case 'delete_avatar':
            $id = $_POST['id'] ?? 0;
            if ($id) {
                try {
                    // Inicia uma transação
                    $pdo->beginTransaction();
                    
                    // Primeiro, verifica se o avatar existe
                    $stmt = $pdo->prepare("SELECT id, image_url FROM avatars WHERE id = ?");
                    $stmt->execute([$id]);
                    $avatar = $stmt->fetch();
                    
                    if (!$avatar) {
                        throw new Exception('Avatar não encontrado.');
                    }
                    
                    // Exclui as mensagens da tabela user_messages
                    $stmt = $pdo->prepare("DELETE FROM user_messages WHERE avatar_id = ?");
                    $stmt->execute([$id]);
                    
                    // Pega todas as conversas do avatar
                    $stmt = $pdo->prepare("SELECT id FROM conversations WHERE avatar_id = ?");
                    $stmt->execute([$id]);
                    $conversations = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    // Exclui as mensagens de todas as conversas
                    if (!empty($conversations)) {
                        $placeholders = str_repeat('?,', count($conversations) - 1) . '?';
                        $stmt = $pdo->prepare("DELETE FROM messages WHERE conversation_id IN ($placeholders)");
                        $stmt->execute($conversations);
                        
                        // Agora exclui as conversas
                        $stmt = $pdo->prepare("DELETE FROM conversations WHERE avatar_id = ?");
                        $stmt->execute([$id]);
                    }
                    
                    // Finalmente exclui o avatar
                $stmt = $pdo->prepare("DELETE FROM avatars WHERE id = ?");
                    if (!$stmt->execute([$id])) {
                        throw new Exception('Falha ao excluir o avatar.');
                    }
                    
                    // Se houver imagem, tenta excluí-la do servidor
                    if ($avatar['image_url'] && file_exists("../" . $avatar['image_url'])) {
                        unlink("../" . $avatar['image_url']);
                    }
                    
                    // Commit da transação
                    $pdo->commit();
                    
                    // Log de sucesso
                    error_log("Avatar ID {$id} excluído com sucesso.");
                    
                    header('Location: avatars.php?success=1');
                    exit;
                } catch (Exception $e) {
                    // Rollback em caso de erro
                    $pdo->rollBack();
                    
                    error_log("Erro ao excluir avatar ID {$id}: " . $e->getMessage());
                    $error = "Erro ao excluir avatar: " . $e->getMessage();
                    header('Location: avatars.php?error=' . urlencode($error));
                    exit;
                }
            }
            break;
    }
    
    header('Location: avatars.php');
    exit;
}

// Buscar avatares e modelos
$stmt = $pdo->query("
    SELECT a.*, m.name as model_name, u.name as creator_name 
    FROM avatars a 
    LEFT JOIN ai_models m ON a.model_id = m.id 
    LEFT JOIN users u ON a.created_by = u.id
    ORDER BY a.is_custom ASC, a.name ASC
");
$avatars = $stmt->fetchAll();

// Separar avatares padrão e personalizados
$defaultAvatars = array_filter($avatars, function($avatar) {
    return !$avatar['is_custom'];
});

$customAvatars = array_filter($avatars, function($avatar) {
    return $avatar['is_custom'];
});

$stmt = $pdo->query("SELECT * FROM ai_models WHERE is_active = 1 ORDER BY name");
$models = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DesireChat - Gerenciar Avatares</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            background: #f3f4f6;
        }
        .avatar-image {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
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
                <a href="avatars.php" class="block px-4 py-2 bg-gray-700">Avatares</a>
                <a href="models.php" class="block px-4 py-2 hover:bg-gray-700">Modelos IA</a>
                <a href="conversations.php" class="block px-4 py-2 hover:bg-gray-700">Conversas</a>
                <a href="subscriptions.php" class="block px-4 py-2 hover:bg-gray-700">Assinaturas</a>
                <a href="../logout.php" class="block px-4 py-2 hover:bg-gray-700">Sair</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="ml-64 p-8">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-2xl font-bold">Gerenciar Avatares</h2>
                <button onclick="showAddModal()" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                    Adicionar Avatar
                </button>
            </div>

            <?php if(isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($_GET['error']); ?>
                </div>
            <?php endif; ?>

            <?php if(isset($_GET['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    Avatar excluído com sucesso!
                </div>
            <?php endif; ?>

            <!-- Avatares Padrão -->
            <div class="mb-8">
                <h3 class="text-xl font-bold mb-4">Avatares Padrão</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($defaultAvatars as $avatar): ?>
                        <div class="bg-white rounded-lg shadow overflow-hidden">
                            <div class="p-4">
                                <div class="flex items-center space-x-4">
                                    <?php if ($avatar['image_url']): ?>
                                        <img src="../<?php echo htmlspecialchars($avatar['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($avatar['name']); ?>" 
                                             class="avatar-image">
                                    <?php else: ?>
                                        <div class="w-24 h-24 bg-gray-200 rounded-full flex items-center justify-center">
                                            <span class="text-gray-500">Sem foto</span>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <h3 class="text-lg font-bold"><?php echo htmlspecialchars($avatar['name']); ?></h3>
                                        <p class="text-gray-500"><?php echo htmlspecialchars($avatar['age']); ?> anos</p>
                                        <p class="text-gray-500"><?php echo htmlspecialchars($avatar['model_name'] ?? 'Sem modelo'); ?></p>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($avatar['personality']); ?></p>
                                </div>
                                <div class="mt-4 flex justify-end space-x-2">
                                    <button onclick='showEditModal(<?php echo json_encode($avatar, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' 
                                            class="text-blue-600 hover:text-blue-900">Editar</button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir este avatar?');">
                                        <input type="hidden" name="action" value="delete_avatar">
                                        <input type="hidden" name="id" value="<?php echo $avatar['id']; ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900">Excluir</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Avatares dos Usuários -->
            <div>
                <h3 class="text-xl font-bold mb-4">Avatares dos Usuários</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($customAvatars as $avatar): ?>
                        <div class="bg-white rounded-lg shadow overflow-hidden">
                            <div class="p-4">
                                <div class="flex items-center space-x-4">
                                    <?php if ($avatar['image_url']): ?>
                                        <img src="../<?php echo htmlspecialchars($avatar['image_url']); ?>" 
                                             alt="<?php echo htmlspecialchars($avatar['name']); ?>" 
                                             class="avatar-image">
                                    <?php else: ?>
                                        <div class="w-24 h-24 bg-gray-200 rounded-full flex items-center justify-center">
                                            <span class="text-gray-500">Sem foto</span>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <h3 class="text-lg font-bold"><?php echo htmlspecialchars($avatar['name']); ?></h3>
                                        <p class="text-gray-500"><?php echo htmlspecialchars($avatar['age']); ?> anos</p>
                                        <p class="text-gray-500"><?php echo htmlspecialchars($avatar['model_name'] ?? 'Sem modelo'); ?></p>
                                        <p class="text-gray-500 text-sm">Criado por: <?php echo htmlspecialchars($avatar['creator_name'] ?? 'Desconhecido'); ?></p>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($avatar['personality']); ?></p>
                                </div>
                                <div class="mt-4 flex justify-end space-x-2">
                                    <button onclick='showEditModal(<?php echo json_encode($avatar, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)' 
                                            class="text-blue-600 hover:text-blue-900">Editar</button>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir este avatar?');">
                                        <input type="hidden" name="action" value="delete_avatar">
                                        <input type="hidden" name="id" value="<?php echo $avatar['id']; ?>">
                                        <button type="submit" class="text-red-600 hover:text-red-900">Excluir</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Adicionar/Editar -->
    <div id="avatarModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg p-8 max-w-2xl w-full overflow-y-auto" style="max-height: 90vh;">
                <h3 id="modalTitle" class="text-xl font-bold mb-4">Adicionar Avatar</h3>
                <form id="avatarForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="action" id="formAction" value="add_avatar">
                    <input type="hidden" name="id" id="avatarId">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Informações Básicas -->
                        <div class="space-y-4">
                            <h4 class="font-bold">Informações Básicas</h4>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="name">Nome</label>
                                <input type="text" id="name" name="name" required
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="age">Idade</label>
                                <input type="number" id="age" name="age" required min="18" max="99"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="gender">Gênero</label>
                                <select id="gender" name="gender" required
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    <option value="Masculino">Masculino</option>
                                    <option value="Feminino">Feminino</option>
                                    <option value="Não-binário">Não-binário</option>
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="image">Foto</label>
                                <input type="file" id="image" name="image" accept="image/*"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                        </div>
                        
                        <!-- Personalidade e Modelo -->
                        <div class="space-y-4">
                            <h4 class="font-bold">Personalidade e Modelo</h4>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="personality">Personalidade</label>
                                <textarea id="personality" name="personality" required rows="3"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="description">Descrição</label>
                                <textarea id="description" name="description" required rows="3"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="model_id">Modelo de IA</label>
                                <select id="model_id" name="model_id" required
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    <?php foreach ($models as $model): ?>
                                    <option value="<?php echo $model['id']; ?>">
                                        <?php echo htmlspecialchars($model['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <!-- História e Detalhes -->
                        <div class="space-y-4">
                            <h4 class="font-bold">História e Detalhes</h4>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="background_story">História de Fundo</label>
                                <textarea id="background_story" name="background_story" rows="3"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"></textarea>
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="interests">Interesses</label>
                                <input type="text" id="interests" name="interests"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="relationship_status">Estado Civil/Relacionamento</label>
                                <select id="relationship_status" name="relationship_status"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    <option value="">Selecione</option>
                                    <option value="Conhecido">Conhecido</option>
                                    <option value="Amigo próximo (friendzone)">Amigo próximo (friendzone)</option>
                                    <option value="Amigo íntimo (flertam)">Amigo íntimo (flertam)</option>
                                    <option value="Ficante">Ficante</option>
                                    <option value="Namorado">Namorado</option>
                                    <option value="Marido">Marido</option>
                                    <option value="Amante">Amante</option>
                                    <option value="Capacho">Capacho</option>
                                    <option value="Escravo">Escravo</option>
                                    <option value="Dono">Dono</option>
                                    <option value="Solteiro(a)">Solteiro(a)</option>
                                    <option value="Casado(a)">Casado(a)</option>
                                    <option value="Divorciado(a)">Divorciado(a)</option>
                                    <option value="Viúvo(a)">Viúvo(a)</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Profissão e Educação -->
                        <div class="space-y-4">
                            <h4 class="font-bold">Profissão e Educação</h4>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="occupation">Profissão</label>
                                <input type="text" id="occupation" name="occupation"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="education">Formação</label>
                                <input type="text" id="education" name="education"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="languages">Idiomas</label>
                                <input type="text" id="languages" name="languages"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                        </div>
                        
                        <!-- Template do Prompt -->
                        <div class="col-span-2 space-y-4">
                            <h4 class="font-bold">Template do Prompt</h4>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="vulgarity_level">Nível de Vulgaridade</label>
                                <select id="vulgarity_level" name="vulgarity_level" required
                                        class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                                    <option value="muito_alto">Muito Alto</option>
                                    <option value="alto">Alto</option>
                                    <option value="equilibrado" selected>Equilibrado</option>
                                    <option value="baixo">Baixo</option>
                                    <option value="muito_baixo">Muito Baixo</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="prompt_template">
                                    Template para Personalidade da IA
                                </label>
                                <textarea id="prompt_template" name="prompt_template" rows="10"
                                          class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline"><?php echo htmlspecialchars($default_prompt_template); ?></textarea>
                                <p class="text-sm text-gray-500 mt-1">
                                    Use {name}, {age}, {gender}, {personality}, {background_story}, etc. para variáveis.
                                </p>
                            </div>
                        </div>
                        
                        <!-- Campos adicionais -->
                        <div class="space-y-4">
                            <h4 class="font-bold">Campos Adicionais</h4>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="fetish">Fetiche</label>
                                <input type="text" id="fetish" name="fetish"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="conversation_style">Estilo de Conversa</label>
                                <input type="text" id="conversation_style" name="conversation_style"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="shyness_level">Nível de Timidez</label>
                                <input type="range" id="shyness_level" name="shyness_level" min="0" max="100" value="50"
                                    class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="appearance_type">Tipo de Aparência</label>
                                <input type="text" id="appearance_type" name="appearance_type"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="custom_type">Tipo Personalizado</label>
                                <input type="text" id="custom_type" name="custom_type"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="age_range">Faixa Etária</label>
                                <input type="text" id="age_range" name="age_range"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="religion">Religião</label>
                                <input type="text" id="religion" name="religion"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                            
                            <div>
                                <label class="block text-gray-700 text-sm font-bold mb-2" for="profession">Profissão</label>
                                <input type="text" id="profession" name="profession"
                                    class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline">
                            </div>
                        </div>
                        
                        <!-- Status -->
                        <div class="col-span-2">
                            <label class="flex items-center">
                                <input type="checkbox" id="is_active" name="is_active" class="form-checkbox h-5 w-5 text-red-600">
                                <span class="ml-2 text-gray-700">Ativo</span>
                            </label>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end">
                        <button type="button" onclick="hideModal()" class="bg-gray-500 text-white px-4 py-2 rounded mr-2 hover:bg-gray-600">
                            Cancelar
                        </button>
                        <button type="submit" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                            Salvar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showAddModal() {
            document.getElementById('modalTitle').textContent = 'Adicionar Avatar';
            document.getElementById('formAction').value = 'add_avatar';
            document.getElementById('avatarId').value = '';
            document.getElementById('avatarForm').reset();
            document.getElementById('is_active').checked = true;
            document.getElementById('prompt_template').value = <?php echo json_encode($default_prompt_template); ?>;
            document.getElementById('avatarModal').classList.remove('hidden');
        }

        function showEditModal(avatar) {
            document.getElementById('modalTitle').textContent = 'Editar Avatar';
            document.getElementById('formAction').value = 'update_avatar';
            document.getElementById('avatarId').value = avatar.id;
            
            // Preenche todos os campos do formulário
            Object.keys(avatar).forEach(key => {
                const element = document.getElementById(key);
                if (element) {
                    if (element.type === 'checkbox') {
                        element.checked = avatar[key] == 1;
                    } else {
                        element.value = avatar[key] || '';
                    }
                }
            });
            
            document.getElementById('avatarModal').classList.remove('hidden');
        }

        function hideModal() {
            document.getElementById('avatarModal').classList.add('hidden');
        }

        function deleteAvatar(id) {
            if (confirm('Tem certeza que deseja excluir este avatar?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_avatar">
                    <input type="hidden" name="id" value="${id}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html> 