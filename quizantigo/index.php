<?php
session_start();
require_once '../config/database.php';

// Define o número total de steps
$totalSteps = 9;

// Inicializa a sessão do quiz se não existir
if (!isset($_SESSION['quiz_data'])) {
    $_SESSION['quiz_data'] = [];
}

// Busca o modelo IA padrão
$stmt = $pdo->query("SELECT id FROM ai_models WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
$defaultModel = $stmt->fetch();
$defaultModelId = $defaultModel ? $defaultModel['id'] : null;

// Define o template padrão
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
1. Adapte sua linguagem ao estilo de comunicação do usuário. Se ele falar em outra língua, você fará o mesmo. 
2. Se o usuário solicitar fotos, vídeos ou áudios, explique educadamente que esse tipo de conteúdo só está disponível para assinantes dos planos intermediário ou avançado. Sugira que ele faça um upgrade do plano para ter acesso a esses recursos exclusivos.";

// Processa o envio do formulário
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentStep = intval($_POST['step'] ?? 1);
    $data = $_POST;
    unset($data['step']);
    
    // Processa campos customizados
    foreach ($data as $key => $value) {
        if ($value === 'custom' && isset($_POST['custom_' . $key])) {
            $data[$key] = $_POST['custom_' . $key];
        }
    }
    
    // Salva os dados da etapa atual
    $_SESSION['quiz_data'] = array_merge($_SESSION['quiz_data'], $data);
    
    // Se for a última etapa, processa os dados
    if ($currentStep === $totalSteps) {
        try {
            $pdo->beginTransaction();
            
            // Processa o nível de vulgaridade baseado no slider
            $vulgarityLevel = 'equilibrado';
            $vulgarityValue = $_SESSION['quiz_data']['vulgarity_level'] ?? 50;
            if ($vulgarityValue >= 80) $vulgarityLevel = 'muito_alto';
            else if ($vulgarityValue >= 60) $vulgarityLevel = 'alto';
            else if ($vulgarityValue <= 20) $vulgarityLevel = 'muito_baixo';
            else if ($vulgarityValue <= 40) $vulgarityLevel = 'baixo';

            // Processa a idade baseada na faixa etária
            $age = 18;
            $ageRange = $_SESSION['quiz_data']['age_range'] ?? '18-22';
            if ($ageRange === 'custom') {
                $age = intval($_SESSION['quiz_data']['custom_age'] ?? 18);
            } else {
                list($minAge, $maxAge) = explode('-', str_replace('+', '-99', $ageRange));
                $age = rand(intval($minAge), intval($maxAge));
            }
            
            // Busca o prompt visual correspondente
            $appearanceType = $_SESSION['quiz_data']['type'];
            $appearanceNumber = $_SESSION['quiz_data']['appearance'];
            $stmtPrompt = $pdo->prepare("SELECT prompt_text FROM avatar_prompts WHERE type = ? AND appearance_number = ?");
            $stmtPrompt->execute([$appearanceType, $appearanceNumber]);
            $appearancePrompt = $stmtPrompt->fetchColumn();
            // Fallback genérico
            $genericos = [
                'humana' => 'uma mulher jovem, atraente, expressão sedutora, fotorrealista',
                'elfa' => 'uma elfa mágica, cabelos longos, orelhas pontudas, roupa mística, ultra realista',
                'android' => 'uma androide feminina, corpo metálico, olhos brilhantes, cenário futurista'
            ];
            if (!$appearancePrompt) {
                $appearancePrompt = $genericos[$appearanceType] ?? 'avatar feminina, fotorrealista';
            }

            // Insere o avatar no banco de dados
            $stmt = $pdo->prepare("INSERT INTO avatars (
                name, age, gender, personality, description, background_story, 
                interests, relationship_status, occupation, education, languages, 
                prompt_template, vulgarity_level, religion, hobbies, created_by, 
                fetish, conversation_style, shyness_level, type, appearance, 
                appearance_type, custom_type, age_range, model_id, profession, is_custom, image_url, appearance_prompt
            ) VALUES (
                :name, :age, :gender, :personality, :description, :background_story,
                :interests, :relationship_status, :occupation, :education, :languages,
                :prompt_template, :vulgarity_level, :religion, :hobbies, :created_by,
                :fetish, :conversation_style, :shyness_level, :type, :appearance,
                :appearance_type, :custom_type, :age_range, :model_id, :profession, :is_custom, :image_url, :appearance_prompt
            )");
            
            $avatarData = [
                'name' => $_SESSION['quiz_data']['name'],
                'age' => $age,
                'gender' => 'Feminino',
                'personality' => $_SESSION['quiz_data']['behavior'] ?? '',
                'description' => $_SESSION['quiz_data']['type'],
                'background_story' => $_SESSION['quiz_data']['religion'],
                'interests' => $_SESSION['quiz_data']['hobbies'] ?? '',
                'relationship_status' => $_SESSION['quiz_data']['relationship'],
                'occupation' => $_SESSION['quiz_data']['occupation'],
                'education' => '',
                'languages' => 'Português',
                'prompt_template' => $default_prompt_template,
                'vulgarity_level' => $vulgarityLevel,
                'religion' => $_SESSION['quiz_data']['religion'],
                'hobbies' => $_SESSION['quiz_data']['hobbies'] ?? '',
                'created_by' => $_SESSION['user_id'] ?? null,
                'fetish' => $_SESSION['quiz_data']['fetish'],
                'conversation_style' => $_SESSION['quiz_data']['conversation_style'],
                'shyness_level' => $_SESSION['quiz_data']['shyness_level'] ?? 50,
                'type' => $_SESSION['quiz_data']['type'],
                'appearance' => $_SESSION['quiz_data']['appearance'],
                'appearance_type' => $_SESSION['quiz_data']['type'],
                'custom_type' => $_SESSION['quiz_data']['type'] === 'custom' ? 
                    $_SESSION['quiz_data']['custom_type'] : null,
                'age_range' => $_SESSION['quiz_data']['age_range'],
                'model_id' => $defaultModelId,
                'profession' => $_SESSION['quiz_data']['occupation'],
                'is_custom' => 1,
                'image_url' => 'quiz/images/appearance/' . $_SESSION['quiz_data']['type'] . '/' . $_SESSION['quiz_data']['appearance'] . '.png',
                'appearance_prompt' => $appearancePrompt
            ];
            
            $stmt->execute($avatarData);
            $avatarId = $pdo->lastInsertId();
            
            $pdo->commit();
            
            // Redireciona para a página de sucesso
            header('Location: success.php?id=' . $avatarId);
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Erro ao criar avatar: " . $e->getMessage();
        }
    } else {
        // Se não for a última etapa, avança para a próxima
        header('Location: index.php?step=' . ($currentStep + 1));
        exit;
    }
}

