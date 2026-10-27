<?php
require_once '../includes/config.php';

$privacy_policy = [
    'updated_at' => '2026-10-27',
    'content' => '<p>' . e(APP_NAME) . ' stores the account details you give us (username, email, hashed password), '
        . 'the licenses you create, and logs of license checks, logins and payments. We use this data only to run the service, '
        . 'and we do not sell it. You can delete your account at any time from your settings.</p>',
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - <?php echo e(APP_NAME); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-6">Privacy Policy Information Hub</h1>
        
        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-semibold mb-4">Our Privacy Policy</h2>
            <p class="mb-4">Last updated: <?php echo date('F j, Y', strtotime($privacy_policy['updated_at'])); ?></p>
            <div class="prose max-w-none">
                <?php echo $privacy_policy['content']; ?>
            </div>
        </div>

        <div class="bg-white shadow-md rounded-lg p-6 mb-6">
            <h2 class="text-2xl font-semibold mb-4">Your Data Rights</h2>
            <ul class="list-disc pl-5">
                <li>Right to access your personal data</li>
                <li>Right to rectify inaccurate personal data</li>
                <li>Right to erasure ('right to be forgotten')</li>
                <li>Right to restrict processing</li>
                <li>Right to data portability</li>
                <li>Right to object to processing</li>
            </ul>
        </div>

        <div class="bg-white shadow-md rounded-lg p-6">
            <h2 class="text-2xl font-semibold mb-4">Contact Us</h2>
            <p>If you have any questions about our privacy policy or your data rights, please contact our Data Protection Officer:</p>
            <p class="mt-2">
                <strong>Email:</strong> dpo@<?php echo e($_SERVER['HTTP_HOST'] ?? 'example.com'); ?><br>
                <strong>Address:</strong> [Your Company Address]
            </p>
        </div>
    </div>
</body>
</html>
