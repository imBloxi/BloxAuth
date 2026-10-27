<?php
// Link a Roblox account by username. The name is checked against Roblox, but
// ownership is not proven, so these links stay unverified (see auth/link_roblox.php).
require_once '../includes/config.php';
redirect_if_not_logged_in();

$user_id = $_SESSION['user_id'];

// Look up a Roblox user by username. Returns ['id', 'name'] or null.
function lookup_roblox_user($username) {
    $ch = curl_init('https://users.roblox.com/v1/usernames/users');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode(['usernames' => [$username], 'excludeBannedUsers' => true]),
    ]);
    $response = curl_exec($ch);
    curl_close($ch);
    $data = json_decode((string) $response, true);
    return $data['data'][0] ?? null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roblox_username = trim($_POST['roblox_username'] ?? '');

    if ($roblox_username === '') {
        $_SESSION['message'] = 'Roblox username cannot be empty.';
        redirect('/billing/link_roblox.php');
    }

    $roblox_user = lookup_roblox_user($roblox_username);
    if (!$roblox_user) {
        $_SESSION['message'] = 'Could not find that Roblox user.';
        redirect('/billing/link_roblox.php');
    }

    $pdo->prepare("INSERT INTO roblox_accounts (user_id, roblox_username, roblox_user_id, verified, linked_at) VALUES (?, ?, ?, 0, NOW())
                   ON DUPLICATE KEY UPDATE roblox_username = VALUES(roblox_username), roblox_user_id = VALUES(roblox_user_id), verified = 0, linked_at = NOW()")
        ->execute([$user_id, $roblox_user['name'], $roblox_user['id']]);

    $_SESSION['message'] = 'Roblox account linked. Use "Link with Roblox" to verify it.';
    redirect('/billing/index.php');
}
?>

<!DOCTYPE html>
<html lang="en" class="bg-[#09090d] text-white overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link Roblox Account</title>
    <link rel="stylesheet" href="https://cdn.keyauth.cc/v3/scripts/animate.min.css">
    <link rel="stylesheet" href="https://cdn.keyauth.cc/v3/dist/output.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <main>
        <section class="py-8 max-w-screen-xl px-4 mx-auto lg:py-16">
            <h2 class="mb-7 md:mb-12 text-3xl md:text-6xl font-bold font-heading tracking-px-n leading-tight text-center">
                Link Roblox Account
            </h2>
            <div class="max-w-md mx-auto bg-[#0f0f17] rounded-xl p-6">
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="bg-red-600 text-white p-3 rounded mb-4">
                        <?php echo e($_SESSION['message']); ?>
                        <?php unset($_SESSION['message']); ?>
                    </div>
                <?php endif; ?>
                <form action="link_roblox.php" method="post" class="space-y-4">
                    <div>
                        <label for="roblox_username" class="block mb-2 text-sm font-medium text-white">Roblox Username:</label>
                        <input type="text" name="roblox_username" id="roblox_username" required class="form-control block w-full p-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white">
                    </div>
                    <button type="submit" class="btn-primary w-full py-3 text-base font-medium text-white bg-blue-700 rounded-lg hover:bg-blue-800 transition duration-200">Link Account</button>
                </form>
                <a href="../billing/index.php" class="block text-blue-600 hover:underline mt-4">Back to Billing</a>
            </div>
        </section>
    </main>

    <?php include '../includes/footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
