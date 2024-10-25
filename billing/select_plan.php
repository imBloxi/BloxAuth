<?php
require_once '../includes/config.php';
redirect_if_not_logged_in();

$user_id = $_SESSION['user_id'];

// Fetch available billing plans
$stmt = $pdo->prepare("SELECT * FROM billing_plans ORDER BY price ASC");
$stmt->execute();
$billing_plans = $stmt->fetchAll();

// Handle plan selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plan_id'])) {
    $plan_id = $_POST['plan_id'];
    $stmt = $pdo->prepare("SELECT * FROM billing_plans WHERE id = ?");
    $stmt->execute([$plan_id]);
    $selected_plan = $stmt->fetch();

    if ($selected_plan) {
        // Here you would typically integrate with a payment gateway
        // For this example, we'll just update the user's plan
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime('+1 month'));

        $stmt = $pdo->prepare("INSERT INTO user_billing (user_id, plan_id, start_date, end_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $plan_id, $start_date, $end_date]);

        // Update user's credits
        $stmt = $pdo->prepare("UPDATE users SET credits = credits + ? WHERE id = ?");
        $stmt->execute([$selected_plan['credits'], $user_id]);

        $_SESSION['success_message'] = "You have successfully subscribed to the " . $selected_plan['name'] . " plan!";
        header("Location: " . APP_URL . "/app/dashboard.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" class="bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Billing Plan - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="max-w-4xl w-full space-y-8 p-10 bg-white rounded-xl shadow-lg z-10">
            <div class="text-center">
                <h2 class="mt-6 text-3xl font-bold text-gray-900">
                    Select Your Billing Plan
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Choose the plan that best fits your needs
                </p>
            </div>
            <div class="mt-8 space-y-6">
                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <?php foreach ($billing_plans as $plan): ?>
                        <div class="border rounded-lg p-6 flex flex-col">
                            <h3 class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($plan['name']); ?></h3>
                            <p class="mt-4 flex items-baseline text-gray-900">
                                <span class="text-5xl font-extrabold tracking-tight">$<?php echo number_format($plan['price'], 2); ?></span>
                                <span class="ml-1 text-xl font-semibold">/month</span>
                            </p>
                            <p class="mt-6 text-gray-500"><?php echo htmlspecialchars($plan['features']); ?></p>
                            <div class="mt-6 bg-gray-50 rounded-md px-6 py-4">
                                <p class="text-sm text-gray-700">
                                    <span class="font-medium"><?php echo number_format($plan['credits']); ?></span> credits included
                                </p>
                            </div>
                            <form method="POST" class="mt-8">
                                <input type="hidden" name="plan_id" value="<?php echo $plan['id']; ?>">
                                <button type="submit" class="w-full flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                    Select Plan
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>