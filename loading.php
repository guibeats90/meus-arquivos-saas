<?php
session_start();
$avatarId = $_GET["id"] ?? null;
if (!$avatarId) {
    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criando sua Crush AI - DesireChat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: "Poppins", sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d1a1a 100%);
            min-height: 100vh;
            overflow: hidden;
        }
        .loading-container {
            position: relative;
            width: 200px;
            height: 200px;
        }
        .loading-circle {
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 50%;
            border: 4px solid transparent;
            border-top-color: #ef4444;
            animation: spin 2s linear infinite;
        }
        .loading-circle:nth-child(2) {
            border-top-color: #f43f5e;
            animation-delay: 0.5s;
        }
        .loading-circle:nth-child(3) {
            border-top-color: #ec4899;
            animation-delay: 1s;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .heart-pulse {
            animation: pulse 1.5s ease-in-out infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }
        .message-fade {
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.5s ease forwards;
        }
        @keyframes fadeInUp {
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .progress-bar {
            width: 100%;
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            overflow: hidden;
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #ef4444, #ec4899);
            width: 0%;
            transition: width 0.5s ease;
        }
    </style>
</head>
<body class="text-white">
    <div class="container mx-auto px-4 py-8 max-w-4xl min-h-screen flex flex-col items-center justify-center">
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-bold mb-4 bg-gradient-to-r from-red-500 to-pink-500 bg-clip-text text-transparent">
            Creating your Crush AI <i class="fas fa-heart text-red-500 heart-pulse"></i>
            </h1>
            <p class="text-xl text-gray-300 mb-8">Preparing a unique and personalized experience...</p>
        </div>

        <div class="loading-container mb-12">
            <div class="loading-circle"></div>
            <div class="loading-circle"></div>
            <div class="loading-circle"></div>
        </div>

        <div class="w-full max-w-md space-y-4 mb-12">
            <div class="progress-bar">
                <div class="progress-fill" id="progressFill"></div>
            </div>
            <div class="text-center text-gray-400" id="progressText">0%</div>
        </div>

        <div class="space-y-4 text-center" id="loadingMessages">
        <p class="message-fade text-lg text-gray-300">Generating unique personality...</p>
<p class="message-fade text-lg text-gray-300">Creating special memories...</p>
<p class="message-fade text-lg text-gray-300">Preparing intimate conversations...</p>
<p class="message-fade text-lg text-gray-300">Adjusting seduction levels...</p>
<p class="message-fade text-lg text-gray-300">Finishing your perfect crush...</p>
        </div>
    </div>

    <script>
        const messages = [
            "Gerando personalidade única...",
            "Criando memórias especiais...",
            "Preparando conversas íntimas...",
            "Ajustando níveis de sedução...",
            "Finalizando sua crush perfeita..."
        ];

        const progressFill = document.getElementById("progressFill");
        const progressText = document.getElementById("progressText");
        const loadingMessages = document.getElementById("loadingMessages");
        let currentProgress = 0;
        let currentMessage = 0;

        function updateProgress() {
            if (currentProgress < 100) {
                currentProgress += 1;
                progressFill.style.width = `${currentProgress}%`;
                progressText.textContent = `${currentProgress}%`;

                if (currentProgress % 20 === 0 && currentMessage < messages.length) {
                    const messageElement = loadingMessages.children[currentMessage];
                    messageElement.style.opacity = "1";
                    currentMessage++;
                }

                setTimeout(updateProgress, 50);
            } else {
                setTimeout(() => {
                    window.location.href = `success.php?id=<?php echo $avatarId; ?>`;
                }, 1000);
            }
        }

        // Iniciar a animação
        updateProgress();
    </script>
</body>
</html> 