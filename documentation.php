<?php
require_once 'includes/config.php';
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BloxAuth Documentation</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/themes/prism-okaidia.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.24.1/components/prism-sql.min.js"></script>
</head>
<body class="h-full">
    <div class="min-h-full">
        <nav class="bg-white shadow-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between h-16">
                    <div class="flex">
                        <div class="flex-shrink-0 flex items-center">
                            <a href="index.php">
                                <img class="h-8 w-auto" src="https://i.ibb.co/zXpTQX4/8-Y23-HTU-2-removebg-preview.png" alt="BloxAuth Logo">
                            </a>
                        </div>
                        <div class="hidden sm:ml-6 sm:flex sm:space-x-8">
                            <a href="index.php" class="border-transparent text-gray-500 hover:border-gray-300 hover:text-gray-700 inline-flex items-center px-1 pt-1 border-b-2 text-sm font-medium">Home</a>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <main>
            <div class="py-12 bg-white">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <h1 class="text-3xl font-extrabold text-gray-900">BloxAuth API Documentation</h1>
                    <div class="mt-6 text-xl text-gray-500">
                        <p>How to create and check BloxAuth licenses from your own tools and from Roblox games. All responses are JSON.</p>
                    </div>

                    <div class="mt-10">
                        <h2 class="text-2xl font-bold text-gray-900">Authentication</h2>
                        <p class="mt-2 text-gray-500">Management endpoints need the API key shown on your dashboard. Send it in either header:</p><pre><code class="language-http">X-API-Key: YOUR_API_KEY
Authorization: Bearer YOUR_API_KEY
</code></pre><p class="mt-2 text-gray-500">License checks from games do not use the API key: the license key is the credential, so never put your API key in a game script.</p>
                    </div>

                    <div class="mt-10">
                        <h2 class="text-2xl font-bold text-gray-900">Validate a license (from a game)</h2>
                        <p class="mt-2 text-gray-500">Call this from a Roblox server script. <code>roblox_id</code> is the user (or group) that owns the game, and <code>place_id</code> is the running place.</p><pre><code class="language-http">POST /api/validate_key.php
Content-Type: application/x-www-form-urlencoded   (JSON also accepted)

license_key=YOUR_LICENSE_KEY&roblox_id=12345678&place_id=87654321

Response:
{"status": "success"}
{"status": "failure", "message": "License has expired"}
</code></pre><p class="mt-2 text-gray-500">A key passes when it is not revoked, banned, expired or over its use limit, and its binding matches: a <em>user</em> license must match <code>roblox_id</code>, a <em>group</em> license must match the group ID sent as <code>roblox_id</code>, and a <em>place</em> license must match <code>place_id</code>. Every successful check counts as one use and is logged. See <code>api/lua.lua</code> for a ready-made client.</p>
                    </div>

                    <div class="mt-10">
                        <h2 class="text-2xl font-bold text-gray-900">Create a license</h2>
                        <pre><code class="language-http">POST /api/generate_license.php
X-API-Key: YOUR_API_KEY
Content-Type: application/json

{
    "whitelist_type": "user",          // user, group or place
    "roblox_user_id": "12345678",      // for user licenses
    "whitelist_id": "87654321",        // group or place ID for group/place licenses
    "valid_until": "2027-01-01",       // optional, omit for no expiry
    "max_uses": 5,                     // optional, omit for unlimited
    "description": "Pro tier",
    "custom_tier": "Pro",
    "is_transferable": false
}

Response (201):
{"success": true, "license_key": "3f9c..."}
</code></pre>
                    </div>

                    <div class="mt-10">
                        <h2 class="text-2xl font-bold text-gray-900">Manage licenses</h2>
                        <p class="mt-2 text-gray-500">All actions only touch licenses owned by the API key.</p><pre><code class="language-http">GET    /api/v1/licenses/index.php?type=list[&search=...&whitelist_type=user]
POST   /api/v1/licenses/index.php?type=create          (same body as above)
PUT    /api/v1/licenses/index.php?type=renew           {"license_id": 1, "new_valid_until": "2027-06-01"}
POST   /api/v1/licenses/index.php?type=ban             {"license_id": 1, "ban_reason": "Chargeback"}
DELETE /api/v1/licenses/index.php?type=revoke&license_id=1
</code></pre><p class="mt-2 text-gray-500">To check whether one of your keys is active: <code>POST /api/whitelist.php</code> with <code>{"license_key": "..."}</code>.</p>
                    </div>

                    <div class="mt-10">
                        <h2 class="text-2xl font-bold text-gray-900">Errors and rate limits</h2>
                        <p class="mt-2 text-gray-500">Errors use standard HTTP status codes (400, 401, 404, 405, 429) with a JSON body containing <code>error</code> or <code>message</code>. Game-facing endpoints allow <?php echo (int) API_RATE_LIMIT; ?> requests per minute per IP address and return 429 above that.</p>
                    </div>

                    <div class="mt-10">
                        <h2 class="text-2xl font-bold text-gray-900">Sellix</h2>
                        <p class="mt-2 text-gray-500">To sell licenses through your Sellix store, add your Sellix API key and webhook secret in Settings, create products under Sellix Products, and point the Sellix webhook at <code>/api/sellix_webhook.php</code>. Each paid order creates one license.</p>
                    </div>
                </div>
            </div>
        </main>

        <footer class="bg-white">
            <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
                <p class="text-center text-base text-gray-400">&copy; <?php echo date('Y'); ?> BloxAuth. All rights reserved.</p>
            </div>
        </footer>
    </div>
</body>
</html>
