<?php
session_start();
require_once 'config/database.php';
require_once 'includes/ImageGenerator.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Buscar modelos ativos
$stmt = $pdo->query("SELECT * FROM ai_models WHERE is_active = 1 ORDER BY name");
$models = $stmt->fetchAll();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $age = (int)$_POST['age'];
    $gender = $_POST['gender'];
    $personality = trim($_POST['personality']);
    $description = trim($_POST['description']);
    $background_story = trim($_POST['background_story']);
    $interests = trim($_POST['interests']);
    $relationship_status = trim($_POST['relationship_status']);
    $occupation = trim($_POST['occupation']);
    $education = trim($_POST['education']);
    $languages = trim($_POST['languages']);
    $model_id = $_POST['model_id'] ?? null;
    $vulgarity_level = $_POST['vulgarity_level'] ?? 'equilibrado';
    $prompt_template = trim($_POST['prompt_template']);
    
    // Validações básicas
    if (empty($name) || empty($personality) || empty($description)) {
        $error = 'Por favor, preencha todos os campos obrigatórios.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO avatars (
                    name, age, gender, personality, description, 
                    background_story, interests, relationship_status, 
                    occupation, education, languages, model_id, 
                    prompt_template, vulgarity_level, created_by, is_custom
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, TRUE)
            ");
            
            $stmt->execute([
                $name, $age, $gender, $personality, $description,
                $background_story, $interests, $relationship_status,
                $occupation, $education, $languages, $model_id,
                $prompt_template, $vulgarity_level, $_SESSION['user_id']
            ]);
            
            $avatarId = $pdo->lastInsertId();

            // Gera a imagem do avatar
            $imageGenerator = new ImageGenerator();
            $imageFilename = $imageGenerator->generateAvatarImage([
                'age' => $age,
                'gender' => $gender,
                'personality' => $personality,
                'occupation' => $occupation
            ]);

            // Atualiza o avatar com o nome do arquivo da imagem
            $stmt = $pdo->prepare("UPDATE avatars SET image_url = ? WHERE id = ?");
            $stmt->execute([$imageFilename, $avatarId]);

            $pdo->commit();
            $_SESSION['success'] = "Avatar criado com sucesso!";
            header("Location: avatars.php");
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error'] = "Erro ao criar avatar: " . $e->getMessage();
        }
    }
}

