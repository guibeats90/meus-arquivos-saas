<?php
require_once 'includes/ImageGenerator.php';

$error = '';
$success = '';
$imageUrl = '';

// Exemplos de prompts
$examplePrompts = [
    "A stunning seductive elf woman with long silver hair, emerald green eyes, wearing extremely revealing fantasy lingerie made of delicate gold chains and silk, seductive pose, voluptuous and curvy body, large breasts, narrow waist, wide hips, soft glowing skin, highly detailed, intricate jewelry, pointy ears, sensual expression, bedroom eyes, moist lips, soft magical lighting, enchanted forest background, erotic atmosphere, 3D render, cinematic style, ultra detailed, full body, high resolution, fantasy sensuality",
    "A beautiful woman with long black hair, wearing a hijab, in a gym setting, muscular body, athletic pose, professional lighting, high quality, detailed, photorealistic, 8k, professional photography",
    "A professional portrait of a doctor in a hospital, wearing a white coat, stethoscope around neck, confident expression, professional lighting, high quality, detailed, photorealistic, 8k, professional photography"
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $prompt = $_POST['prompt'];
        $imageGenerator = new ImageGenerator();
        
        // Gera um nome único para o arquivo
        $filename = 'test_' . time() . '_' . uniqid() . '.png';
        $outputPath = __DIR__ . '/uploads/avatars/' . $filename;
        
        // Gera a imagem
        $imageGenerator->generateImage($prompt, $outputPath);
        
        $imageUrl = 'uploads/avatars/' . $filename;
        $success = 'Imagem gerada com sucesso!';
    } catch (Exception $e) {
        $error = 'Erro ao gerar imagem: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Geração de Imagens</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Teste de Geração de Imagens</h1>
        
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
        
        <form method="POST" class="mb-8">
            <div class="mb-4">
                <label for="prompt" class="block text-sm font-medium mb-2">Prompt para a imagem:</label>
                <textarea id="prompt" name="prompt" rows="6" class="w-full px-4 py-2 rounded bg-gray-800 border border-gray-700 focus:border-red-500 focus:ring-1 focus:ring-red-500" required></textarea>
            </div>
            
            <div class="mb-4">
                <label class="block text-sm font-medium mb-2">Exemplos de prompts:</label>
                <div class="space-y-2">
                    <?php foreach ($examplePrompts as $index => $example): ?>
                        <button type="button" onclick="document.getElementById('prompt').value = `<?php echo htmlspecialchars($example); ?>`" class="text-left w-full p-2 rounded bg-gray-800 hover:bg-gray-700 transition duration-300">
                            Exemplo <?php echo $index + 1; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <button type="submit" class="bg-red-500 text-white px-6 py-3 rounded hover:bg-red-600 transition duration-300">
                Gerar Imagem
            </button>
        </form>
        
        <?php if ($imageUrl): ?>
            <div class="mt-8">
                <h2 class="text-xl font-bold mb-4">Imagem Gerada:</h2>
                <img src="<?php echo $imageUrl; ?>" alt="Imagem gerada" class="max-w-full rounded-lg shadow-lg">
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 