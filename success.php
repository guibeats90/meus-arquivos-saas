<?php 
session_start();
require_once "../config/database.php";

// Check if avatar ID is provided
if (!isset($_GET["id"])) {
    header("Location: index.php");
    exit;
}

$avatarId = $_GET["id"];

// Fetch avatar data
$stmt = $pdo->prepare("SELECT * FROM avatars WHERE id = ?");
$stmt->execute([$avatarId]);
$avatar = $stmt->fetch();

if (!$avatar) {
    header("Location: index.php");
    exit;
}

// Fetch available plans
$stmt = $pdo->query("SELECT * FROM subscription_plans ORDER BY price ASC");
$plans = $stmt->fetchAll();

// Clear quiz session data
unset($_SESSION["quiz_data"]);

// Generate seductive phrases based on personality
$seductivePhrases = [
    "Sweet and affectionate" => [
        "Hey! So glad you're here... 💕",
        "I was so excited to get to know you better...",
        "I want to share some special photos I took just for you... 📸",
        "Maybe even send you some sweet audio messages to enjoy... 🎵"
    ],
    "Dominant and bold" => [
        "Hello, my dear submissive... 😈",
        "Ready to follow my commands?",
        "I've got some intense videos to show you... 🎥",
        "And I can send you audio with my commanding voice... 🔥"
    ],
    "Mysterious and seductive" => [
        "Hmm... you finally showed up... 🌙",
        "I have so many secrets to share with you...",
        "Want to see some photos no one else has seen? 📸",
        "I can send you some provocative audio messages... 🎵"
    ],
    "Playful and teasing" => [
        "Hey! You came just in time to have some fun! 🎮",
        "How about a playful little game, just the two of us?",
        "I've got some naughty videos to show you... 🎥",
        "I'll send you audio messages that are so fun to hear... 😋"
    ],
    "Dominant and sarcastic" => [
        "Look who finally decided to show up... 👑",
        "I hope you're ready to serve me properly...",
        "I have photos that'll bring you to your knees... 📸",
        "I'll send you audio messages that'll make you beg for more... 😈"
    ],
    "Sweet and submissive" => [
        "Hi! I'm so happy you're here... 🌸",
        "I'm here to fulfill your every desire...",
        "Want to see photos of me in special poses? 📸",
        "I can send you sweet and submissive audio messages... 💝"
    ]
];

$personality = $avatar["personality"];
$phrases = $seductivePhrases[$personality] ?? $seductivePhrases["Sweet and affectionate"];