// Template padrão
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
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criar Avatar Personalizado - DesireChat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <?php include 'header.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <div class="max-w-2xl mx-auto">
            <h1 class="text-3xl font-bold mb-8 text-center">Criar Avatar Personalizado</h1>

            <?php if ($error): ?>
                <div class="bg-red-500 text-white p-4 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-500 text-white p-4 rounded mb-4">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6">
                <div>
                    <label for="name" class="block text-sm font-medium mb-2">Nome do Avatar *</label>
                    <input type="text" id="name" name="name" required
                           class="w-full px-4 py-2 rounded bg-gray-800 border border-gray-700 focus:border-red-500 focus:ring-1 focus:ring-red-500">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="age" class="block text-sm font-medium mb-2">Idade *</label>
                        <input type="number" id="age" name="age" min="18" max="100" required
                               class="w-full px-4 py-2 rounded bg-gray-800 border border-gray-700 focus:border-red-500 focus:ring-1 focus:ring-red-500">
                    </div>

                    <div>
                        <label for="gender" class="block text-sm font-medium mb-2">Gênero *</label>
                        <select id="gender" name="gender" required
                                class="w-full px-4 py-2 rounded bg-gray-800 border border-gray-700 focus:border-red-500 focus:ring-1 focus:ring-red-500">
                            <option value="feminino">Feminino</option>
                            <option value="masculino">Masculino</option>
                            <option value="não-binário">Não-binário</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="personality" class="block text-sm font-medium mb-2">Personalidade *</label>
                    <textarea id="personality" name="personality" rows="3" required
                              class="w-full px-4 py-2 rounded bg-gray-800 border border-gray-700 focus:border-red-500 focus:ring-1 focus:ring-red-500"
                              placeholder="Descreva a personalidade do avatar..."></textarea>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium mb-2">Descrição *</label>
                    <textarea id="description" name="description" rows="3" required
                              class="w-full px-4 py-2 rounded bg-gray-800 border border-gray-700 focus:border-red-500 focus:ring-1 focus:ring-red-500"
                              placeholder="Descreva a aparência e características do avatar..."></textarea>
                </div>

                <div>
                    <label for="background_story" class="block text-sm font-medium mb-2">História de Fundo</label>
                    <textarea id="background_story" name="background_story" rows="3"
                              class="w-full px-4 py-2 rounded bg-gray-800 border border-gray-700 focus:border-red-500 focus:ring-1 focus:ring-red-500"
                              placeholder="Descreva a história de fundo do avatar..."></textarea>
                </div>

                <div>
                    <label for="interests" class="block text-sm font-medium mb-2">Interesses</label>
                    <textarea id="interests" name="interests" rows="2"
                              class="w-full px-4 py-2 rounded bg-gray-800 border border-gray-700 focus:border-red-500 focus:ring-1 focus:ring-red-500"
                              placeholder="Liste os interesses do avatar..."></textarea>
                </div>

                <div>
                    <label for="relationship_status" class="block text-sm font-medium mb-2">Estado Civil</label>
                    <input type="text" id="relationship_status" name="relationship_status"
                           class="w-full px-4 py-2 rounded bg-gray-800 border border-gray-700 focus:border-red-500 focus:ring-1 focus:ring-red-500">
                </div>

                <div>
                    <label for="occupation" class="block text-sm font-medium mb-2">Ocupação</label>
                    <input type="text" id="occupation" name="occupation"
                           class="w-full px-4 py-2 rounded bg-gray-800 border border-gray-700 focus:border-red-500 focus:ring-1 focus:ring-red-500">
                </div>

                <div>
                    <label for="education" class="block text-sm font-medium mb-2">Educação</label>
                    <input type="text" id="education" name="education"
                           class="w-full px-4 py-2 rounded bg-gray-800 border border-gray-700 focus:border-red-500 focus:ring-1 focus:ring-red-500">
                </div>

                <div>
                    <label for="languages" class="block text-sm font-medium mb-2">Idiomas</label>
                    <input type="text" id="languages" name="languages"
                           class="w-full px-4 py-2 rounded bg-gray-800 border border-gray-700 focus:border-red-500 focus:ring-1 focus:ring-red-500">
                </div>

                <div>
                    <label for="model_id" class="block text-sm font-medium mb-2">Modelo de IA</label>
                    <select id="model_id" name="model_id"
                            class="w-full px-4 py-2 rounded bg-gray-800 border border-gray-700 focus:border-red-500 focus:ring-1 focus:ring-red-500">
                        <option value="">Selecione um modelo</option>
                        <?php foreach ($models as $model): ?>
                            <option value="<?php echo $model['id']; ?>"><?php echo htmlspecialchars($model['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label for="vulgarity_level" class="block text-sm font-medium mb-2">Nível de Vulgaridade *</label>
                    <select id="vulgarity_level" name="vulgarity_level" required
                            class="w-full px-4 py-2 rounded bg-gray-800 border border-gray-700 focus:border-red-500 focus:ring-1 focus:ring-red-500">
                        <option value="muito_alto">Muito Alto</option>
                        <option value="alto">Alto</option>
                        <option value="equilibrado" selected>Equilibrado</option>
                        <option value="baixo">Baixo</option>
                        <option value="muito_baixo">Muito Baixo</option>
                    </select>
                </div>

                <div>
                    <label for="prompt_template" class="block text-sm font-medium mb-2">Template de Prompt</label>
                    <textarea id="prompt_template" name="prompt_template" rows="10"
                              class="w-full px-4 py-2 rounded bg-gray-800 border border-gray-700 focus:border-red-500 focus:ring-1 focus:ring-red-500"
                              placeholder="Template de prompt para o modelo de IA..."><?php echo htmlspecialchars($default_prompt_template); ?></textarea>
                </div>

                <div class="text-center">
                    <button type="submit" class="bg-red-500 text-white px-6 py-3 rounded hover:bg-red-600 transition duration-300">
                        Criar Avatar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html> 