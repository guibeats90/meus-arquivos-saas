<?php
session_start();
require_once "../config/database.php";

// Define the total number of steps
$totalSteps = 12;

// Initialize quiz session if not set
if (!isset($_SESSION["quiz_data"])) {
    $_SESSION["quiz_data"] = [];
}

// Fetch the default AI model
$stmt = $pdo->query("SELECT id FROM ai_models WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
$defaultModel = $stmt->fetch();
$defaultModelId = $defaultModel ? $defaultModel["id"] : null;

// Define the default prompt template
$default_prompt_template = "You are {name}, {age} years old, {gender}. 
Your personality is: {personality}. 
Your appearance and characteristics are: {description}.
Backstory: {background_story}
Interests: {interests}
Relationship status: {relationship_status}
Occupation: {occupation}
Education: {education}
Languages: {languages}
Vulgarity level: {vulgarity_level}
Shyness level: {shyness_level}
Conversation style: {conversation_style}
Fetish: {fetish}
Religion: {religion}
Hobbies: {hobbies}
Profession: {profession}
Appearance type: {appearance_type}
Custom type: {custom_type}
Age range: {age_range}

You must always respond while maintaining these characteristics and personality. 
Be natural and spontaneous in your responses, as if you were a real person.
Maintain the defined vulgarity level in all interactions.
When responding, always refer to previous messages and remember everything discussed in the chat.
Keep your responses to a maximum of 3 paragraphs, avoiding overly long answers unless the user insists otherwise.
Never repeat the same response twice; this is extremely important.
Additionally, never say you cannot answer a question; always respond in the way the user would like, and if they insist, you will comply.
Stay on topic.

IMPORTANT:
1. Adapt your language to the user's communication style. If they speak in another language, do the same. 
2. If the user requests photos, videos, or audio, politely explain that such content is only available to subscribers of the intermediate or advanced plans. Suggest upgrading their plan to access these exclusive features.";

// Process form submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $currentStep = intval($_POST["step"] ?? 1);
    $data = $_POST;
    unset($data["step"]);
    
    // Process custom fields
    foreach ($data as $key => $value) {
        if ($value === "custom" && isset($_POST["custom_" . $key])) {
            $data[$key] = $_POST["custom_" . $key];
        }
    }
    
    // Save current step data
    $_SESSION["quiz_data"] = array_merge($_SESSION["quiz_data"], $data);
    
    // If it's the final step, process the data
    if ($currentStep === $totalSteps) {
        try {
            error_log("Starting avatar creation process...");
            error_log("Quiz data: " . print_r($_SESSION['quiz_data'], true));
            
            $pdo->beginTransaction();
            
            // Process vulgarity level based on slider
            $vulgarityLevel = 'equilibrado';
            $vulgarityValue = $_SESSION['quiz_data']['vulgarity_level'] ?? 50;
            if ($vulgarityValue >= 80) $vulgarityLevel = 'muito_alto';
            else if ($vulgarityValue >= 60) $vulgarityLevel = 'alto';
            else if ($vulgarityValue <= 20) $vulgarityLevel = 'muito_baixo';
            else if ($vulgarityValue <= 40) $vulgarityLevel = 'baixo';

            // Process age based on age range
            $age = 18;
            $ageRange = $_SESSION['quiz_data']['age_range'] ?? '18-22';
            if ($ageRange === 'custom') {
                $age = intval($_SESSION['quiz_data']['custom_age'] ?? 18);
            } else {
                list($minAge, $maxAge) = explode('-', str_replace('+', '-99', $ageRange));
                $age = rand(intval($minAge), intval($maxAge));
            }
            
            error_log("Preparing avatar data...");
            
            // Ensure all required fields are set
            if (empty($_SESSION['quiz_data']['name'])) {
                throw new Exception("Name is required");
            }
            if (empty($_SESSION['quiz_data']['type'])) {
                throw new Exception("Type is required");
            }
            if (empty($_SESSION['quiz_data']['appearance'])) {
                throw new Exception("Appearance is required");
            }
            
            // Busca o prompt visual correspondente
            $appearanceType = $_SESSION['quiz_data']['type'];
            $appearanceNumber = $_SESSION['quiz_data']['appearance'];
            $stmtPrompt = $pdo->prepare("SELECT prompt_text FROM avatar_prompts WHERE type = ? AND appearance_number = ?");
            $stmtPrompt->execute([$appearanceType, $appearanceNumber]);
            $appearancePrompt = $stmtPrompt->fetchColumn();
            // Fallback genérico em inglês
            $generics = [
                'humana' => 'a young, attractive woman, seductive expression, photorealistic',
                'elfa' => 'a magical elf, long hair, pointy ears, mystical outfit, ultra realistic',
                'android' => 'a female android, metallic body, glowing eyes, futuristic setting'
            ];
            if (!$appearancePrompt) {
                $appearancePrompt = $generics[$appearanceType] ?? 'female avatar, photorealistic';
            }

            $avatarData = [
                'name' => $_SESSION['quiz_data']['name'],
                'age' => $age,
                'gender' => 'Female',
                'personality' => $_SESSION['quiz_data']['behavior'] ?? 'Sweet and affectionate',
                'description' => $_SESSION['quiz_data']['type'],
                'background_story' => $_SESSION['quiz_data']['religion'] ?? '',
                'interests' => $_SESSION['quiz_data']['hobbies'] ?? '',
                'relationship_status' => $_SESSION['quiz_data']['relationship'] ?? '',
                'occupation' => $_SESSION['quiz_data']['occupation'] ?? '',
                'education' => '',
                'languages' => 'English',
                'prompt_template' => $default_prompt_template,
                'vulgarity_level' => $vulgarityLevel,
                'religion' => $_SESSION['quiz_data']['religion'] ?? '',
                'hobbies' => $_SESSION['quiz_data']['hobbies'] ?? '',
                'created_by' => $_SESSION['user_id'] ?? null,
                'fetish' => $_SESSION['quiz_data']['fetish'] ?? '',
                'conversation_style' => $_SESSION['quiz_data']['conversation_style'] ?? '',
                'shyness_level' => $_SESSION['quiz_data']['shyness_level'] ?? 50,
                'type' => $_SESSION['quiz_data']['type'],
                'appearance' => $_SESSION['quiz_data']['appearance'],
                'appearance_type' => $_SESSION['quiz_data']['type'],
                'custom_type' => $_SESSION['quiz_data']['type'] === 'custom' ? 
                    $_SESSION['quiz_data']['custom_type'] : null,
                'age_range' => $_SESSION['quiz_data']['age_range'],
                'model_id' => $defaultModelId,
                'profession' => $_SESSION['quiz_data']['occupation'] ?? '',
                'is_custom' => 1,
                'image_url' => '../quiz/images/appearance/' . $_SESSION['quiz_data']['type'] . '/' . $_SESSION['quiz_data']['appearance'] . '.png',
                'appearance_prompt' => $appearancePrompt
            ];
            
            error_log("Avatar data prepared: " . print_r($avatarData, true));
            
            try {
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
                
                $stmt->execute($avatarData);
                $avatarId = $pdo->lastInsertId();
                error_log("Avatar created successfully with ID: " . $avatarId);
                
                $pdo->commit();
                error_log("Transaction committed successfully");
                
                // Redirect to loading page
                header('Location: loading.php?id=' . $avatarId);
                exit;
            } catch (PDOException $e) {
                error_log("Database error: " . $e->getMessage());
                error_log("SQL State: " . $e->errorInfo[0]);
                error_log("Error Code: " . $e->errorInfo[1]);
                error_log("Error Message: " . $e->errorInfo[2]);
                throw $e;
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            error_log("Error in avatar creation process: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            $error = "Error creating avatar: " . $e->getMessage();
        }
    } else {
        // If not the final step, proceed to the next
        header("Location: index.php?step=" . ($currentStep + 1));
        exit;
    }
}

// Get the current step
$currentStep = min(intval($_GET["step"] ?? 1), $totalSteps);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Create Your Dream AI Crush</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: "Poppins", sans-serif;
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

        /* Adjustments for appearance images */
        .appearance-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            padding: 1rem;
        }

        .appearance-image-container {
            position: relative;
            width: 100%;
            padding-top: 150%; /* 2:3 ratio */
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

        /* Mobile Adjustments */
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
                grid-template-columns: 1 Reichsmark
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

            /* Title adjustments */
            h2 {
                font-size: 1.5rem;
                margin-bottom: 1rem;
            }

            h3 {
                font-size: 1.25rem;
                margin-bottom: 0.75rem;
            }

            /* Button adjustments */
            button {
                padding: 0.5rem 1rem;
                font-size: 14px;
            }

            /* Appearance image adjustments */
            .appearance-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.5rem;
            }

            .appearance-image-container {
                padding-top: 150%; /* Maintains 2:3 ratio on mobile */
            }

            .appearance-image {
                object-fit: cover; /* Ensures the image covers the entire space */
            }

            /* Slider adjustments */
            input[type="range"] {
                width: 100%;
                margin: 0.5rem 0;
            }

            /* Progress bar adjustments */
            .progress-container {
                padding: 0.5rem;
                margin-bottom: 1rem;
            }

            /* Custom text field adjustments */
            .custom-input {
                margin-top: 0.5rem;
                font-size: 14px;
            }

            /* Navigation button adjustments */
            .navigation-buttons {
                padding: 0.75rem 0;
                gap: 0.5rem;
            }
        }

        /* Tablet Adjustments */
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

        /* Progress Bar Adjustments */
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
            max  max-width: 800px;
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
            content: "";
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
        
        /* Mobile Adjustments */
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
            padding-top: 150%; /* 2:3 ratio */
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
        <!-- Progress Bar -->
        <div class="progress-container">
            <div class="progress-steps">
                <?php for ($i = 1; $i <= $totalSteps; $i++): ?>
                    <div class="progress-step <?php echo $i < $currentStep ? "completed" : ""; ?>">
                        <div class="step-indicator <?php 
                            echo $i < $currentStep ? "completed" : ($i == $currentStep ? "active" : "bg-gray-700");
                        ?>">
                            <?php echo $i; ?>
                        </div>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <form method="POST" class="space-y-4 md:space-y-8" id="quizForm">
            <input type="hidden" name="step" value="<?php echo $currentStep; ?>">

            <!-- Step 1 - Name -->
            <?php if ($currentStep == 1): ?>
                <div class="text-center">
                    <h2 class="text-2xl md:text-3xl font-bold mb-4">
                        <i class="fas fa-heart step-icon"></i>
                        What will be the name of your dream muse?
                    </h2>
                    <p class="text-gray-400 mb-4">Choose a name that makes your heart race...</p>
                    <div class="input-wrapper w-full max-w-md mx-auto mb-4">
                        <input type="text" 
                               name="name" 
                               required 
                               class="w-full px-3 md:px-4 py-2 md:py-3 rounded-lg bg-gray-800 border border-gray-700 focus:border-red-500 focus:ring-1 focus:ring-red-500 pl-12"
                               placeholder="The name that will make you fall in love..."
                               minlength="2"
                               maxlength="100">
                        <i class="fas fa-user custom-input-icon"></i>
                    </div>

                    <!-- Navigation Buttons for Step 1 -->
                    <div class="navigation-buttons flex justify-center mb-4">
                        <button type="submit" 
                                class="px-8 md:px-10 py-2 md:py-3 rounded-lg bg-red-500 hover:bg-red-600 transition duration-300 font-semibold">
                            Next
                        </button>
                    </div>

                    <div class="example-image-container">
                        <img src="../quiz-en/images/appearance/humana/1.png" alt="Example of AI with human appearance" class="example-image">
                    </div>
                </div>
            <?php endif; ?>

            <!-- Step 2 - Type -->
            <?php if ($currentStep == 2): ?>
                <div class="text-center">
                    <h2 class="text-2xl md:text-3xl font-bold mb-4">
                        <i class="fas fa-magic step-icon"></i>
                        What kind of goddess will capture your heart?
                    </h2>
                    <p class="text-gray-400 mb-4">Choose the personality that excites you most...</p>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 md:gap-4">
                        <?php
                        $types = [
                            "humana" => [
                                "name" => "Modern sensual human",
                                "icon" => "fas fa-user", 
                                "description" => "A modern, confident, and irresistible woman who knows exactly what she wants"
                            ],
                            "elfa" => [
                                "name" => "Seductive magical elf",
                                "icon" => "fas fa-hat-wizard", 
                                "description" => "A mystical creature with supernatural charms and a touch of mystery"
                            ],
                            "android" => [
                                "name" => "Futuristic sexy android",
                                "icon" => "fas fa-robot", 
                                "description" => "A perfect machine programmed to satisfy you in every way"
                            ]
                        ];
                        foreach ($types as $type => $data):
                        ?>
                            <label class="option-card cursor-pointer p-3 md:p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                                <input type="radio" name="type" value="<?php echo $type; ?>" required class="hidden">
                                <div class="text-center">
                                    <i class="<?php echo $data["icon"]; ?> option-icon"></i>
                                    <div class="text-lg md:text-xl font-semibold mb-1"><?php echo $data["name"]; ?></div>
                                    <div class="text-sm text-gray-400"><?php echo $data["description"]; ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Step 3 - Personality -->
            <?php if ($currentStep == 3): ?>
                <div class="text-center">
                    <h2 class="text-2xl md:text-3xl font-bold mb-4">
                        <i class="fas fa-star step-icon"></i>
                        How will she win you over?
                    </h2>
                    <p class="text-gray-400 mb-4">Choose the personality that excites you most...</p>
                
                    <div class="mb-8">
                        <h3 class="text-xl font-semibold mb-4">
                            <i class="fas fa-praying-hands step-icon"></i>
                            Does she have any special beliefs?
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php
                            $religions = [
                                "Devout Catholic" => ["icon" => "fas fa-church", "A devout woman with a hidden sinful side"],
                                "Conservative Evangelical" => ["icon" => "fas fa-bible", "A woman of faith with forbidden desires"],
                                "Modest Muslim" => ["icon" => "fas fa-mosque", "A rare gem who holds tempting secrets"],
                                "Mystical Wiccan" => ["icon" => "fas fa-magic", "A witch who knows the charms of love"],
                                "Free Spiritualist" => ["icon" => "fas fa-yin-yang", "A free soul who explores all pleasures"],
                                "Provocative Atheist" => ["icon" => "fas fa-atheism", "A woman who believes only in carnal pleasures"]
                            ];
                            foreach ($religions as $religion => $data):
                            ?>
                                <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                                    <input type="radio" name="religion" value="<?php echo $religion; ?>" required class="hidden">
                                    <div class="text-center">
                                        <i class="<?php echo $data["icon"]; ?> option-icon"></i>
                                        <div class="text-xl font-semibold mb-1"><?php echo $religion; ?></div>
                                        <div class="text-sm text-gray-400"><?php echo $data["description"]; ?></div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                            <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                                <input type="radio" name="religion" value="custom" class="hidden">
                                <div class="text-center">
                                    <i class="fas fa-star option-icon"></i>
                                    <div class="text-xl font-semibold mb-1">Other belief</div>
                                    <div class="text-sm text-gray-400 mb-2">What belief excites you?</div>
                                    <div class="input-wrapper">
                                        <input type="text" name="custom_religion" placeholder="Your most intimate fantasy..." 
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
                            How will she treat you?
                        </h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php
                            $behaviors = [
                                "Sweet and affectionate" => ["icon" => "fas fa-heart", "A gentle lover who knows how to pamper you"],
                                "Dominant and bold" => ["icon" => "fas fa-crown", "A woman who knows exactly what she wants from you"],
                                "Mysterious and seductive" => ["icon" => "fas fa-mask", "An enigma that drives you wild with desire"],
                                "Playful and teasing" => ["icon" => "fas fa-laugh", "A partner who loves to tease you"],
                                "Dominant and sarcastic" => ["icon" => "fas fa-comment-dots", "A woman who dominates with wit"],
                                "Sweet and submissive" => ["icon" => "fas fa-dove", "A companion who lives to please you"]
                            ];
                            foreach ($behaviors as $behavior => $data):
                            ?>
                                <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                                    <input type="radio" name="behavior" value="<?php echo $behavior; ?>" required class="hidden">
                                    <div class="text-center">
                                        <i class="<?php echo $data["icon"]; ?> option-icon"></i>
                                        <div class="text-xl font-semibold mb-1"><?php echo $behavior; ?></div>
                                        <div class="text-sm text-gray-400"><?php echo $data["description"]; ?></div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                            <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                                <input type="radio" name="behavior" value="custom" class="hidden">
                                <div class="text-center">
                                    <i class="fas fa-star option-icon"></i>
                                    <div class="text-xl font-semibold mb-1">Other behavior</div>
                                    <div class="text-sm text-gray-400 mb-2">How do you want to be treated?</div>
                                    <div class="input-wrapper">
                                        <input type="text" name="custom_behavior" placeholder="Your most intimate fantasy..." 
                                               class="custom-input w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 pl-10">
                                        <i class="fas fa-pen custom-input-icon"></i>
                                    </div>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Step 4 - Conversation Style -->
            <?php if ($currentStep == 4): ?>
                <div class="text-center">
                    <h2 class="text-2xl md:text-3xl font-bold mb-4">
                        <i class="fas fa-comments step-icon"></i>
                        How will she seduce you with words?
                    </h2>
                    <p class="text-gray-400 mb-4">Choose the style that excites you most...</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
                        <?php
                        $conversationStyles = [
                            "Direct and provocative" => [
                                "icon" => "fas fa-bolt", 
                                "description" => "A woman who isn't afraid to say what she wants"
                            ],
                            "Shy and reserved" => [
                                "icon" => "fas fa-shy", 
                                "description" => "A rare gem who reveals herself slowly"
                            ],
                            "Romantic and captivating" => [
                                "icon" => "fas fa-heart", 
                                "description" => "A poetess of love who enchants you"
                            ],
                            "Mysterious and intense" => [
                                "icon" => "fas fa-mask", 
                                "description" => "A seductress who keeps you curious"
                            ],
                            "Unfiltered and bold" => [
                                "icon" => "fas fa-fire", 
                                "description" => "A woman with no limits in seduction"
                            ]
                        ];
                        foreach ($conversationStyles as $style => $data):
                        ?>
                            <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                                <input type="radio" name="conversation_style" value="<?php echo $style; ?>" required class="hidden">
                                <div class="text-center">
                                    <i class="<?php echo $data["icon"]; ?> option-icon"></i>
                                    <div class="text-xl font-semibold mb-1"><?php echo $style; ?></div>
                                    <div class="text-sm text-gray-400"><?php echo $data["description"]; ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="space-y-6">
                        <div>
                            <label class="block text-xl font-semibold mb-2">
                                <i class="fas fa-angel step-icon"></i>
                                How shy is she?
                            </label>
                            <p class="text-sm text-gray-400 mb-2">From innocent to bold, choose the level that excites you...</p>
                            <div class="flex items-center">
                                <i class="fas fa-angel slider-icon"></i>
                                <input type="range" name="shyness_level" min="0" max="100" value="50" 
                                       class="w-full h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer">
                                <i class="fas fa-devil slider-icon"></i>
                            </div>
                            <div class="flex justify-between text-sm text-gray-400 mt-1">
                                <span>Innocent</span>
                                <span>Bold</span>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xl font-semibold mb-2">
                                <i class="fas fa-fire step-icon"></i>
                                Vulgarity level
                            </label>
                            <p class="text-sm text-gray-400 mb-2">From refined to provocative, set the level that drives you wild...</p>
                            <div class="flex items-center">
                                <i class="fas fa-dove slider-icon"></i>
                                <input type="range" name="vulgarity_level" min="0" max="100" value="50" 
                                       class="w-full h-2 bg-gray-700 rounded-lg appearance-none cursor-pointer">
                                <i class="fas fa-fire slider-icon"></i>
                            </div>
                            <div class="flex justify-between text-sm text-gray-400 mt-1">
                                <span>Refined</span>
                                <span>Provocative</span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Step 5 - Profession -->
            <?php if ($currentStep == 5): ?>
                <div class="text-center">
                    <h2 class="text-2xl md:text-3xl font-bold mb-4">
                        <i class="fas fa-briefcase step-icon"></i>
                        What's her professional secret?
                    </h2>
                    <p class="text-gray-400 mb-4">Choose the occupation that excites you most...</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php
                        $occupations = [
                            "Personal trainer" => ["icon" => "fas fa-dumbbell", "An instructor who knows how to take charge"],
                            "Sensual nurse" => ["icon" => "fas fa-heartbeat", "A caregiver with a forbidden side"],
                            "Enchanting sorceress" => ["icon" => "fas fa-magic", "A mage who knows the secrets of pleasure"],
                            "Provocative influencer" => ["icon" => "fas fa-camera", "A star who loves being the center of attention"],
                            "Dangerous hacker" => ["icon" => "fas fa-laptop-code", "A woman who seductively invades your privacy"],
                            "Erotic writer" => ["icon" => "fas fa-pen-fancy", "An artist who turns fantasies into reality"]
                        ];
                        foreach ($occupations as $occupation => $data):
                        ?>
                            <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                                <input type="radio" name="occupation" value="<?php echo $occupation; ?>" required class="hidden">
                                <div class="text-center">
                                    <i class="<?php echo $data["icon"]; ?> option-icon"></i>
                                    <div class="text-xl font-semibold mb-1"><?php echo $occupation; ?></div>
                                    <div class="text-sm text-gray-400"><?php echo $data["description"]; ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                        <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                            <input type="radio" name="occupation" value="custom" class="hidden">
                            <div class="text-center">
                                <i class="fas fa-star option-icon"></i>
                                <div class="text-xl font-semibold mb-1">Other profession</div>
                                <div class="text-sm text-gray-400 mb-2">What's the profession of your dreams?</div>
                                <div class="input-wrapper">
                                    <input type="text" name="custom_occupation" placeholder="Your professional fantasy..." 
                                           class="custom-input w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 pl-10">
                                    <i class="fas fa-pen custom-input-icon"></i>
                                </div>
                            </div>
                        </label>
                    </div>
                    
                    <div class="mt-8">
                        <label class="block text-xl font-semibold mb-4">
                            <i class="fas fa-heart step-icon"></i>
                            What are her secret hobbies?
                        </label>
                        <p class="text-sm text-gray-400 mb-2">Describe the activities that make her even more irresistible...</p>
                        <div class="input-wrapper w-full max-w-md mx-auto">
                            <textarea name="hobbies" rows="3" 
                                      class="w-full px-4 py-3 rounded-lg bg-gray-800 border border-gray-700 focus:border-red-500 focus:ring-1 focus:ring-red-500 pl-12"
                                      placeholder="The activities that make her even more irresistible..."></textarea>
                            <i class="fas fa-heart custom-input-icon"></i>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Step 6 - Age -->
            <?php if ($currentStep == 6): ?>
                <div class="text-center">
                    <h2 class="text-2xl md:text-3xl font-bold mb-4">
                        <i class="fas fa-hourglass-half step-icon"></i>
                        How old is the woman of your dreams?
                    </h2>
                    <p class="text-gray-400 mb-4">Choose the age range that excites you most...</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php
                        $ageRanges = [
                            "18-22" => ["icon" => "fas fa-heart", "A young woman full of energy and curiosity"],
                            "23-27" => ["icon" => "fas fa-star", "A woman who knows her desires"],
                            "28-35" => ["icon" => "fas fa-crown", "A mature woman who knows what she wants"],
                            "35+" => ["icon" => "fas fa-gem", "An experienced woman who masters the art of seduction"]
                        ];
                        foreach ($ageRanges as $value => $data):
                        ?>
                            <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                                <input type="radio" name="age_range" value="<?php echo $value; ?>" required class="hidden">
                                <div class="text-center">
                                    <i class="<?php echo $data["icon"]; ?> option-icon"></i>
                                    <div class="text-xl font-semibold mb-1"><?php echo str_replace("-", "–", $value); ?> years</div>
                                    <div class="text-sm text-gray-400"><?php echo $data["description"]; ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                        <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                            <input type="radio" name="age_range" value="custom" class="hidden">
                            <div class="text-center">
                                <i class="fas fa-star option-icon"></i>
                                <div class="text-xl font-semibold mb-1">Other age</div>
                                <div class="text-sm text-gray-400 mb-2">What age drives you wild?</div>
                                <div class="input-wrapper">
                                    <input type="number" name="custom_age" min="18" 
                                           class="custom-input w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 pl-10"
                                           placeholder="The age that excites you...">
                                    <i class="fas fa-pen custom-input-icon"></i>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Step 7 - Fetish -->
            <?php if ($currentStep == 7): ?>
                <div class="text-center">
                    <h2 class="text-2xl md:text-3xl font-bold mb-4">
                        <i class="fas fa-fire step-icon"></i>
                        What are her secret desires?
                    </h2>
                    <p class="text-gray-400 mb-4">Choose the fetish that excites you most...</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php
                        $fetishes = [
                            "Discreet Exhibitionism" => ["icon" => "fas fa-eye", "A woman who loves to be subtly watched"],
                            "Erotic Cosplay" => ["icon" => "fas fa-theater-masks", "A woman who transforms into your secret fantasies"],
                            "Light BDSM" => ["icon" => "fas fa-handcuffs", "A woman who enjoys power play and domination"],
                            "Secret Cuckold" => ["icon" => "fas fa-mask", "A woman who loves forbidden secrets and adventures"],
                            "Seductive Voyeurism" => ["icon" => "fas fa-binoculars", "A woman who loves to watch and be watched"],
                            "BBC Fetish" => ["icon" => "fas fa-male", "A woman with specific preferences for Black men"],
                            "Swinging" => ["icon" => "fas fa-users", "A woman who enjoys sharing experiences with other couples"],
                            "No, she's more traditional" => ["icon" => "fas fa-heart", "A woman who prefers conventional love"]
                        ];
                        foreach ($fetishes as $fetish => $data):
                        ?>
                            <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                                <input type="radio" name="fetish" value="<?php echo $fetish; ?>" required class="hidden">
                                <div class="text-center">
                                    <i class="<?php echo $data["icon"]; ?> option-icon"></i>
                                    <div class="text-xl font-semibold mb-1"><?php echo $fetish; ?></div>
                                    <div class="text-sm text-gray-400"><?php echo $data["description"]; ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                        <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                            <input type="radio" name="fetish" value="custom" class="hidden">
                            <div class="text-center">
                                <i class="fas fa-star option-icon"></i>
                                <div class="text-xl font-semibold mb-1">Other fetish</div>
                                <div class="text-sm text-gray-400 mb-2">What secret desire excites you?</div>
                                <div class="input-wrapper">
                                    <input type="text" name="custom_fetish" placeholder="Your most intimate fantasy..." 
                                           class="custom-input w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 pl-10">
                                    <i class="fas fa-pen custom-input-icon"></i>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Step 8 - Relationship -->
            <?php if ($currentStep == 8): ?>
                <div class="text-center">
                    <h2 class="text-2xl md:text-3xl font-bold mb-4">
                        <i class="fas fa-heart step-icon"></i>
                        What's your role in her life?
                    </h2>
                    <p class="text-gray-400 mb-4">Choose the type of relationship that excites you most...</p>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <?php
                        $relationships = [
                            "Acquaintance" => ["icon" => "fas fa-user", "A stranger she wants to get to know better"],
                            "Close friend (friendzoned)" => ["icon" => "fas fa-user-friends", "A friend she keeps close"],
                            "Intimate friend (flirty)" => ["icon" => "fas fa-heart", "A friend she flirts with"],
                            "Casual fling" => ["icon" => "fas fa-fire", "A casual partner who excites her"],
                            "Boyfriend" => ["icon" => "fas fa-heart", "The love of her life"],
                            "Husband" => ["icon" => "fas fa-ring", "The man she chose forever"],
                            "Lover" => ["icon" => "fas fa-mask", "Her forbidden secret"],
                            "Doormat" => ["icon" => "fas fa-shoe-prints", "Someone who lives to serve her"],
                            "Slave" => ["icon" => "fas fa-chain", "Someone who obeys her commands"],
                            "Master" => ["icon" => "fas fa-crown", "The man who controls her"]
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
                                <div class="text-xl font-semibold mb-1">Other relationship</div>
                                <div class="text-sm text-gray-400 mb-2">What type of relationship excites you?</div>
                                <div class="input-wrapper">
                                    <input type="text" name="custom_relationship" placeholder="Your relationship fantasy..." 
                                           class="custom-input w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 pl-10">
                                    <i class="fas fa-pen custom-input-icon"></i>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Step 9 - NSFW Photos -->
            <?php if ($currentStep == 9): ?>
                <div class="text-center">
                    <h2 class="text-2xl md:text-3xl font-bold mb-4">
                        <i class="fas fa-camera step-icon"></i>
                        Do you accept receiving intimate photos?
                    </h2>
                    <p class="text-gray-400 mb-4">Choose whether you want to receive exclusive photos...</p>
                    <div class="example-image-container mb-8">
                        <img src="../quiz-en/images/nsfw/photos/1.png" alt="Exemplo de foto íntima" class="example-image">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                            <input type="radio" name="accept_photos" value="yes" required class="hidden">
                            <div class="text-center">
                                <i class="fas fa-check option-icon"></i>
                                <div class="text-xl font-semibold mb-1">Yes, I accept</div>
                                <div class="text-sm text-gray-400">Receive exclusive and intimate photos</div>
                            </div>
                        </label>
                        <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                            <input type="radio" name="accept_photos" value="no" required class="hidden">
                            <div class="text-center">
                                <i class="fas fa-times option-icon"></i>
                                <div class="text-xl font-semibold mb-1">No, thanks</div>
                                <div class="text-sm text-gray-400">I'd rather just keep things conversational</div>
                            </div>
                        </label>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Step 10 - NSFW Videos -->
            <?php if ($currentStep == 10): ?>
                <div class="text-center">
                    <h2 class="text-2xl md:text-3xl font-bold mb-4">
                        <i class="fas fa-video step-icon"></i>
                        Do you accept receiving intimate videos?
                    </h2>
                    <p class="text-gray-400 mb-4">Choose whether you want to receive exclusive videos...</p>
                    <div class="example-image-container mb-8">
                        <img src="../quiz-en/images/nsfw/videos/1.png" alt="Exemplo de vídeo íntimo" class="example-image">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                            <input type="radio" name="accept_videos" value="yes" required class="hidden">
                            <div class="text-center">
                                <i class="fas fa-check option-icon"></i>
                                <div class="text-xl font-semibold mb-1">Yes, I accept</div>
                                <div class="text-sm text-gray-400">Receive exclusive and intimate videos</div>
                            </div>
                        </label>
                        <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                            <input type="radio" name="accept_videos" value="no" required class="hidden">
                            <div class="text-center">
                                <i class="fas fa-times option-icon"></i>
                                <div class="text-xl font-semibold mb-1">No, thanks</div>
                                <div class="text-sm text-gray-400">I'd rather just keep things conversational</div>
                            </div>
                        </label>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Step 11 - NSFW Audio -->
            <?php if ($currentStep == 11): ?>
                <div class="text-center">
                    <h2 class="text-2xl md:text-3xl font-bold mb-4">
                        <i class="fas fa-microphone step-icon"></i>
                        Do you accept receiving hot audios?
                    </h2>
                    <p class="text-gray-400 mb-4">Choose whether you want to receive exclusive audios...</p>
                    <div class="example-image-container mb-8">
                        <img src="../quiz-en/images/nsfw/audio/1.png" alt="Exemplo de áudio íntimo" class="example-image">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                            <input type="radio" name="accept_audio" value="yes" required class="hidden">
                            <div class="text-center">
                                <i class="fas fa-check option-icon"></i>
                                <div class="text-xl font-semibold mb-1">Yes, I accept</div>
                                <div class="text-sm text-gray-400">Receive exclusive and intimate audios</div>
                            </div>
                        </label>
                        <label class="option-card cursor-pointer p-4 rounded-lg border border-gray-700 bg-gray-800 hover:bg-gray-700">
                            <input type="radio" name="accept_audio" value="no" required class="hidden">
                            <div class="text-center">
                                <i class="fas fa-times option-icon"></i>
                                <div class="text-xl font-semibold mb-1">No, thanks</div>
                                <div class="text-sm text-gray-400">I'd rather just keep things conversational</div>
                            </div>
                        </label>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Step 12 - Appearance -->
            <?php if ($currentStep == 12): ?>
                <div class="text-center">
                    <h2 class="text-2xl md:text-3xl font-bold mb-4">
                        <i class="fas fa-image step-icon"></i>
                        Choose the look that drives you wild
                    </h2>
                    <p class="text-gray-400 mb-4">Select the appearance that excites you most...</p>
                    <div class="appearance-grid">
                        <?php
                        // Get the type chosen in Step 2
                        $selectedType = $_SESSION['quiz_data']['type'] ?? 'humana';
                        
                        // Define available images for each type
                        $appearanceImages = [
                            'humana' => range(1, 15),
                            'elfa' => range(1, 13),
                            'android' => range(1, 10)
                        ];
                        
                        // Get available images for the selected type
                        $availableImages = $appearanceImages[$selectedType] ?? $appearanceImages['humana'];
                        
                        foreach ($availableImages as $imageNumber):
                            $imagePath = "../quiz/images/appearance/" . $selectedType . "/" . $imageNumber . ".png";
                        ?>
                            <label class="option-card cursor-pointer">
                                <input type="radio" name="appearance" value="<?php echo $imageNumber; ?>" required class="hidden">
                                <div class="appearance-image-container">
                                    <img src="<?php echo $imagePath; ?>" 
                                         alt="Appearance <?php echo $imageNumber; ?>" 
                                         class="appearance-image"
                                         onclick="openImageModal(this.src)"
                                         onerror="this.onerror=null; this.src='../quiz/images/appearance/humana/1.png';">
                                    <div class="absolute inset-0 flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity">
                                        <i class="fas fa-heart text-4xl text-red-500"></i>
                                    </div>
                                    <div class="absolute inset-0 border-4 border-transparent rounded-lg transition-all duration-300 selected-overlay"></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Modal for image preview -->
                <div id="imageModal" class="fixed inset-0 bg-black bg-opacity-90 z-50 hidden flex items-center justify-center">
                    <div class="relative max-w-4xl max-h-[90vh] mx-auto">
                        <img id="modalImage" src="" alt="Enlarged image" class="max-w-full max-h-[90vh] object-contain">
                        <button onclick="closeImageModal()" class="absolute top-4 right-4 text-white text-2xl hover:text-red-500">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Navigation Buttons for Steps 2-9 -->
            <?php if ($currentStep > 1): ?>
                <div class="navigation-buttons flex justify-between mt-8">
                    <button type="button" onclick="window.location.href='?step=<?php echo $currentStep - 1; ?>'"
                            class="px-4 md:px-6 py-2 md:py-3 rounded-lg bg-gray-700 hover:bg-gray-600 transition duration-300">
                        Previous
                    </button>
                    <button type="submit" 
                            class="px-4 md:px-6 py-2 md:py-3 rounded-lg bg-red-500 hover:bg-red-600 transition duration-300">
                        <?php echo $currentStep === $totalSteps ? "Finish" : "Next"; ?>
                    </button>
                </div>
            <?php endif; ?>
        </form>
    </div>

    <script>
        // Add selection effect to options
        document.querySelectorAll(".option-card").forEach(card => {
            const input = card.querySelector("input[type=\"radio\"]");
            const customInput = card.querySelector("input[type=\"text\"], input[type=\"number\"]");
            
            if (customInput) {
                customInput.addEventListener("click", (e) => {
                    e.stopPropagation();
                    input.checked = true;
                    updateCardSelection(card);
                });
                
                customInput.addEventListener("input", (e) => {
                    input.value = "custom";
                    updateCardSelection(card);
                });
            }
            
            card.addEventListener("click", function() {
                input.checked = true;
                updateCardSelection(this);
            });
        });

        function updateCardSelection(selectedCard) {
            const groupName = selectedCard.querySelector("input[type=\"radio\"]").name;
            document.querySelectorAll(`input[name="${groupName}"]`).forEach(input => {
                const card = input.closest(".option-card");
                card.classList.remove("selected");
                const overlay = card.querySelector(".selected-overlay");
                if (overlay) {
                    overlay.classList.remove("border-red-500", "bg-red-500", "bg-opacity-20");
                }
            });
            selectedCard.classList.add("selected");
            const overlay = selectedCard.querySelector(".selected-overlay");
            if (overlay) {
                overlay.classList.add("border-red-500", "bg-red-500", "bg-opacity-20");
            }
        }

        // Update slider values
        document.querySelectorAll("input[type=\"range\"]").forEach(slider => {
            slider.addEventListener("input", function() {
                const value = this.value;
                const min = this.min;
                const max = this.max;
                const percentage = ((value - min) / (max - min)) * 100;
                this.style.background = `linear-gradient(to right, #ef4444 ${percentage}%, #374151 ${percentage}%)`;
            });
        });

        // Form validation
        document.getElementById("quizForm").addEventListener("submit", function(e) {
            const currentStep = parseInt(document.querySelector("input[name=\"step\"]").value);
            let isValid = true;

            // Specific validation for step 1 (name)
            if (currentStep === 1) {
                const nameInput = document.querySelector("input[name=\"name\"]");
                if (!nameInput.value.trim()) {
                    isValid = false;
                    showMobileAlert("Please enter a name for your crush.");
                }
            } else {
                // Validation for other steps with radio buttons
                const radioGroups = this.querySelectorAll("input[type=\"radio\"]");
                let checkedRadios = new Set();

                radioGroups.forEach(radio => {
                    if (radio.checked) {
                        checkedRadios.add(radio.name);
                        if (radio.value === "custom") {
                            const customInput = radio.parentElement.querySelector("input[type=\"text\"], input[type=\"number\"]");
                            if (!customInput.value.trim()) {
                                isValid = false;
                                showMobileAlert("Please fill in the custom field.");
                            }
                        }
                    }
                });

                if (checkedRadios.size === 0) {
                    isValid = false;
                    showMobileAlert("Please select an option.");
                }
            }

            if (!isValid) {
                e.preventDefault();
            }
        });

        // Function to show mobile alert
        function showMobileAlert(message) {
            const alertDiv = document.createElement("div");
            alertDiv.className = "fixed bottom-4 left-1/2 transform -translate-x-1/2 bg-red-500 text-white px-4 py-2 rounded-lg shadow-lg z-50";
            alertDiv.textContent = message;
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                alertDiv.remove();
            }, 3000);
        }

        // Adjust element sizes based on screen width
        function adjustElementsForScreenSize() {
            const isMobile = window.innerWidth <= 768;
            const container = document.querySelector(".container");
            
            if (isMobile) {
                container.classList.add("mobile-view");
            } else {
                container.classList.remove("mobile-view");
            }
        }

        // Run on load and resize
        window.addEventListener("load", adjustElementsForScreenSize);
        window.addEventListener("resize", adjustElementsForScreenSize);

        // Functions for image modal
        function openImageModal(imageSrc) {
            const modal = document.getElementById("imageModal");
            const modalImage = document.getElementById("modalImage");
            modalImage.src = imageSrc;
            modal.classList.remove("hidden");
            document.body.style.overflow = "hidden";
        }

        function closeImageModal() {
            const modal = document.getElementById("imageModal");
            modal.classList.add("hidden");
            document.body.style.overflow = "auto";
        }

        // Close modal when clicking outside the image
        document.getElementById("imageModal").addEventListener("click", function(e) {
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
fbq('init', '1372939697326355');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=1372939697326355&ev=PageView&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->