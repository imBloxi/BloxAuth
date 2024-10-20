<?php
session_start();
require '../includes/db.php';

// Function to check if the user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Function to check if the user is a staff member
function is_staff($pdo) {
    if (!is_logged_in()) {
        return false;
    }
    $stmt = $pdo->prepare('SELECT is_staff FROM users WHERE id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    return $user && $user['is_staff'];
}

// Check if the user is logged in and is a staff member
if (!is_staff($pdo)) {
    header('Location: ../auth/login.php');
    exit;
}

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Fetch all applications
    $stmt = $pdo->prepare('SELECT * FROM applications');
    $stmt->execute();
    $applications = $stmt->fetchAll();
} catch (PDOException $e) {
    echo 'Database error: ' . $e->getMessage();
} catch (Exception $e) {
    echo 'General error: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en" class="bg-[#09090d] text-white overflow-x-hidden">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Applications</title>
    <link rel="stylesheet" href="https://cdn.keyauth.cc/v3/scripts/animate.min.css" />
    <link rel="stylesheet" href="https://cdn.keyauth.cc/v3/dist/output.css">
</head>
<body>
    <header>
        <nav class="border-gray-200 px-4 lg:px-6 py-2.5 mb-14">
            <div class="flex flex-wrap justify-between items-center mx-auto max-w-screen-xl">
                <a href="../" class="flex items-center">
                    <!-- Optional logo or brand name -->
                </a>
                <div class="flex items-center lg:order-2">
                    <a href="../auth/logout.php" class="text-white font-medium text-sm px-4 py-2 lg:px-5 lg:py-2.5 mr-2 bg-red-600 rounded-lg hover:opacity-80 transition duration-200">
                        Logout
                    </a>
                </div>
            </div>
        </nav>
    </header>

    <main>
        <section class="py-8 max-w-screen-xl px-4 mx-auto lg:py-16">
            <h2 class="mb-7 md:mb-12 text-3xl md:text-6xl font-bold font-heading tracking-px-n leading-tight text-center">
                Review Applications
            </h2>
            <div class="max-w-md mx-auto bg-[#0f0f17] rounded-xl p-6">
                <?php if ($applications): ?>
                    <?php foreach ($applications as $application): ?>
                        <div class="mb-4 p-4 bg-gray-800 rounded-lg">
                            <h3 class="text-xl font-bold"><?php echo htmlspecialchars($application['username']); ?></h3>
                            <p><strong>Email:</strong> <?php echo htmlspecialchars($application['email']); ?></p>
                            <p><strong>Application:</strong></p>
                            <p><?php echo nl2br(htmlspecialchars($application['application_text'])); ?></p>
                            <form method="post" action="process_application.php">
                                <input type="hidden" name="username" value="<?php echo htmlspecialchars($application['username']); ?>">
                                <button type="submit" name="action" value="accept" class="btn-primary w-full py-2 mt-2 text-base font-medium text-white bg-green-700 rounded-lg hover:bg-green-800 transition duration-200">Accept</button>
                                <button type="submit" name="action" value="reject" class="btn-primary w-full py-2 mt-2 text-base font-medium text-white bg-red-700 rounded-lg hover:bg-red-800 transition duration-200">Reject</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No applications to review.</p>
                <?php endif; ?>
            </div>
        </section>
    </main>

    <footer class="bg-[#0f0f17] text-white py-6 px-4 mt-16">
        <div class="max-w-screen-xl mx-auto text-center">
            <p>&copy; 2024 BloxAuth. All rights reserved.</p>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.3/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
