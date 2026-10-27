<?php
require_once '../includes/config.php';
redirect_if_not_logged_in();

$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT credits, subscription_tier, subscription_end_date, trial_end_date, sellix_api_key, sellix_store_id, gamepass_claimed FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_info = $stmt->fetch();

$stmt = $pdo->prepare("SELECT roblox_username, verified FROM roblox_accounts WHERE user_id = ?");
$stmt->execute([$user_id]);
$roblox_account = $stmt->fetch();

$trial_eligible = !$user_info['trial_end_date'];

$subscription_tiers = $pdo->query("SELECT * FROM subscription_tiers ORDER BY price ASC")->fetchAll();

// Products from the Sellix store configured in Settings.
function fetch_sellix_products($api_key, $store_id) {
    if (!$api_key) {
        return [];
    }
    $ch = curl_init('https://dev.sellix.io/v1/products');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => array_filter([
            "Authorization: Bearer $api_key",
            $store_id ? "X-Sellix-Merchant: $store_id" : null,
        ]),
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode((string) $response, true);
    return $data['data']['products'] ?? [];
}
$sellix_products = fetch_sellix_products($user_info['sellix_api_key'], $user_info['sellix_store_id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_trial'])) {
    if (SELLIX_TRIAL_URL !== '') {
        header('Location: ' . SELLIX_TRIAL_URL);
        exit();
    }
    $_SESSION['message'] = 'Free trials are not available right now.';
    redirect('/billing/index.php');
}