// Obtém a etapa atual
$currentStep = min(intval($_GET['step'] ?? 1), $totalSteps);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Crie Sua Crush IA dos Sonhos</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d1a1a 100%);
            min-height: 100vh;
            overflow-x: hidden;
        }
        .step-indicator {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
        }
        .step-indicator.active {
            background: #ef4444;
            color: white;
        }
        .step-indicator.completed {
            background: #22c55e;
            color: white;
        }
        .option-card {
            transition: all 0.3s ease;
            width: 100%;
            position: relative;
            overflow: hidden;
        }
        .option-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }
        .option-card.selected {
            border-color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
        }
        
        .option-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #ef4444;
            transition: all 0.3s ease;
        }
        
        .option-card:hover .option-icon {
            transform: scale(1.1);
        }
        
        .step-icon {
            font-size: 1.5rem;
            margin-right: 0.5rem;
        }
        
        .slider-icon {
            font-size: 1.25rem;
            margin: 0 0.5rem;
        }
        
        .custom-input-icon {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6b7280;
        }
        
        .input-wrapper {
            position: relative;
        }

        /* Ajustes para imagens de aparência */
        .appearance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            padding: 1rem;
        }

        .appearance-image-container {
            position: relative;
            width: 100%;
            padding-top: 150%; /* Proporção 2:3 */
            overflow: hidden;
        }

        .appearance-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 0.5rem;
            transition: all 0.3s ease;
        }

        .appearance-image:hover {
            transform: scale(1.05);
        }
        
        /* Ajustes Mobile */
        @media (max-width: 768px) {
            .container {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
                max-width: 100%;
            }
            
            .step-indicator {
                width: 20px;
                height: 20px;
                font-size: 10px;
            }
            
            .grid {
                grid-template-columns: 1fr;
                gap: 0.75rem;
            }
            
            .option-card {
                margin-bottom: 0.75rem;
                padding: 0.75rem;
            }
            
            input[type="text"],
            input[type="number"],
            textarea {
                width: 100%;
                max-width: 100%;
                font-size: 14px;
                padding: 0.5rem;
            }
            
            .h-1 {
                display: none;
            }

            /* Ajustes para títulos */
            h2 {
                font-size: 1.5rem;
                margin-bottom: 1rem;
            }

            h3 {
                font-size: 1.25rem;
                margin-bottom: 0.75rem;
            }

            /* Ajustes para botões */
            button {
                padding: 0.5rem 1rem;
                font-size: 14px;
            }

            /* Ajustes para imagens de aparência */
            .appearance-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.5rem;
            }

            .appearance-image-container {
                padding-top: 150%; /* Mantém a proporção 2:3 mesmo no mobile */
            }

            .appearance-image {
                object-fit: cover; /* Garante que a imagem cubra todo o espaço */
            }

            /* Ajustes para sliders */
            input[type="range"] {
                width: 100%;
                margin: 0.5rem 0;
            }

            /* Ajustes para barra de progresso */
            .progress-container {
                padding: 0.5rem;
                margin-bottom: 1rem;
            }

            /* Ajustes para campos de texto customizados */
            .custom-input {
                margin-top: 0.5rem;
                font-size: 14px;
            }

            /* Ajustes para botões de navegação */
            .navigation-buttons {
                padding: 0.75rem 0;
                gap: 0.5rem;
            }
        }

        /* Ajustes para tablets */
        @media (min-width: 769px) and (max-width: 1024px) {
            .container {
                max-width: 90%;
            }
            
            .grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .appearance-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        /* Ajustes para barra de progresso */
        .progress-container {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            -ms-overflow-style: none;
            margin-bottom: 1.5rem;
        }
        
        .progress-container::-webkit-scrollbar {
            display: none;
        }
        
        .progress-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            padding: 0.5rem 0;
            position: relative;
        }
        
        .progress-step {
            display: flex;
            align-items: center;
            justify-content: center;
            flex: 1;
            position: relative;
            min-width: 40px;
        }
        
        .progress-step:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translateY(-50%);
            width: calc(100% - 30px);
            height: 2px;
            background-color: #374151;
            z-index: 0;
        }
        
        .progress-step.completed::after {
            background-color: #22c55e;
        }
        
        .step-indicator {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.3s ease;
            flex-shrink: 0;
            z-index: 1;
            position: relative;
        }
        
        /* Ajustes Mobile */
        @media (max-width: 768px) {
            .progress-steps {
                max-width: 100%;
                padding: 0.25rem 0;
            }
            
            .step-indicator {
                width: 24px;
                height: 24px;
                font-size: 12px;
            }
            
            .progress-step {
                min-width: 30px;
            }
            
            .progress-step:not(:last-child)::after {
                width: calc(100% - 24px);
            }
        }
        .step-content {
            display: none;
            animation: fadeIn 0.5s ease;
        }
        .step-content.active {
            display: block;
        }
        .example-image-container {
            width: 100%;
            max-width: 336px;
            margin: 0.5rem auto;
            position: relative;
            padding-top: 150%; /* Proporção 2:3 */
            overflow: hidden;
            border-radius: 0;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        }
        .example-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: contain;
            background-color: rgba(0, 0, 0, 0.1);
            border-radius: 0;
        }
    </style>
