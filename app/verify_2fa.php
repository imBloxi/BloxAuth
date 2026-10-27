<?php
require_once '../includes/config.php';
redirect_if_not_logged_in();

if (!isset($_SESSION['qrCodeUrl'])) {
    redirect('/app/settings.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify 2FA</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <h1>Verify Two-Factor Authentication</h1>
    <?php
    if (isset($_SESSION['message'])) {
        echo "<p>" . e($_SESSION['message']) . "</p>";
        unset($_SESSION['message']);
    }
    ?>
    <img src="<?php echo e($_SESSION['qrCodeUrl']); ?>" alt="QR Code">
    <form action="verify_2fa.php" method="post">
        <label for="token">Enter the code from your 2FA app:</label>
        <input type="text" name="token" id="token" required>
        <button type="submit">Verify</button>
    </form>
</body>
</html>