$credit_usage = fetch_credit_usage($pdo, $user_id);
$feature_usage = fetch_feature_usage($pdo, $user_id);
$message = flash('message');
?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advanced Billing Dashboard - BloxAuth</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.sellix.io/static/js/embed.js"></script>
</head>
<body class="h-full">
    <div class="min-h-full">
        <?php include '../includes/navbar.php'; ?>

        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <h1 class="text-3xl font-bold text-gray-900">Advanced Billing Dashboard</h1>
            </div>
        </header>

        <main class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
            <?php if ($message): ?>
                <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6" role="alert"><?= e($message) ?></div>
            <?php endif; ?>

            <!-- Roblox account and game pass credits -->
            <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-2">Roblox Account</h2>
                    <?php if ($roblox_account): ?>
                        <p class="text-sm text-gray-700">Linked: <strong><?= e($roblox_account['roblox_username']) ?></strong><?= $roblox_account['verified'] ? ' (verified)' : ' (not verified)' ?></p>
                    <?php else: ?>
                        <p class="text-sm text-gray-500">No Roblox account linked.</p>
                    <?php endif; ?>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a href="<?= e(url('/auth/link_roblox.php')) ?>" class="bg-indigo-600 text-white py-2 px-4 rounded hover:bg-indigo-700 text-sm">Link with Roblox</a>
                        <a href="<?= e(url('/billing/link_roblox.php')) ?>" class="bg-gray-200 text-gray-800 py-2 px-4 rounded hover:bg-gray-300 text-sm">Enter username manually</a>
                        <?php if (!$user_info['gamepass_claimed']): ?>
                        <form method="post" action="<?= e(url('/billing/payment.php')) ?>">
                            <button type="submit" name="purchase_gamepass" class="bg-green-600 text-white py-2 px-4 rounded hover:bg-green-700 text-sm">Claim game pass credits</button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Current Plan -->
            <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-2">Current Plan</h2>
                    <p class="text-sm text-gray-500">Your subscription and credit information</p>
                    <div class="mt-4 flex justify-between items-center">
                        <div>
                            <p class="text-2xl font-bold text-indigo-600"><?= e($user_info['subscription_tier'] ?: 'Free') ?></p>
                            <p class="text-sm text-gray-500">Expires: <?= $user_info['subscription_end_date'] ? date('Y-m-d', strtotime($user_info['subscription_end_date'])) : 'N/A' ?></p>
                        </div>
                        <div>
                            <p class="text-3xl font-bold text-green-600"><?= (int) $user_info['credits'] ?></p>
                            <p class="text-sm text-gray-500">Credits</p>
                        </div>
                    </div>
                    <div class="mt-4">
                        <div class="bg-gray-200 rounded-full h-2">
                            <div class="bg-indigo-600 rounded-full h-2" style="width: <?= min(100, max(0, ($user_info['credits'] / 1000) * 100)) ?>%;"></div>
                        </div>
                        <div class="flex justify-between text-sm text-gray-500 mt-1">
                            <span>0 credits</span>
                            <span>1000 credits</span>
                        </div>
                    </div>
                    <div class="mt-4">
                        <label class="inline-flex items-center">
                            <input type="checkbox" class="form-checkbox text-indigo-600" checked>
                            <span class="ml-2 text-sm text-gray-700">Auto-renew</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Credit Usage and Feature Usage -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Credit Usage -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-2">Credit Usage</h2>
                        <p class="text-sm text-gray-500">Your credit usage over the past months</p>
                        <canvas id="creditUsageChart" class="mt-4"></canvas>
                    </div>
                </div>

                <!-- Feature Usage -->
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h2 class="text-lg font-medium text-gray-900 mb-2">Feature Usage</h2>
                        <p class="text-sm text-gray-500">Credits used per feature</p>
                        <canvas id="featureUsageChart" class="mt-4"></canvas>
                    </div>
                </div>
            </div>

            <!-- Subscription Plans -->
            <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:p-6">
                    <h2 class="text-lg font-medium text-gray-900 mb-2">Subscription Plans</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-4">
                        <?php foreach ($subscription_tiers as $tier): ?>
                            <div class="border rounded-lg p-4">
                                <h3 class="text-xl font-semibold"><?= e($tier['name']) ?></h3>
                                <p class="text-2xl font-bold text-indigo-600 mt-2">$<?= number_format($tier['price'], 2) ?><span class="text-sm text-gray-500">/month</span></p>
                                <p class="text-sm text-gray-500 mt-2"><?= e($tier['description']) ?></p>
                                <ul class="mt-4 space-y-2">
                                    <?php
                                    $benefits = explode(',', $tier['benefits']);
                                    foreach ($benefits as $benefit):
                                    ?>
                                        <li class="flex items-center">
                                            <svg class="h-5 w-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            <?= e(trim($benefit)) ?>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                                <form method="post" class="mt-6">
                                    <input type="hidden" name="tier_id" value="<?= (int) $tier['id'] ?>">
                                    <button type="submit" name="subscribe" class="w-full bg-indigo-600 text-white py-2 px-4 rounded hover:bg-indigo-700 transition duration-200">
                                        <?= $user_info['subscription_tier'] === $tier['name'] ? 'Current Plan' : 'Subscribe' ?>
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Buy Tokens (Sellix.io Embed) -->
            <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
                <div class="px-4 py-5 sm:px-6">
                    <h2 class="text-lg font-medium text-gray-900">Buy Tokens</h2>
                </div>
                <div class="px-4 py-5 sm:p-6">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <?php foreach ($sellix_products as $product): ?>
                            <div class="border rounded-lg p-4 flex flex-col justify-between">
                                <div>
                                    <h3 class="text-lg font-semibold"><?= e($product['title']) ?></h3>
                                    <p class="text-2xl font-bold text-indigo-600">$<?= e($product['price'] ?? '') ?></p>
                                    <p class="text-sm text-gray-500 mt-2"><?= e($product['description'] ?? '') ?></p>
                                </div>
                                <button 
                                    class="w-full bg-indigo-600 text-white py-2 px-4 rounded hover:bg-indigo-700 transition duration-200 mt-4"
                                    data-sellix-product="<?= e($product['uniqid'] ?? '') ?>"
                                    type="submit"
                                >
                                    Buy Now
                                </button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <?php if ($trial_eligible): ?>
            <!-- Free Trial Offer -->
            <div class="bg-indigo-100 border-l-4 border-indigo-500 text-indigo-700 p-4 mb-6" role="alert">
                <p class="font-bold">Special Offer!</p>
                <p>Try our service for free for 14 days. No credit card required.</p>
                <form method="post" class="mt-4">
                    <button type="button" onclick="showToSModal()" class="bg-indigo-600 text-white py-2 px-4 rounded hover:bg-indigo-700 transition duration-200">
                        Start Free Trial
                    </button>
                </form>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- ToS Modal -->
    <div id="tosModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-medium text-gray-900">Terms of Service</h3>
            <p class="mt-2 text-sm text-gray-500">Please read and accept our Terms of Service before starting your free trial.</p>
            <div class="mt-4">
                <textarea class="w-full h-40 p-2 border rounded" readonly>
                **Terms of Service for BloxAuth Free Trial on Sellix.io**



