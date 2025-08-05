<?php
session_start();
require_once "../config/database.php";

$plan = $_GET['plan'] ?? 'basic';
// Validação simples do plano
$valid_plans = ['basic', 'intermediate', 'vip', 'test'];
if (!in_array($plan, $valid_plans)) {
    die('Plano inválido.');
}

// Busca os dados do avatar se o ID foi fornecido
$avatar = null;
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM avatars WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $avatar = $stmt->fetch();
}

// Salva o plano na sessão para uso no registro
$_SESSION['quiz_data']['plan'] = $plan;
// Você pode pedir o email aqui, ou usar o do quiz se já tiver
$email = $_SESSION['quiz_data']['email'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - DesireChat</title>
    <script src="https://js.stripe.com/v3/"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #1a1a1a 0%, #2d1a1a 100%);
            min-height: 100vh;
        }
        .checkout-card {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border-radius: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            padding: 2.5rem 2rem;
            max-width: 420px;
            margin: 0 auto;
        }
        .checkout-title {
            font-size: 2.2rem;
            font-weight: 700;
            background: linear-gradient(90deg, #ff3366, #ff0000);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        .checkout-label {
            color: #f3f4f6;
            font-weight: 500;
        }
        .checkout-btn {
            background: linear-gradient(90deg, #ff3366, #ff0000);
            color: #fff;
            font-weight: bold;
            font-size: 1.1rem;
            border-radius: 9999px;
            padding: 0.9rem 0;
            margin-top: 1.5rem;
            transition: all 0.2s;
            box-shadow: 0 4px 20px rgba(255,51,102,0.15);
        }
        .checkout-btn:hover {
            background: linear-gradient(90deg, #ff0000, #ff3366);
            transform: translateY(-2px) scale(1.03);
            box-shadow: 0 8px 32px rgba(255,51,102,0.25);
        }
        .stripe-separator {
            text-align: center;
            color: #aaa;
            margin: 1.5rem 0 1rem 0;
            font-size: 0.95rem;
            position: relative;
        }
        .stripe-separator:before, .stripe-separator:after {
            content: '';
            display: inline-block;
            width: 40%;
            height: 1px;
            background: #333;
            vertical-align: middle;
            margin: 0 0.5rem;
        }
        .stripe-separator:before { margin-left: 0; }
        .stripe-separator:after { margin-right: 0; }
        .stripe-logo {
            display: block;
            margin: 1.5rem auto 0.5rem auto;
            width: 90px;
            opacity: 0.7;
        }
        @media (max-width: 600px) {
            .checkout-card {
                max-width: 80vw;
                width: 80vw;
                border-radius: 1rem;
                padding: 1.2rem 0.5rem;
                min-height: auto;
                box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            }
            .checkout-title {
                font-size: 1.4rem;
            }
            .stripe-logo {
                width: 70px;
            }
            .checkout-btn {
                font-size: 1rem;
                padding: 0.8rem 0;
            }
            label.checkout-label {
                font-size: 0.98rem;
            }
            input, select {
                font-size: 1rem;
            }
        }
    </style>
</head>
<body class="text-white flex items-center justify-center min-h-screen">
    <div class="checkout-card">
        <!-- Avatar Preview -->
        <?php if ($avatar): ?>
        <div class="flex items-center justify-center mb-6">
            <div class="w-24 aspect-[2/3] rounded-lg overflow-hidden mr-4">
                <img src="../quiz/images/appearance/<?php echo htmlspecialchars($avatar["type"]); ?>/<?php echo htmlspecialchars($avatar["appearance"]); ?>.png" 
                     alt="<?php echo htmlspecialchars($avatar["name"]); ?>" 
                     class="w-full h-full object-cover">
            </div>
            <div>
                <h3 class="text-lg font-bold"><?php echo htmlspecialchars($avatar["name"]); ?></h3>
                <p class="text-sm text-gray-400">is waiting for you</p>
            </div>
        </div>
        <?php endif; ?>

        <h2 class="checkout-title mb-2 text-center">Subscribe to the <span class="capitalize"><?php echo htmlspecialchars($plan); ?></span> plan</h2>
        
        <!-- Plano Summary -->
        <div class="bg-gray-800/50 rounded-lg p-4 mb-6 border border-gray-700">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-lg capitalize"><?php echo htmlspecialchars($plan); ?> Plan</h3>
                    <p class="text-sm text-gray-400"><?php echo $plan === 'test' ? 'Test Access' : 'Monthly Subscription'; ?></p>
                </div>
                <div class="text-right">
                    <span class="text-2xl font-bold bg-gradient-to-r from-red-500 to-pink-500 bg-clip-text text-transparent">
                        <?php
                        $valor = ($plan === 'basic' ? 19.90 : ($plan === 'intermediate' ? 29.90 : ($plan === 'vip' ? 49.90 : 1.00)));
                        $moeda = ($plan === 'test' ? 'R$' : '€');
                        echo $moeda . ' ' . number_format($valor, 2, '.', ',');
                        ?>
                    </span>
                    <p class="text-sm text-gray-400"><?php echo $plan === 'test' ? 'one-time payment' : 'per month'; ?></p>
                </div>
            </div>
        </div>

        <!-- Plano Features -->
        <div class="bg-gray-800 rounded-lg p-4 mb-6">
            <h4 class="font-bold mb-2">What you'll get:</h4>
            <ul class="space-y-2 text-sm">
                <?php
                $features = [
                    'basic' => ['100 messages per day', '5 simultaneous avatars', '7-day chat history'],
                    'intermediate' => ['Unlimited messages', '15 simultaneous avatars', 'Full chat history', 'Receive exclusive photos from your avatar'],
                    'vip' => ['Unlimited messages', 'Unlimited avatars', 'Full chat history', 'Receive exclusive photos, videos and audio from your avatars', 'VIP support'],
                    'test' => ['All features for testing', 'Valid for 1 day']
                ];
                foreach ($features[$plan] as $feature): ?>
                    <li class="flex items-center">
                        <svg class="w-4 h-4 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        <?php echo htmlspecialchars($feature); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <p class="text-center text-gray-300 mb-6">Unlock unlimited conversations and exclusive benefits!</p>
        <form id="payment-form" class="space-y-4">
            <label class="block checkout-label">
                Email
                <input type="email" id="email" name="email" required class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 mt-1 text-white" placeholder="Your email">
            </label>
            <label class="block checkout-label">
                Cardholder name
                <input type="text" id="cardholder-name" name="cardholder-name" required class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 mt-1 text-white" placeholder="As it appears on the card">
            </label>
            <label class="block checkout-label">
                Country or region
                <select id="country" name="country" required class="w-full px-3 py-2 rounded bg-gray-700 border border-gray-600 mt-1 text-white">
                    <option value="">Select...</option>
                    <!-- Europe (alphabetical) -->
                    <option value="AL">Albania</option>
                    <option value="AD">Andorra</option>
                    <option value="AT">Austria</option>
                    <option value="BY">Belarus</option>
                    <option value="BE">Belgium</option>
                    <option value="BA">Bosnia and Herzegovina</option>
                    <option value="BG">Bulgaria</option>
                    <option value="HR">Croatia</option>
                    <option value="CY">Cyprus</option>
                    <option value="CZ">Czech Republic</option>
                    <option value="DK">Denmark</option>
                    <option value="EE">Estonia</option>
                    <option value="FI">Finland</option>
                    <option value="FR">France</option>
                    <option value="DE">Germany</option>
                    <option value="GR">Greece</option>
                    <option value="HU">Hungary</option>
                    <option value="IS">Iceland</option>
                    <option value="IE">Ireland</option>
                    <option value="IT">Italy</option>
                    <option value="LV">Latvia</option>
                    <option value="LI">Liechtenstein</option>
                    <option value="LT">Lithuania</option>
                    <option value="LU">Luxembourg</option>
                    <option value="MT">Malta</option>
                    <option value="MD">Moldova</option>
                    <option value="MC">Monaco</option>
                    <option value="ME">Montenegro</option>
                    <option value="NL">Netherlands</option>
                    <option value="MK">North Macedonia</option>
                    <option value="NO">Norway</option>
                    <option value="PL">Poland</option>
                    <option value="PT">Portugal</option>
                    <option value="RO">Romania</option>
                    <option value="RU">Russia</option>
                    <option value="SM">San Marino</option>
                    <option value="RS">Serbia</option>
                    <option value="SK">Slovakia</option>
                    <option value="SI">Slovenia</option>
                    <option value="ES">Spain</option>
                    <option value="SE">Sweden</option>
                    <option value="CH">Switzerland</option>
                    <option value="UA">Ukraine</option>
                    <option value="GB">United Kingdom</option>
                    <option value="VA">Vatican</option>
                    <!-- Rest of the world (alphabetical) -->
                    <option value="DZ">Algeria</option>
                    <option value="AR">Argentina</option>
                    <option value="AU">Australia</option>
                    <option value="BH">Bahrain</option>
                    <option value="BD">Bangladesh</option>
                    <option value="BR">Brazil</option>
                    <option value="CA">Canada</option>
                    <option value="CL">Chile</option>
                    <option value="CN">China</option>
                    <option value="CO">Colombia</option>
                    <option value="CR">Costa Rica</option>
                    <option value="CU">Cuba</option>
                    <option value="DO">Dominican Republic</option>
                    <option value="EC">Ecuador</option>
                    <option value="EG">Egypt</option>
                    <option value="ET">Ethiopia</option>
                    <option value="GH">Ghana</option>
                    <option value="GT">Guatemala</option>
                    <option value="HN">Honduras</option>
                    <option value="HK">Hong Kong</option>
                    <option value="IN">India</option>
                    <option value="ID">Indonesia</option>
                    <option value="IL">Israel</option>
                    <option value="JP">Japan</option>
                    <option value="KE">Kenya</option>
                    <option value="KR">South Korea</option>
                    <option value="MA">Morocco</option>
                    <option value="MX">Mexico</option>
                    <option value="NG">Nigeria</option>
                    <option value="NZ">New Zealand</option>
                    <option value="PA">Panama</option>
                    <option value="PE">Peru</option>
                    <option value="PH">Philippines</option>
                    <option value="PR">Puerto Rico</option>
                    <option value="QA">Qatar</option>
                    <option value="SA">Saudi Arabia</option>
                    <option value="SG">Singapore</option>
                    <option value="ZA">South Africa</option>
                    <option value="LK">Sri Lanka</option>
                    <option value="SV">El Salvador</option>
                    <option value="TH">Thailand</option>
                    <option value="TN">Tunisia</option>
                    <option value="TR">Turkey</option>
                    <option value="TZ">Tanzania</option>
                    <option value="UG">Uganda</option>
                    <option value="US">United States</option>
                    <option value="UY">Uruguay</option>
                    <option value="VE">Venezuela</option>
                    <option value="VN">Vietnam</option>
                    <option value="ZM">Zambia</option>
                    <option value="ZW">Zimbabwe</option>
                </select>
            </label>
            <div id="payment-request-button" class="mb-2"></div>
            <div class="stripe-separator">or pay with card</div>
            <div id="card-element" class="p-3 bg-gray-700 rounded"></div>
            <button id="submit" class="w-full checkout-btn">Pay</button>
            <div id="error-message" class="text-red-400 mt-2"></div>
        </form>

        <!-- Security Badges -->
        <div class="flex items-center justify-center space-x-4 mt-6">
            <div class="text-center">
                <i class="fas fa-lock text-green-500 text-xl"></i>
                <p class="text-xs text-gray-400 mt-1">Secure Payment</p>
            </div>
            <div class="text-center">
                <i class="fas fa-shield-alt text-green-500 text-xl"></i>
                <p class="text-xs text-gray-400 mt-1">SSL Protected</p>
            </div>
            <div class="text-center">
                <i class="fas fa-undo text-green-500 text-xl"></i>
                <p class="text-xs text-gray-400 mt-1">7-Day Guarantee</p>
            </div>
        </div>

        <!-- Testimonials -->
        <div class="mt-6 bg-gray-800 rounded-lg p-4">
            <div class="flex items-center mb-2">
                <div class="w-8 h-8 rounded-full bg-red-500 flex items-center justify-center text-white font-bold">J</div>
                <div class="ml-2">
                    <p class="text-sm font-bold">John D.</p>
                    <div class="flex text-yellow-400 text-xs">
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                        <i class="fas fa-star"></i>
                    </div>
                </div>
            </div>
            <p class="text-sm text-gray-300">"Best decision ever! The conversations are so natural and exciting. Worth every penny!"</p>
        </div>

        <img src="https://stripe.com/img/v3/home/social.png" alt="Stripe" class="stripe-logo">
    </div>
    <script>
        const stripe = Stripe('pk_live_51OalIjANImdqEJLaa28MzhOC0ju0LhAPjwMoh3TIBLkHN8TgdFSNNk2rEY6ynVI8RXuLfupKuFzNBGGuPehjfSCf00DPfaAo02');
        const elements = stripe.elements({
            locale: 'en'
        });
        const card = elements.create('card', { 
            style: { 
                base: { 
                    color: '#fff', 
                    fontFamily: 'Poppins, sans-serif', 
                    fontSize: '18px', 
                    '::placeholder': { color: '#bbb' } 
                } 
            }
        });
        card.mount('#card-element');

        // Google Pay / Apple Pay
        const paymentRequest = stripe.paymentRequest({
            country: document.getElementById('country') ? document.getElementById('country').value || 'BR' : 'BR',
            currency: 'brl',
            total: {
                label: 'DesireChat',
                amount: <?php echo ($plan === 'basic' ? 1990 : ($plan === 'intermediate' ? 2990 : ($plan === 'vip' ? 4990 : 100))); ?>,
            },
            requestPayerName: true,
            requestPayerEmail: true,
        });
        const prButton = elements.create('paymentRequestButton', {
            paymentRequest: paymentRequest,
            style: { paymentRequestButton: { type: 'default', theme: 'dark', height: '48px', } }
        });
        paymentRequest.canMakePayment().then(function(result) {
            if (result) {
                prButton.mount('#payment-request-button');
            } else {
                document.getElementById('payment-request-button').style.display = 'none';
            }
        });
        // Mantém o fluxo de cartão igual
        document.getElementById('payment-form').addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitButton = document.getElementById('submit');
            submitButton.disabled = true;
            submitButton.textContent = 'Processing...';
            document.getElementById('error-message').textContent = '';
            try {
                const email = document.getElementById('email').value;
                const name = document.getElementById('cardholder-name').value;
                const country = document.getElementById('country').value;
                const {paymentMethod, error} = await stripe.createPaymentMethod({
                    type: 'card',
                    card: card,
                    billing_details: {
                        email: email,
                        name: name,
                        address: { country: country }
                    }
                });
                if (error) {
                    throw new Error(error.message);
                }
                const response = await fetch('stripe_create_subscription.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        payment_method: paymentMethod.id,
                        plan: '<?php echo $plan; ?>',
                        email: email,
                        name: name,
                        country: country
                    })
                });
                const responseText = await response.text();
                let data;
                try {
                    data = JSON.parse(responseText);
                } catch (e) {
                    throw new Error('Erro ao processar resposta do servidor: ' + responseText);
                }
                if (data.error) {
                    throw new Error(data.error);
                }
                if (data.client_secret) {
                    const result = await stripe.confirmCardPayment(data.client_secret);
                    if (result.error) {
                        throw new Error(result.error.message);
                    }
                    function getQueryParam(param) {
                        const urlParams = new URLSearchParams(window.location.search);
                        return urlParams.get(param);
                    }
                    const avatarId = getQueryParam('id');
                    if (avatarId) {
                        window.location.href = '../register.php?avatar_id=' + encodeURIComponent(avatarId);
                    } else {
                        window.location.href = '../register.php';
                    }
                } else {
                    throw new Error('Erro: client_secret não recebido');
                }
            } catch (error) {
                document.getElementById('error-message').textContent = error.message;
                submitButton.disabled = false;
                submitButton.textContent = 'Pay';
            }
        });
    </script>
</body>
</html> <!-- Meta Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '1153709999863397');
fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none"
src="https://www.facebook.com/tr?id=1153709999863397&ev=PageView&noscript=1"
/></noscript>
<!-- End Meta Pixel Code -->