</head>
<body class="text-white">
    <div class="container mx-auto px-4 py-4 md:py-8 max-w-4xl">
        <!-- Barra de Progresso -->
        <div class="progress-container">
            <div class="progress-steps">
                <?php for ($i = 1; $i <= $totalSteps; $i++): ?>
                    <div class="progress-step <?php echo $i < $currentStep ? 'completed' : ''; ?>">
                        <div class="step-indicator <?php 
                            echo $i < $currentStep ? 'completed' : ($i == $currentStep ? 'active' : 'bg-gray-700');
                        ?>">
                            <?php echo $i; ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <form method="POST" class="space-y-4 md:space-y-8" id="quizForm">
            <input type="hidden" name="step" value="<?php echo $currentStep; ?>">

            <!-- Etapa 1 - Nome -->
            <?php if ($currentStep == 1): ?>
                <div class="text-center">
                    <h2 class="text-2xl md:text-3xl font-bold mb-4">
                        <i class="fas fa-heart step-icon"></i>
                        Qual será o nome da sua musa dos sonhos?
                    </h2>
                    <p class="text-gray-400 mb-4">Escolha um nome que faça seu coração acelerar...</p>
                    <div class="input-wrapper w-full max-w-md mx-auto mb-4">
                    <input type="text" 
                           name="name" 
                           required 
                               class="w-full px-3 md:px-4 py-2 md:py-3 rounded-lg bg-gray-800 border border-gray-700 focus:border-red-500 focus:ring-1 focus:ring-red-500 pl-12"
                               placeholder="O nome que fará você se apaixonar..."
                           minlength="2"
                           maxlength="100">
                        <i class="fas fa-user custom-input-icon"></i>
                    </div>

                    <!-- Botões de Navegação para Step 1 -->
                    <div class="navigation-buttons flex justify-center mb-4">
                        <button type="submit" 
                                class="px-8 md:px-10 py-2 md:py-3 rounded-lg bg-red-500 hover:bg-red-600 transition duration-300 font-semibold">
                            Próximo
                        </button>
                    </div>

                    <div class="example-image-container">
                        <img src="images/appearance/humana/1.png" alt="Exemplo de IA com aparência humana" class="example-image">
                    </div>
                </div>
            <?php endif; ?>

            <!-- Etapa 2 - Tipo -->
            <?php if ($currentStep == 2): ?>
                <div class="text-center">
                    <h2 class="text-2xl md:text-3xl font-bold mb-4">
                        <i class="fas fa-magic step-icon"></i>
                        Que tipo de deusa vai conquistar seu coração?
                    </h2>
                    <p class="text-gray-400 mb-4">Escolha a personalidade que mais te excita...</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4">
                        <?php
                        $types = [
                            'humana' => [
                                'name' => 'Humana sensual moderna',
                                'icon' => 'fas fa-user', 
                                'description' => 'Uma mulher moderna, confiante e irresistível, que sabe exatamente o que quer'
                            ],
                            'elfa' => [
                                'name' => 'Elfa mágica sedutora',
                                'icon' => 'fas fa-hat-wizard', 
                                'description' => 'Uma criatura mística com encantos sobrenaturais e um toque de mistério'
                            ],
                            'android' => [
                                'name' => 'Android sexy futurista',
                                'icon' => 'fas fa-robot', 
                                'description' => 'Uma máquina perfeita programada para te satisfazer de todas as formas'
                            ]
                        ];
                        foreach ($types as $type => $data):
                        ?>
                            <label class="option-card cursor-pointer p-3 md:p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                                <input type="radio" name="type" value="<?php echo $type; ?>" required class="hidden">
                                <div class="text-center">
                                    <i class="<?php echo $data['icon']; ?> option-icon"></i>
                                    <div class="text-lg md:text-xl font-semibold mb-1"><?php echo $data['name']; ?></div>
                                    <div class="text-sm text-gray-400"><?php echo $data['description']; ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Etapa 3 - Personalidade -->
            <?php if ($currentStep == 3): ?>
                <div class="text-center">
                    <h2 class="text-2xl md:text-3xl font-bold mb-4">
                        <i class="fas fa-star step-icon"></i>
                        Como ela vai te conquistar?
                    </h2>
                    <p class="text-gray-400 mb-4">Escolha a personalidade que mais te excita...</p>
                
                <div class="mb-8">
                        <h3 class="text-xl font-semibold mb-4">
                            <i class="fas fa-praying-hands step-icon"></i>
                            Ela tem alguma crença especial?
                        </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php
                            $religions = [
                                'Católica fervorosa' => ['icon' => 'fas fa-church', 'Uma devota com um lado pecaminoso escondido'],
                                'Evangélica conservadora' => ['icon' => 'fas fa-bible', 'Uma mulher de fé com desejos proibidos'],
                                'Muçulmana recatada' => ['icon' => 'fas fa-mosque', 'Uma joia rara que guarda segredos tentadores'],
                                'Wicca mística' => ['icon' => 'fas fa-magic', 'Uma bruxa que conhece os encantos do amor'],
                                'Espiritualista livre' => ['icon' => 'fas fa-yin-yang', 'Uma alma livre que explora todos os prazeres'],
                                'Ateia provocante' => ['icon' => 'fas fa-atheism', 'Uma mulher que acredita apenas nos prazeres da carne']
                            ];
                            foreach ($religions as $religion => $data):
                            ?>
                                <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                                    <input type="radio" name="religion" value="<?php echo $religion; ?>" required class="hidden">
                                    <div class="text-center">
                                        <i class="<?php echo $data['icon']; ?> option-icon"></i>
                                        <div class="text-xl font-semibold mb-1"><?php echo $religion; ?></div>
                                        <div class="text-sm text-gray-400"><?php echo $data['description']; ?></div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                            <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                                <input type="radio" name="religion" value="custom" class="hidden">
                                <div class="text-center">
                                    <i class="fas fa-star option-icon"></i>
                                    <div class="text-xl font-semibold mb-1">Outra crença</div>
                                    <div class="text-sm text-gray-400 mb-2">Qual é a crença que te excita?</div>
                                    <div class="input-wrapper">
                                        <input type="text" name="custom_religion" placeholder="Sua fantasia mais íntima..." 
                                               class="custom-input w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 pl-10">
                                        <i class="fas fa-pen custom-input-icon"></i>
                                    </div>
                                </div>
                            </label>
                    </div>
                </div>

                <div>
                        <h3 class="text-xl font-semibold mb-4">
                            <i class="fas fa-heart step-icon"></i>
                            Como ela vai te tratar?
                        </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php
                            $behaviors = [
                                'Doce e carinhosa' => ['icon' => 'fas fa-heart', 'Uma amante gentil que sabe como te mimar'],
                                'Dominadora e ousada' => ['icon' => 'fas fa-crown', 'Uma mulher que sabe exatamente o que quer de você'],
                                'Misteriosa e sedutora' => ['icon' => 'fas fa-mask', 'Uma enigma que te deixa louco de desejo'],
                                'Brincalhona e provocante' => ['icon' => 'fas fa-laugh', 'Uma parceira que adora te provocar'],
                                'Dominadora e sarcástica' => ['icon' => 'fas fa-comment-dots', 'Uma mulher que te domina com inteligência'],
                                'Doce e submissa' => ['icon' => 'fas fa-dove', 'Uma companheira que vive para te agradar']
                            ];
                            foreach ($behaviors as $behavior => $data):
                            ?>
                                <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                                    <input type="radio" name="behavior" value="<?php echo $behavior; ?>" required class="hidden">
                                    <div class="text-center">
                                        <i class="<?php echo $data['icon']; ?> option-icon"></i>
                                        <div class="text-xl font-semibold mb-1"><?php echo $behavior; ?></div>
                                        <div class="text-sm text-gray-400"><?php echo $data['description']; ?></div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                            <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                                <input type="radio" name="behavior" value="custom" class="hidden">
                                <div class="text-center">
                                    <i class="fas fa-star option-icon"></i>
                                    <div class="text-xl font-semibold mb-1">Outro comportamento</div>
                                    <div class="text-sm text-gray-400 mb-2">Como você quer ser tratado?</div>
                                    <div class="input-wrapper">
                                        <input type="text" name="custom_behavior" placeholder="Sua fantasia mais íntima..." 
                                               class="custom-input w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 pl-10">
                                        <i class="fas fa-pen custom-input-icon"></i>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Etapa 4 - Estilo de Conversa -->
            <?php if ($currentStep == 4): ?>
                <div class="text-center">
                    <h2 class="text-2xl md:text-3xl font-bold mb-4">
                        <i class="fas fa-comments step-icon"></i>
                        Como ela vai te seduzir com palavras?
                    </h2>
                    <p class="text-gray-400 mb-4">Escolha o estilo que mais te excita...</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                        <?php
                        $conversationStyles = [
                            'Direta e provocante' => [
                                'icon' => 'fas fa-bolt', 
                                'description' => 'Uma mulher que não tem medo de dizer o que quer'
                            ],
                            'Tímida e reservada' => [
                                'icon' => 'fas fa-shy', 
                                'description' => 'Uma joia rara que se revela aos poucos'
                            ],
                            'Romântica e envolvente' => [
                                'icon' => 'fas fa-heart', 
                                'description' => 'Uma poetisa do amor que te encanta'
                            ],
                            'Misteriosa e intensa' => [
                                'icon' => 'fas fa-mask', 
                                'description' => 'Uma sedutora que te mantém curioso'
                            ],
                            'Sem filtro e ousada' => [
                                'icon' => 'fas fa-fire', 
                                'description' => 'Uma mulher que não tem limites na sedução'
                            ]
                        ];
                        foreach ($conversationStyles as $style => $data):
                        ?>
                            <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                                <input type="radio" name="conversation_style" value="<?php echo $style; ?>" required class="hidden">
                                <div class="text-center">
                                    <i class="<?php echo $data['icon']; ?> option-icon"></i>
                                    <div class="text-xl font-semibold mb-1"><?php echo $style; ?></div>
                                    <div class="text-sm text-gray-400"><?php echo $data['description']; ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="space-y-6">
                        <div>
                                <label class="block text-xl font-semibold mb-2">
                                    <i class="fas fa-angel step-icon"></i>
                                    Quão tímida ela é?
                                </label>
                                <p class="text-sm text-gray-400 mb-2">De inocente a ousada, escolha o nível que te excita...</p>
                                <div class="flex items-center">
                                    <i class="fas fa-angel slider-icon"></i>
                                <input type="range" name="shyness_level" min="0" max="100" value="50" 
                                       class="w-full h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer">
                                    <i class="fas fa-devil slider-icon"></i>
                                </div>
                                <div class="flex justify-between text-sm text-gray-400 mt-1">
                                    <span>Inocente</span>
                                    <span>Ousada</span>
                            </div>
                        </div>

                        <div>
                                <label class="block text-xl font-semibold mb-2">
                                    <i class="fas fa-fire step-icon"></i>
                                    Nível de vulgaridade
                                </label>
                                <p class="text-sm text-gray-400 mb-2">De recatada a provocante, defina o nível que te deixa louco...</p>
                                <div class="flex items-center">
                                    <i class="fas fa-dove slider-icon"></i>
                                <input type="range" name="vulgarity_level" min="0" max="100" value="50" 
                                       class="w-full h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer">
                                    <i class="fas fa-fire slider-icon"></i>
                                </div>
                                <div class="flex justify-between text-sm text-gray-400 mt-1">
                                    <span>Recatada</span>
                                    <span>Provocante</span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Etapa 5 - Profissão -->
            <?php if ($currentStep == 5): ?>
                <div class="text-center">
                    <h2 class="text-2xl md:text-3xl font-bold mb-4">
                        <i class="fas fa-briefcase step-icon"></i>
                        Qual é o segredo profissional dela?
                    </h2>
                    <p class="text-gray-400 mb-4">Escolha a ocupação que mais te excita...</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php
                        $occupations = [
                            'Personal trainer' => ['icon' => 'fas fa-dumbbell', 'Uma instrutora que sabe como te dominar'],
                            'Enfermeira sensual' => ['icon' => 'fas fa-heartbeat', 'Uma cuidadora com um lado proibido'],
                            'Feiticeira encantadora' => ['icon' => 'fas fa-magic', 'Uma maga que conhece os segredos do prazer'],
                            'Influencer provocante' => ['icon' => 'fas fa-camera', 'Uma estrela que adora ser o centro das atenções'],
                            'Hacker perigosa' => ['icon' => 'fas fa-laptop-code', 'Uma mulher que invade sua privacidade de forma sedutora'],
                            'Escritora erótica' => ['icon' => 'fas fa-pen-fancy', 'Uma artista que transforma fantasias em realidade']
                        ];
                        foreach ($occupations as $occupation => $data):
                        ?>
                            <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                                <input type="radio" name="occupation" value="<?php echo $occupation; ?>" required class="hidden">
                                <div class="text-center">
                                    <i class="<?php echo $data['icon']; ?> option-icon"></i>
                                    <div class="text-xl font-semibold mb-1"><?php echo $occupation; ?></div>
                                    <div class="text-sm text-gray-400"><?php echo $data['description']; ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                        <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                            <input type="radio" name="occupation" value="custom" class="hidden">
                            <div class="text-center">
                                <i class="fas fa-star option-icon"></i>
                                <div class="text-xl font-semibold mb-1">Outra profissão</div>
                                <div class="text-sm text-gray-400 mb-2">Qual é a ocupação dos seus sonhos?</div>
                                <div class="input-wrapper">
                                    <input type="text" name="custom_occupation" placeholder="Sua fantasia profissional..." 
                                           class="custom-input w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 pl-10">
                                    <i class="fas fa-pen custom-input-icon"></i>
                                </div>
                            </div>
                        </label>
                </div>
                
                <div class="mt-8">
                        <label class="block text-xl font-semibold mb-4">
                            <i class="fas fa-heart step-icon"></i>
                            Quais são os hobbies secretos dela?
                        </label>
                        <p class="text-sm text-gray-400 mb-2">Descreva as atividades que a deixam mais excitante...</p>
                        <div class="input-wrapper w-full max-w-md mx-auto">
                        <textarea name="hobbies" rows="3" 
                                      class="w-full px-4 py-3 rounded-lg bg-gray-800 border border-gray-700 focus:border-red-500 focus:ring-1 focus:ring-red-500 pl-12"
                                      placeholder="As atividades que a tornam ainda mais irresistível..."></textarea>
                            <i class="fas fa-heart custom-input-icon"></i>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Etapa 6 - Idade -->
            <?php if ($currentStep == 6): ?>
                <div class="text-center">
                    <h2 class="text-2xl md:text-3xl font-bold mb-4">
                        <i class="fas fa-hourglass-half step-icon"></i>
                        Que idade tem a mulher dos seus sonhos?
                    </h2>
                    <p class="text-gray-400 mb-4">Escolha a faixa etária que mais te excita...</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php
                        $ageRanges = [
                            '18-22' => ['icon' => 'fas fa-heart', 'Uma jovem cheia de energia e curiosidade'],
                            '23-27' => ['icon' => 'fas fa-star', 'Uma mulher que conhece seus desejos'],
                            '28-35' => ['icon' => 'fas fa-crown', 'Uma mulher madura que sabe o que quer'],
                            '35+' => ['icon' => 'fas fa-gem', 'Uma mulher experiente que domina a arte da sedução']
                        ];
                        foreach ($ageRanges as $value => $data):
                        ?>
                            <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                                <input type="radio" name="age_range" value="<?php echo $value; ?>" required class="hidden">
                                <div class="text-center">
                                    <i class="<?php echo $data['icon']; ?> option-icon"></i>
                                    <div class="text-xl font-semibold mb-1"><?php echo str_replace('-', '–', $value); ?> anos</div>
                                    <div class="text-sm text-gray-400"><?php echo $data['description']; ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                        <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                            <input type="radio" name="age_range" value="custom" class="hidden">
                            <div class="text-center">
                                <i class="fas fa-star option-icon"></i>
                                <div class="text-xl font-semibold mb-1">Outra idade</div>
                                <div class="text-sm text-gray-400 mb-2">Qual é a idade que te deixa louco?</div>
                                <div class="input-wrapper">
                                <input type="number" name="custom_age" min="18" 
                                           class="custom-input w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 pl-10"
                                           placeholder="A idade que te excita...">
                                    <i class="fas fa-pen custom-input-icon"></i>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Etapa 7 - Fetiche -->
            <?php if ($currentStep == 7): ?>
                <div class="text-center">
                    <h2 class="text-2xl md:text-3xl font-bold mb-4">
                        <i class="fas fa-fire step-icon"></i>
                        Quais são os desejos secretos dela?
                    </h2>
                    <p class="text-gray-400 mb-4">Escolha o fetiche que mais te excita...</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php
                        $fetishes = [
                            'Exibicionismo Discreto' => ['icon' => 'fas fa-eye', 'Uma mulher que adora ser observada discretamente'],
                            'Cosplay Erótico' => ['icon' => 'fas fa-theater-masks', 'Uma mulher que se transforma em suas fantasias mais secretas'],
                            'BDSM Leve' => ['icon' => 'fas fa-handcuffs', 'Uma mulher que gosta de jogos de poder e dominação'],
                            'Cuckold Secreto' => ['icon' => 'fas fa-mask', 'Uma mulher que adora ter segredos e aventuras proibidas'],
                            'Voyeurismo Sedutor' => ['icon' => 'fas fa-binoculars', 'Uma mulher que adora observar e ser observada'],
                            'BBC Fetich' => ['icon' => 'fas fa-male', 'Uma mulher que tem preferências específicas por homens negros'],
                            'Swing' => ['icon' => 'fas fa-users', 'Uma mulher que gosta de compartilhar experiências com outros casais'],
                            'Não, ela é mais tradicional' => ['icon' => 'fas fa-heart', 'Uma mulher que prefere o amor mais convencional']
                        ];
                        foreach ($fetishes as $fetish => $data):
                        ?>
                            <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                                <input type="radio" name="fetish" value="<?php echo $fetish; ?>" required class="hidden">
                                <div class="text-center">
                                    <i class="<?php echo $data['icon']; ?> option-icon"></i>
                                    <div class="text-xl font-semibold mb-1"><?php echo $fetish; ?></div>
                                    <div class="text-sm text-gray-400"><?php echo $data['description']; ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                        <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                            <input type="radio" name="fetish" value="custom" class="hidden">
                            <div class="text-center">
                                <i class="fas fa-star option-icon"></i>
                                <div class="text-xl font-semibold mb-1">Outro fetiche</div>
                                <div class="text-sm text-gray-400 mb-2">Qual é o desejo secreto que te excita?</div>
                                <div class="input-wrapper">
                                    <input type="text" name="custom_fetish" placeholder="Sua fantasia mais íntima..." 
                                           class="custom-input w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 pl-10">
                                    <i class="fas fa-pen custom-input-icon"></i>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Etapa 8 - Relação -->
            <?php if ($currentStep == 8): ?>
                <div class="text-center">
                    <h2 class="text-2xl md:text-3xl font-bold mb-4">
                        <i class="fas fa-heart step-icon"></i>
                        Qual é o seu papel na vida dela?
                    </h2>
                    <p class="text-gray-400 mb-4">Escolha o tipo de relação que mais te excita...</p>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php
                        $relationships = [
                            'Conhecido' => ['icon' => 'fas fa-user', 'Um estranho que ela quer conhecer melhor'],
                            'Amigo próximo (friendzone)' => ['icon' => 'fas fa-user-friends', 'Um amigo que ela mantém por perto'],
                            'Amigo íntimo (flertam)' => ['icon' => 'fas fa-heart', 'Um amigo com quem ela flerta'],
                            'Ficante' => ['icon' => 'fas fa-fire', 'Um parceiro casual que a excita'],
                            'Namorado' => ['icon' => 'fas fa-heart', 'O amor da vida dela'],
                            'Marido' => ['icon' => 'fas fa-ring', 'O homem que ela escolheu para sempre'],
                            'Amante' => ['icon' => 'fas fa-mask', 'O segredo proibido dela'],
                            'Capacho' => ['icon' => 'fas fa-shoe-prints', 'Alguém que vive para servi-la'],
                            'Escravo' => ['icon' => 'fas fa-chain', 'Alguém que obedece seus comandos'],
                            'Dono' => ['icon' => 'fas fa-crown', 'O homem que a controla']
                        ];
                        foreach ($relationships as $relationship => $data):
                        ?>
                            <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                                <input type="radio" name="relationship" value="<?php echo $relationship; ?>" required class="hidden">
                                <div class="text-center">
                                    <i class="<?php echo $data['icon']; ?> option-icon"></i>
                                    <div class="text-xl font-semibold mb-1"><?php echo $relationship; ?></div>
                                    <div class="text-sm text-gray-400"><?php echo $data['description']; ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                        <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                            <input type="radio" name="relationship" value="custom" class="hidden">
                            <div class="text-center">
                                <i class="fas fa-star option-icon"></i>
                                <div class="text-xl font-semibold mb-1">Outra relação</div>
                                <div class="text-sm text-gray-400 mb-2">Qual é o tipo de relação que te excita?</div>
                                <div class="input-wrapper">
                                    <input type="text" name="custom_relationship" placeholder="Sua fantasia de relacionamento..." 
                                           class="custom-input w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 pl-10">
                                    <i class="fas fa-pen custom-input-icon"></i>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Etapa 9 - Aparência -->
            <?php if ($currentStep == 9): ?>
                <div class="text-center">
                    <h2 class="text-2xl md:text-3xl font-bold mb-4">
                        <i class="fas fa-image step-icon"></i>
                        Escolha o visual que te deixa louco
                    </h2>
                    <p class="text-gray-400 mb-4">Selecione a aparência que mais te excita...</p>
                    <div class="appearance-grid">
                        <?php
                        // Obtém o tipo escolhido na Step 2
                        $selectedType = $_SESSION['quiz_data']['type'] ?? 'humana';
                        
                        // Define as imagens disponíveis para cada tipo
                        $appearanceImages = [
                            'humana' => range(1, 15),
                            'elfa' => range(1, 13),
                            'android' => range(1, 10)
                        ];
                        
                        // Obtém as imagens disponíveis para o tipo selecionado
                        $availableImages = $appearanceImages[$selectedType] ?? $appearanceImages['humana'];
                        
                        foreach ($availableImages as $imageNumber):
                        ?>
                            <label class="option-card cursor-pointer">
                                <input type="radio" name="appearance" value="<?php echo $imageNumber; ?>" required class="hidden">
                                <div class="appearance-image-container">
                                    <img src="images/appearance/<?php echo $selectedType; ?>/<?php echo $imageNumber; ?>.png" 
                                         alt="Aparência <?php echo $imageNumber; ?>" 
                                         class="appearance-image"
                                         onclick="openImageModal(this.src)">
                                    <div class="absolute inset-0 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                                        <i class="fas fa-heart text-4xl text-red-500"></i>
                                    </div>
                                    <div class="absolute inset-0 border-4 border-transparent rounded-lg transition-all duration-300 selected-overlay"></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
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
            <?php endif; ?>

            <!-- Botões de Navegação para Steps 2-9 -->
                <?php if ($currentStep > 1): ?>
                <div class="navigation-buttons flex justify-between mt-8">
                    <button type="button" onclick="window.location.href='?step=<?php echo $currentStep - 1; ?>'"
                            class="px-4 md:px-6 py-2 md:py-3 rounded-lg bg-gray-700 hover:bg-gray-600 transition duration-300">
                        Anterior
                    </button>
                <button type="submit" 
                        class="px-4 md:px-6 py-2 md:py-3 rounded-lg bg-red-500 hover:bg-red-600 transition duration-300">
                    <?php echo $currentStep === $totalSteps ? 'Finalizar' : 'Próximo'; ?>
                </button>
            </div>
            <?php endif; ?>
        </form>
    </div>

    <script>
        // Adiciona efeito de seleção nas opções
        document.querySelectorAll('.option-card').forEach(card => {
            const input = card.querySelector('input[type="radio"]');
            const customInput = card.querySelector('input[type="text"], input[type="number"]');
            
            if (customInput) {
                customInput.addEventListener('click', (e) => {
                    e.stopPropagation();
                    input.checked = true;
                    updateCardSelection(card);
                });
                
                customInput.addEventListener('input', (e) => {
                    input.value = 'custom';
                    updateCardSelection(card);
                });
            }
            
            card.addEventListener('click', function() {
                input.checked = true;
                updateCardSelection(this);
            });
        });

        function updateCardSelection(selectedCard) {
            const groupName = selectedCard.querySelector('input[type="radio"]').name;
            document.querySelectorAll(`input[name="${groupName}"]`).forEach(input => {
                const card = input.closest('.option-card');
                card.classList.remove('selected');
                const overlay = card.querySelector('.selected-overlay');
                if (overlay) {
                    overlay.classList.remove('border-red-500', 'bg-red-500', 'bg-opacity-20');
                }
            });
            selectedCard.classList.add('selected');
            const overlay = selectedCard.querySelector('.selected-overlay');
            if (overlay) {
                overlay.classList.add('border-red-500', 'bg-red-500', 'bg-opacity-20');
            }
        }

        // Atualiza o valor dos sliders
        document.querySelectorAll('input[type="range"]').forEach(slider => {
            slider.addEventListener('input', function() {
                const value = this.value;
                const min = this.min;
                const max = this.max;
                const percentage = ((value - min) / (max - min)) * 100;
                this.style.background = `linear-gradient(to right, #ef4444 ${percentage}%, #374151 ${percentage}%)`;
            });
        });

        // Validação do formulário
        document.getElementById('quizForm').addEventListener('submit', function(e) {
            const currentStep = parseInt(document.querySelector('input[name="step"]').value);
            let isValid = true;

            // Validação específica para a step 1 (nome)
            if (currentStep === 1) {
                const nameInput = document.querySelector('input[name="name"]');
                if (!nameInput.value.trim()) {
                    isValid = false;
                    showMobileAlert('Por favor, digite um nome para sua crush.');
                }
            } else {
                // Validação para outras steps com radio buttons
                const radioGroups = this.querySelectorAll('input[type="radio"]');
                let checkedRadios = new Set();

                radioGroups.forEach(radio => {
                    if (radio.checked) {
                        checkedRadios.add(radio.name);
                        if (radio.value === 'custom') {
                            const customInput = radio.parentElement.querySelector('input[type="text"], input[type="number"]');
                            if (!customInput.value.trim()) {
                                isValid = false;
                                showMobileAlert('Por favor, preencha o campo personalizado.');
                            }
                        }
                    }
                });

                if (checkedRadios.size === 0) {
                    isValid = false;
                    showMobileAlert('Por favor, selecione uma opção.');
                }
            }

            if (!isValid) {
                e.preventDefault();
            }
        });

        // Função para mostrar alerta mobile
        function showMobileAlert(message) {
            const alertDiv = document.createElement('div');
            alertDiv.className = 'fixed bottom-4 left-1/2 transform -translate-x-1/2 bg-red-500 text-white px-4 py-2 rounded-lg shadow-lg z-50';
            alertDiv.textContent = message;
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }

        // Ajusta o tamanho dos elementos baseado na largura da tela
        function adjustElementsForScreenSize() {
            const isMobile = window.innerWidth <= 768;
            const container = document.querySelector('.container');
            
            if (isMobile) {
                container.classList.add('mobile-view');
            } else {
                container.classList.remove('mobile-view');
            }
        }

        // Executa na carga e no redimensionamento
        window.addEventListener('load', adjustElementsForScreenSize);
        window.addEventListener('resize', adjustElementsForScreenSize);

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