These Terms of Service ("Terms") govern your use of the free trial of BloxAuth’s digital product licensing services ("Services") provided via Sellix.io. By using the free trial, you agree to the following terms and conditions. Please read them carefully before proceeding. If you do not agree to these Terms, do not use the free trial.

**1. Free Trial Eligibility**  
The BloxAuth free trial is available to users who meet the following criteria:
- You must be 18 years of age or older to access the free trial.
- You must not have previously used a free trial with BloxAuth.
- You must have an active Sellix.io account to access the embed functionality.

**2. Duration of Free Trial**  
The free trial lasts for 14 days, beginning from the date of account activation. After the trial period, access to the Services will automatically end unless a paid subscription is activated.

**3. Limitation of Liability**  
BloxAuth provides the free trial on an "AS IS" and "AS AVAILABLE" basis, without any warranties of any kind, either expressed or implied. BloxAuth does not guarantee that the free trial will be uninterrupted or error-free.  
- You understand and agree that BloxAuth shall not be liable for any direct, indirect, incidental, consequential, or exemplary damages resulting from your use of the free trial.
- BloxAuth is not responsible for any loss of data, revenue, or any other form of damage related to the use of the free trial.

**4. Limitation on Disputes and Abuse**  
BloxAuth reserves the right to terminate or restrict your access to the free trial at any time without notice if:
- We detect misuse, fraud, or any attempt to exploit the free trial, including but not limited to creating multiple accounts to extend the trial or bypassing the free trial limitations.
- You violate any of these Terms or engage in abusive behavior toward the BloxAuth platform or its staff.
  
Any disputes or claims arising from your use of the free trial will be subject to binding arbitration in accordance with the laws of [insert jurisdiction], and you agree to waive any rights to participate in a class action or seek damages exceeding the value of the free trial.

**5. Prohibited Use**  
You agree not to use the BloxAuth free trial to:
- Develop competing services or reverse-engineer the BloxAuth platform.
- Distribute, resell, or share your free trial access with unauthorized users.
- Engage in illegal activities or violate the rights of third parties.

**6. Data Usage and Privacy**  
During the free trial, BloxAuth may collect information about your usage of the platform to improve our services and offer a better user experience. Your use of the trial is also subject to our [Privacy Policy](insert link). By using the free trial, you consent to the collection and processing of your data in accordance with the policy.

**7. No Guarantee of Future Features or Access**  
Participation in the free trial does not guarantee any specific features, updates, or access to future versions of BloxAuth’s Services. Once the trial period ends, access to all Services will cease unless you choose to subscribe to a paid plan.

**8. Modifications to Terms**  
BloxAuth reserves the right to modify or update these Terms at any time. Any such changes will be posted on our website, and continued use of the free trial constitutes your acceptance of the updated Terms.






                </textarea>
            </div>
            <div class="mt-4 flex justify-end">
                <button onclick="hideToSModal()" class="bg-gray-300 text-gray-700 py-2 px-4 rounded mr-2 hover:bg-gray-400 transition">Cancel</button>
                <form method="post">
                    <button type="submit" name="start_trial" class="bg-indigo-600 text-white py-2 px-4 rounded hover:bg-indigo-700 transition">Accept & Start Trial</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showToSModal() {
            document.getElementById('tosModal').classList.remove('hidden');
        }

        function hideToSModal() {
            document.getElementById('tosModal').classList.add('hidden');
        }

        // Credit Usage Chart
        var ctxCredit = document.getElementById('creditUsageChart').getContext('2d');
        var creditChart = new Chart(ctxCredit, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_column($credit_usage, 'month')) ?>,
                datasets: [{
                    label: 'Credits Used',
                    data: <?= json_encode(array_column($credit_usage, 'credits')) ?>,
                    borderColor: 'rgb(79, 70, 229)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Feature Usage Chart
        var ctxFeature = document.getElementById('featureUsageChart').getContext('2d');
        var featureChart = new Chart(ctxFeature, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($feature_usage, 'feature')) ?>,
                datasets: [{
                    label: 'Credits Used',
                    data: <?= json_encode(array_column($feature_usage, 'credits')) ?>,
                    backgroundColor: 'rgba(79, 70, 229, 0.6)',
                    borderColor: 'rgb(79, 70, 229)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Initialize Sellix Embed
        if (window.Sellix) Sellix.init({
            merchant: <?= json_encode((string) $user_info['sellix_store_id']) ?>
        });
    </script>
</body>
</html>
