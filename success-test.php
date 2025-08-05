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
                $basicBenefits = [
                    "100 messages per day",
                    "5 simultaneous avatars",
                    "7-day chat history",
                    "Email support"
                ];

                $intermediateBenefits = [
                    "Unlimited messages",
                    ["Receive exclusive photos from your avatar🔞", true],
                    "15 simultaneous avatars",
                    "Full chat history",
                    "Priority 24/7 support"
                ];

                $advancedBenefits = [
                    "Unlimited messages",
                    ["Receive exclusive photos and videos from your avatar🔞", true],
                    ["Receive unlimited audio messages from your avatar🔞", true],
                    "Unlimited avatars",
                    "Full chat history",
                    "VIP 24/7 support",
                    "Exclusive access to VIP avatars",
                ];

                $testBenefits = [
                    "Test plan - 1 real",
                    "All features for testing",
                    "Valid for 1 day",
                    "Test support"
                ];

                foreach ($plans as $plan): 
                    if (strpos(strtolower($plan['name']), 'basic') !== false) {
                        $benefits = $basicBenefits;
                        $planLink = 'checkout.php?plan=basic&id=' . urlencode($avatarId);
                    } elseif (strpos(strtolower($plan['name']), 'intermediate') !== false) {
                        $benefits = $intermediateBenefits;
                        $planLink = 'checkout.php?plan=intermediate&id=' . urlencode($avatarId);
                    } else {
                        $benefits = $advancedBenefits;
                        $planLink = 'checkout.php?plan=vip&id=' . urlencode($avatarId);
                    }
                ?>
                    <div class="plan-card rounded-lg">
                        <div class="plan-header">
                            <?php if (strpos(strtolower($plan['name']), 'basic') !== false): ?>
                                <div class="text-pink-500 text-sm uppercase tracking-wide">Starter Plan</div>
                            <?php elseif (strpos(strtolower($plan['name']), 'intermediate') !== false): ?>
                                <div class="text-pink-500 text-sm uppercase tracking-wide">Most Popular</div>
                            <?php elseif (strpos(strtolower($plan['name']), 'advanced') !== false): ?>
                                <div class="text-pink-500 text-sm uppercase tracking-wide">VIP Plan</div>
                            <?php endif; ?>
                            <h3 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($plan["name"]); ?></h3>
                            <div class="plan-price">
                                <span class="text-4xl font-bold">€<?php echo number_format($plan["price"], 2, ",", "."); ?></span>
                                <span class="text-gray-400">/month</span>
                            </div>
                        </div>
                        <ul>
                            <?php foreach ($benefits as $benefit): 
                                $isHighlight = is_array($benefit);
                                $text = $isHighlight ? $benefit[0] : $benefit;
                            ?>
                                <li>
                                    <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    <span class="<?php echo $isHighlight ? "highlight" : ""; ?>"><?php echo htmlspecialchars($text); ?></span>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                        <a href="<?php echo $planLink; ?>" 
                           target="_blank"
                           class="plan-button">
                            Start Now
                        </a>
                    </div>
                <?php endforeach; ?>

                <!-- Test Plan -->
                <div class="plan-card rounded-lg">
                    <div class="plan-header">
                        <div class="text-pink-500 text-sm uppercase tracking-wide">Test Plan</div>
                        <h3 class="text-2xl font-bold mb-2">Test Plan</h3>
                        <div class="plan-price">
                            <span class="text-4xl font-bold">R$1,00</span>
                            <span class="text-gray-400">/test</span>
                        </div>
                    </div>
                    <ul>
                        <?php foreach ($testBenefits as $benefit): ?>
                            <li>
                                <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                <span><?php echo htmlspecialchars($benefit); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <a href="checkout.php?plan=test&id=<?php echo urlencode($avatarId); ?>" 
                       target="_blank"
                       class="plan-button">
                        Test Now
                    </a>
                </div>
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

        // Close modal when clicking outside the image
        document.getElementById("imageModal").addEventListener("click", function(e) {
            if (e.target === this) {
                closeImageModal();
            }
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
fbq('init', '1618229525799946');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=1618229525799946&ev=PageView&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->