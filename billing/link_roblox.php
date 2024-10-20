<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

redirect_if_not_logged_in();

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $roblox_username = $_POST['roblox_username'];

    // Perform a basic validation
    if (empty($roblox_username)) {
        $_SESSION['message'] = 'Roblox username cannot be empty.';
        header('Location: link_roblox.php');
        exit;
    }

    // Call Roblox API to verify the username (Replace with actual API logic)
    $verified = verify_roblox_username($roblox_username);

    if ($verified) {
        // Store the Roblox username in the database with the current timestamp
        $stmt = $pdo->prepare("INSERT INTO roblox_accounts (id, roblox_username, linked_at) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE roblox_username = VALUES(roblox_username), linked_at = VALUES(linked_at)");
        $stmt->execute([$user_id, $roblox_username, date('Y-m-d H:i:s')]);

        $_SESSION['message'] = 'Roblox account linked successfully!';
        header('Location: ../billing/index.php');
        exit;
    } else {
        $_SESSION['message'] = 'Failed to verify Roblox username.';
    }
}

function verify_roblox_username($username) {
    // Implement actual Roblox username verification logic here
    // For now, we will assume the verification is always successful
    return true;
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
                        <?php echo htmlspecialchars($_SESSION['message']); ?>
                        <?php unset($_SESSION['message']); ?>
                    </div>
                <?php endif; ?>
                <form action="link_roblox.php" method="post" class="space-y-4">
                    <div>
                        <label for="roblox_username" class="block mb-2 text-sm font-medium text-white">Roblox Username:</label>
                        <input type="text" name="roblox_username" id="roblox_username" required class="form-control block w-full p-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white">
                        <label for="user_id" class="block mb-2 text-sm font-medium text-white">Roblox user_id:</label>
                        <input type="text" name="id" id="id" required class="form-control block w-full p-2.5 bg-gray-800 border border-gray-700 rounded-lg text-white">
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