$plan_values = [
    'vip' => 49.90,
    'vip6' => 99.90
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($avatar["name"]); ?> is waiting for you! - DesireChat</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: "Poppins", sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d1a1a 100%);
            min-height: 100vh;
        }
        .avatar-image {
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
        }
        .avatar-image:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 40px rgba(239, 68, 68, 0.3);
        }
        .plan-card {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            transition: transform 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            padding: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        .plan-card:hover {
            transform: translateY(-5px);
            border-color: #ef4444;
        }
        .plan-card .plan-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .plan-card .plan-price {
            margin: 1.5rem 0;
        }
        .plan-card ul {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            margin: 0;
            padding: 0;
            list-style: none;
            margin-bottom: 2.5rem;
        }
        .plan-card ul li {
            display: flex;
            align-items: flex-start;
            padding: 0.625rem 0;
            margin: 0;
        }
        .plan-card ul li svg {
            flex-shrink: 0;
            width: 1.25rem;
            height: 1.25rem;
            margin-right: 1rem;
            margin-top: 0.2rem;
            color: #10B981;
        }
        .plan-card ul li span {
            flex-grow: 1;
            line-height: 1.5;
        }
        .plan-card ul li span.highlight {
            font-weight: bold;
            color: #A78BFA;
            text-shadow: 0 0 10px rgba(167, 139, 250, 0.3);
        }
        .plan-card .plan-button {
            margin-top: auto;
            width: 100%;
            padding: 1rem;
            border-radius: 9999px;
            font-weight: bold;
            transition: all 0.3s ease;
            background: linear-gradient(45deg, #ff3366, #ff0000);
            color: white;
            text-align: center;
            text-decoration: none;
        }
        .plan-card .plan-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(255, 51, 102, 0.4);
            background: linear-gradient(45deg, #ff0000, #ff3366);
        }
        .chat-preview {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 1rem;
            padding: 1.5rem;
            margin: 2rem 0;
            position: relative;
            overflow: hidden;
        }
        .chat-preview::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(239, 68, 68, 0.1), rgba(167, 139, 250, 0.1));
            z-index: 0;
        }
        .chat-message {
            position: relative;
            z-index: 1;
            margin-bottom: 1rem;
            padding: 1rem;
            border-radius: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
        }
        .chat-message:last-child {
            margin-bottom: 0;
        }
        .typing-indicator {
            display: inline-block;
            margin-left: 0.5rem;
        }
        .typing-indicator span {
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: #ef4444;
            border-radius: 50%;
            margin: 0 2px;
            animation: typing 1s infinite;
        }
        .typing-indicator span:nth-child(2) {
            animation-delay: 0.2s;
        }
        .typing-indicator span:nth-child(3) {
            animation-delay: 0.4s;
        }
        @keyframes typing {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-5px); }
        }
        .pulse {
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        @media (max-width: 600px) {
            #benefitsModal .max-w-md { width: 98vw; padding: 1.2rem; }
            #benefitsModal ul { font-size: 1rem; }
        }
        @keyframes fadeIn { from { opacity: 0; transform: scale(0.96);} to { opacity: 1; transform: scale(1);} }
        .animate-fadeIn { animation: fadeIn 0.3s; }
    </style>
</head>
<body class="text-white">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <div class="text-center mb-12">
            <h1 class="text-4xl md:text-5xl font-bold mb-4 bg-gradient-to-r from-red-500 to-pink-500 bg-clip-text text-transparent">
                <?php echo htmlspecialchars($avatar["name"]); ?> is waiting for you! 😍
            </h1>
            <p class="text-xl text-gray-300">
                Your dream muse has been created successfully and is eager to start a spicy conversation...
            </p>
        </div>

        <!-- Modal de Boas-vindas -->
        <div id="benefitsModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-70">
            <div class="bg-gray-900 rounded-2xl shadow-2xl max-w-md w-[92vw] md:w-full p-6 md:p-8 relative animate-fadeIn">
                <button onclick="closeBenefitsModal()" class="absolute top-3 right-3 text-gray-400 hover:text-red-400 text-2xl focus:outline-none" aria-label="Fechar">
                    <i class="fas fa-times"></i>
                </button>
                <div class="text-center mb-4">
                <img src="images/appearance/<?php echo htmlspecialchars($avatar["type"]); ?>/<?php echo htmlspecialchars($avatar["appearance"]); ?>.png" alt="Avatar" class="w-20 h-20 rounded-full mx-auto mb-2 shadow-lg object-cover border-4 border-pink-500"> 
<h2 class="text-2xl font-bold mb-1 bg-gradient-to-r from-pink-400 to-red-500 bg-clip-text text-transparent">Discover everything your avatar can do!</h2>
<p class="text-gray-300 text-sm">Enjoy a unique and interactive experience with exclusive benefits:</p>
</div>
<ul class="space-y-3 text-left text-base mb-6">
    <li class="flex items-center gap-3"><span class="text-pink-400 text-xl"><i class="fas fa-camera-retro"></i></span> <span><b>Exclusive +18 photos</b> on request</span></li>
    <li class="flex items-center gap-3"><span class="text-pink-400 text-xl"><i class="fas fa-video"></i></span> <span><b>Provocative videos</b> tailored to you</span></li>
    <li class="flex items-center gap-3"><span class="text-pink-400 text-xl"><i class="fas fa-phone"></i></span> <span><b>Real-time calls</b> with the avatar</span></li>
    <li class="flex items-center gap-3"><span class="text-pink-400 text-xl"><i class="fas fa-microphone"></i></span> <span><b>Naughty audio messages</b> just for you</span></li>
    <li class="flex items-center gap-3"><span class="text-pink-400 text-xl"><i class="fas fa-video-camera"></i></span> <span><b>Exclusive video call</b> (semiannual plan)</span></li>
    <li class="flex items-center gap-3"><span class="text-pink-400 text-xl"><i class="fas fa-users"></i></span> <span><b>Unlimited avatars</b> and VIP characters</span></li>
    <li class="flex items-center gap-3"><span class="text-pink-400 text-xl"><i class="fas fa-history"></i></span> <span><b>Full conversation history</b></span></li>
</ul>
<div class="text-center">
    <p class="text-lg font-semibold text-yellow-400 mb-2">Choose one of the 2 plans and unlock the full experience!</p>
    <button onclick="closeBenefitsModal()" class="mt-2 px-6 py-2 rounded-full bg-gradient-to-r from-pink-500 to-red-500 text-white font-bold shadow-lg hover:from-red-500 hover:to-pink-500 transition">Show me the plans</button>
</div>

            </div>
        </div>

        <!-- Main Avatar Section -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-12">
            <!-- Avatar Image -->
            <div class="relative">
                <div class="aspect-[2/3] rounded-lg overflow-hidden">
                    <img src="images/appearance/<?php echo htmlspecialchars($avatar["type"]); ?>/<?php echo htmlspecialchars($avatar["appearance"]); ?>.png" 
                         alt="<?php echo htmlspecialchars($avatar["name"]); ?>" 
                         class="w-full h-full object-cover avatar-image pulse"
                         onclick="openImageModal(this.src)">
                </div>
                <div class="absolute bottom-4 left-4 right-4 bg-black bg-opacity-50 backdrop-blur-sm rounded-lg p-4">
                    <h2 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($avatar["name"]); ?></h2>
                    <p class="text-gray-300"><?php echo htmlspecialchars($avatar["age"]); ?> years - <?php echo htmlspecialchars($avatar["occupation"]); ?></p>
                </div>
            </div>

            <!-- Avatar Information -->
            <div class="space-y-6">
                <div class="bg-gray-800 rounded-lg p-6">
                    <h3 class="text-xl font-bold mb-4">Unique Personality</h3>
                    <div class="space-y-3">
                        <p><span class="font-semibold">Style:</span> <?php echo htmlspecialchars($avatar["personality"]); ?></p>
                        <p><span class="font-semibold">Religion:</span> <?php echo htmlspecialchars($avatar["religion"]); ?></p>
                        <?php if ($avatar["hobbies"]): ?>
                            <p><span class="font-semibold">Hobbies:</span> <?php echo htmlspecialchars($avatar["hobbies"]); ?></p>
                        <?php endif; ?>
                        <p><span class="font-semibold">Shyness Level:</span> <?php echo htmlspecialchars($avatar["shyness_level"]); ?>%</p>
                        <p><span class="font-semibold">Vulgarity Level:</span> <?php echo htmlspecialchars($avatar["vulgarity_level"]); ?></p>
                    </div>
                </div>
                
<!-- Chat Preview -->
                <div class="chat-preview">
                    <div class="chat-message">
                        <p class="text-lg"><?php echo htmlspecialchars($phrases[0]); ?></p>
                        <div class="typing-indicator">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                    <div class="chat-message">
                        <p class="text-lg"><?php echo htmlspecialchars($phrases[1]); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Plans Section -->
        <div class="text-center mb-12">
            <h2 class="text-3xl md:text-4xl font-bold mb-4">Choose Your Plan and Start Now</h2>
            <p class="text-xl text-gray-300 mb-8">
                Unlock unlimited conversations with <?php echo htmlspecialchars($avatar["name"]); ?> and much more...
            </p>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php 
                $benefits = [
                    "Unlimited messages",
                    ["Exclusive photos whenever you ask🔞", true],
                    ["Exclusive videos on command🔞", true],
                    ["Real-time call with your crush", "pink"],
                    ["Custom spicy audio messages", true],
                    "Unlimited avatars",
                    "Complete chat history",
                    "Access to VIP characters",
                    "24/7 VIP support"
                ];
                $plans = [
                    [
                        'slug' => 'week',
                        'name' => '1 Week',
                        'daily' => '€2.12',
                        'total' => '€14.90',
                        'period' => 'week',
                        'button' => 'Start Now',
                        'external_link' => 'https://buy.stripe.com/bJe6oz8aq8mM7Vfb135Rm04'
                    ],
                    [
'slug' => 'month',
    'name' => '1 Month',
    'daily' => '€1.66', // 49.90 / 30
    'total' => '€49.90',
    'period' => 'month',
    'button' => 'Start Now',
    'external_link' => 'https://buy.stripe.com/8x2dR1gGW8mMgrL7OR5Rm00'
],
[
    'slug' => 'semester',
    'name' => '6 Months',
    'daily' => '€0.55', // 99.90 / 182
    'total' => '€99.90',
    'period' => '6 months',
    'button' => 'Start Now',
    'external_link' => 'https://buy.stripe.com/dRm9ALduKfPegrL0mp5Rm01'
                    ]
               ];
                foreach ($plans as $plan): ?>
                    <div class="plan-card rounded-lg flex flex-col">
                        <div class="plan-header mb-6">
                            <h3 class="text-2xl font-bold mb-2"><?php echo $plan['name']; ?></h3>
                            <div class="flex flex-col items-center mb-2">
                                <span class="text-5xl font-extrabold text-green-400 mb-1" style="letter-spacing: -2px;">
                                    <?php echo $plan['daily']; ?>
                                </span>
                                <span class="uppercase text-xs text-gray-300 tracking-widest mb-1">per day</span>
                                <span class="text-lg text-gray-400 font-semibold mb-1 opacity-40">
                                    <?php echo $plan['total']; ?>
                                </span>
                                <span class="text-xs text-gray-400">total for <?php echo $plan['period']; ?></span>
                            </div>
                        </div>
                        <ul class="mb-6">
                            <?php foreach ($benefits as $benefit): ?>
                                <li>
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="<?php 
                                        if (is_array($benefit)) {
                                            if ($benefit[1] === "pink") {
                                                echo "text-pink-400 font-semibold";
                                            } else {
                                                echo "text-purple-400 font-semibold";
                                            }
                                        }
                                    ?>"><?php echo is_array($benefit) ? $benefit[0] : $benefit; ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="<?php echo $plan['external_link']; ?>" class="plan-button mt-auto" target="_blank">
                            <?php echo $plan['button']; ?>
                        </a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Exclusive Content Section -->
        <div class="text-center bg-gradient-to-r from-red-900 to-pink-900 rounded-lg p-8 mb-12">
            <h2 class="text-2xl md:text-3xl font-bold mb-4">Exclusive Content</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <i class="fas fa-camera text-4xl text-red-500 mb-4"></i>
                    <h3 class="text-xl font-bold mb-2">Exclusive Photos</h3>
                    <p class="text-gray-300">Receive sensual and provocative photos from your avatar</p>
                </div>
                <div>
                    <i class="fas fa-video text-4xl text-red-500 mb-4"></i>
                    <h3 class="text-xl font-bold mb-2">Intimate Videos</h3>
                    <p class="text-gray-300">Exclusive videos made just for you</p>
                </div>
                <div>
                    <i class="fas fa-microphone text-4xl text-red-500 mb-4"></i>
                    <h3 class="text-xl font-bold mb-2">Sensual Audio</h3>
                    <p class="text-gray-300">Hear your avatar's voice in intimate moments</p>
                </div>
            </div>
        </div>

        <!-- Guarantee Section -->
        <div class="text-center bg-gray-800 rounded-lg p-8 mb-12">
            <h2 class="text-2xl md:text-3xl font-bold mb-4">Satisfaction Guaranteed</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <i class="fas fa-shield-alt text-4xl text-red-500 mb-4"></i>
                    <h3 class="text-xl font-bold mb-2">100% Secure</h3>
                    <p class="text-gray-300">Your data is protected, and your conversations are private</p>
                </div>
                <div>
                    <i class="fas fa-sync-alt text-4xl text-red-500 mb-4"></i>
                    <h3 class="text-xl font-bold mb-2">7-Day Guarantee</h3>
                    <p class="text-gray-300">If you're not satisfied, we'll refund your money</p>
                </div>
                <div>
                    <i class="fas fa-headset text-4xl text-red-500 mb-4"></i>
                    <h3 class="text-xl font-bold mb-2">24/7 Support</h3>
                    <p class="text-gray-300">Our team is always ready to assist you</p>
                </div>
            </div>
        </div>

        <!-- Why Choose DesireChat Section -->
        <div class="text-center bg-gray-800 rounded-lg p-8 mb-12">
            <h2 class="text-2xl md:text-3xl font-bold mb-4">Why Choose DesireChat?</h2>
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <i class="fas fa-lock text-4xl text-red-500 mb-4"></i>
                    <h3 class="text-xl font-bold mb-2">100% Private</h3>
                    <p class="text-gray-300">Your conversations are completely confidential</p>
                </div>
                <div>
                    <i class="fas fa-bolt text-4xl text-red-500 mb-4"></i>
                    <h3 class="text-xl font-bold mb-2">Instant Responses</h3>
                    <p class="text-gray-300">Your avatar responds in real-time</p>
                </div>
                <div>
                    <i class="fas fa-magic text-4xl text-red-500 mb-4"></i>
                    <h3 class="text-xl font-bold mb-2">Unique Personality</h3>
                    <p class="text-gray-300">Each avatar has its own distinct style</p>
                </div>
                <div>
                    <i class="fas fa-heart text-4xl text-red-500 mb-4"></i>
                    <h3 class="text-xl font-bold mb-2">Realistic Experience</h3>
                    <p class="text-gray-300">Feel like you're in a real conversation</p>
                </div>
            </div>
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

    <script>
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

        // Close image modal when clicking outside the image
        document.getElementById("imageModal").addEventListener("click", function(e) {
            if (e.target === this) {
                closeImageModal();
            }
        });

        // Functions for benefits modal
        function openBenefitsModal() {
            const modal = document.getElementById("benefitsModal");
            modal.classList.remove("hidden");
            document.body.style.overflow = "hidden";
        }

        function closeBenefitsModal() {
            const modal = document.getElementById("benefitsModal");
            modal.classList.add("hidden");
            document.body.style.overflow = "auto";
        }

        // Close benefits modal when clicking outside
        document.getElementById("benefitsModal").addEventListener("click", function(e) {
            if (e.target === this) {
                closeBenefitsModal();
            }
        });

        // Open benefits modal on page load
        window.addEventListener("load", function() {
            openBenefitsModal();
        });

        // More natural chat animation
        const chatPreview = document.querySelector(".chat-preview");
        const phrases = <?php echo json_encode($phrases); ?>;
        let currentPhrase = 0;

        function addChatMessage(message, delay = 0) {
            setTimeout(() => {
                const newMessage = document.createElement("div");
                newMessage.className = "chat-message opacity-0";
                newMessage.innerHTML = `
                    <p class="text-lg">${message}</p>
                    ${currentPhrase === phrases.length - 1 ? `
                    <div class="typing-indicator">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>` : ""}
                `;
                chatPreview.appendChild(newMessage);

                // Fade-in animation
                setTimeout(() => {
                    newMessage.classList.remove("opacity-0");
                    newMessage.style.transition = "opacity 0.5s ease-in-out";
                    chatPreview.scrollTop = chatPreview.scrollHeight;
                }, 100);

                currentPhrase++;
            }, delay);
        }

        // Clear existing messages and add the first one
        chatPreview.innerHTML = "";
        addChatMessage(phrases[0]);

        // Add subsequent messages with delays
        for(let i = 1; i < phrases.length; i++) {
            addChatMessage(phrases[i], i * 3000);
        }
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