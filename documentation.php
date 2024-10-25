<?php
require_once 'includes/config.php';
require_once 'includes/functions.php';
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
                        <p>Welcome to the comprehensive BloxAuth API documentation. This guide provides detailed information on how to interact with our RESTful API for managing Roblox game licenses and user authentication.</p>
                    </div>
                    
                    <div class="mt-10">
                        <h2 class="text-2xl font-bold text-gray-900">API Overview</h2>
                        <p class="mt-4 text-gray-500">The BloxAuth API allows you to programmatically manage licenses, validate user access, handle user authentication, and integrate licensing functionality into your Roblox games. Our API uses standard HTTP response codes and returns JSON-encoded responses.</p>
                    </div>

                    <div class="mt-10">
                        <h2 class="text-2xl font-bold text-gray-900">Authentication</h2>
                        <p class="mt-4 text-gray-500">To use the BloxAuth API, you need to include your API key in the header of each request:</p>
                        <pre><code class="language-http">
Authorization: Bearer YOUR_API_KEY
                        </code></pre>
                    </div>

                    <div class="mt-10">
                        <h2 class="text-2xl font-bold text-gray-900">API Endpoints</h2>
                        
                        <h3 class="mt-6 text-xl font-semibold text-gray-900">1. License Management</h3>
                        
                        <h4 class="mt-4 text-lg font-semibold text-gray-900">1.1 Create License</h4>
                        <p class="mt-2 text-gray-500">Creates a new license for a user.</p>
                        <pre><code class="language-http">
POST /api/v1/licenses

Request Body:
{
    "user_id": 123,
    "whitelist_id": "abc123",
    "whitelist_type": "user",
    "description": "Premium license",
    "valid_until": "2023-12-31T23:59:59Z",
    "max_uses": 5,
    "features": ["feature1", "feature2"],
    "custom_fields": {
        "field1": "value1",
        "field2": "value2"
    }
}

Response:
{
    "success": true,
    "data": {
        "license_key": "XXXX-XXXX-XXXX-XXXX",
        "expires_at": "2023-12-31T23:59:59Z",
        "features": ["feature1", "feature2"],
        "custom_fields": {
            "field1": "value1",
            "field2": "value2"
        }
    }
}
                        </code></pre>

                        <h4 class="mt-4 text-lg font-semibold text-gray-900">1.2 Validate License</h4>
                        <p class="mt-2 text-gray-500">Validates a license key.</p>
                        <pre><code class="language-http">
GET /api/v1/licenses/validate/{license_key}

Response:
{
    "success": true,
    "data": {
        "is_valid": true,
        "expires_at": "2023-12-31T23:59:59Z",
        "whitelist_type": "user",
        "whitelist_id": "abc123",
        "features": ["feature1", "feature2"],
        "custom_fields": {
            "field1": "value1",
            "field2": "value2"
        }
    }
}
                        </code></pre>

                        <h4 class="mt-4 text-lg font-semibold text-gray-900">1.3 Get License Details</h4>
                        <p class="mt-2 text-gray-500">Retrieves detailed information about a specific license.</p>
                        <pre><code class="language-http">
GET /api/v1/licenses/{license_key}

Response:
{
    "success": true,
    "data": {
        "license_key": "XXXX-XXXX-XXXX-XXXX",
        "user_id": 123,
        "whitelist_id": "abc123",
        "whitelist_type": "user",
        "description": "Premium license",
        "valid_until": "2023-12-31T23:59:59Z",
        "max_uses": 5,
        "current_uses": 2,
        "features": ["feature1", "feature2"],
        "custom_fields": {
            "field1": "value1",
            "field2": "value2"
        },
        "created_at": "2023-01-01T00:00:00Z",
        "updated_at": "2023-01-02T00:00:00Z"
    }
}
                        </code></pre>

                        <h4 class="mt-4 text-lg font-semibold text-gray-900">1.4 Update License</h4>
                        <p class="mt-2 text-gray-500">Updates an existing license.</p>
                        <pre><code class="language-http">
PUT /api/v1/licenses/{license_key}

Request Body:
{
    "description": "Updated Premium license",
    "valid_until": "2024-12-31T23:59:59Z",
    "max_uses": 10,
    "features": ["feature1", "feature2", "feature3"],
    "custom_fields": {
        "field1": "updated_value1",
        "field3": "new_value"
    }
}

Response:
{
    "success": true,
    "message": "License updated successfully",
    "data": {
        "license_key": "XXXX-XXXX-XXXX-XXXX",
        "description": "Updated Premium license",
        "valid_until": "2024-12-31T23:59:59Z",
        "max_uses": 10,
        "features": ["feature1", "feature2", "feature3"],
        "custom_fields": {
            "field1": "updated_value1",
            "field2": "value2",
            "field3": "new_value"
        }
    }
}
                        </code></pre>

                        <h4 class="mt-4 text-lg font-semibold text-gray-900">1.5 Revoke License</h4>
                        <p class="mt-2 text-gray-500">Revokes an active license.</p>
                        <pre><code class="language-http">
DELETE /api/v1/licenses/{license_key}

Response:
{
    "success": true,
    "message": "License revoked successfully"
}
                        </code></pre>

                        <h4 class="mt-4 text-lg font-semibold text-gray-900">1.6 List Licenses</h4>
                        <p class="mt-2 text-gray-500">Retrieves a list of licenses for a specific user or all licenses.</p>
                        <pre><code class="language-http">
GET /api/v1/licenses?user_id=123&page=1&limit=20

Response:
{
    "success": true,
    "data": {
        "licenses": [
            {
                "license_key": "XXXX-XXXX-XXXX-XXXX",
                "description": "Premium license",
                "valid_until": "2023-12-31T23:59:59Z",
                "max_uses": 5,
                "current_uses": 2
            },
            // ... more licenses
        ],
        "total": 50,
        "page": 1,
        "limit": 20
    }
}
                        </code></pre>

                        <h3 class="mt-6 text-xl font-semibold text-gray-900">2. User Management</h3>

                        <h4 class="mt-4 text-lg font-semibold text-gray-900">2.1 Create User</h4>
                        <p class="mt-2 text-gray-500">Creates a new user account.</p>
                        <pre><code class="language-http">
POST /api/v1/users

Request Body:
{
    "username": "johndoe",
    "email": "john@example.com",
    "password": "securepassword123",
    "role": "user"
}

Response:
{
    "success": true,
    "data": {
        "user_id": 124,
        "username": "johndoe",
        "email": "john@example.com",
        "role": "user",
        "created_at": "2023-06-01T00:00:00Z"
    }
}
                        </code></pre>

                        <h4 class="mt-4 text-lg font-semibold text-gray-900">2.2 Get User Details</h4>
                        <p class="mt-2 text-gray-500">Retrieves details of a specific user.</p>
                        <pre><code class="language-http">
GET /api/v1/users/{user_id}

Response:
{
    "success": true,
    "data": {
        "user_id": 124,
        "username": "johndoe",
        "email": "john@example.com",
        "role": "user",
        "created_at": "2023-06-01T00:00:00Z",
        "last_login": "2023-06-02T12:00:00Z"
    }
}
                        </code></pre>

                        <h4 class="mt-4 text-lg font-semibold text-gray-900">2.3 Update User</h4>
                        <p class="mt-2 text-gray-500">Updates an existing user's information.</p>
                        <pre><code class="language-http">
PUT /api/v1/users/{user_id}

Request Body:
{
    "email": "newemail@example.com",
    "role": "admin"
}

Response:
{
    "success": true,
    "message": "User updated successfully",
    "data": {
        "user_id": 124,
        "username": "johndoe",
        "email": "newemail@example.com",
        "role": "admin"
    }
}
                        </code></pre>

                        <h4 class="mt-4 text-lg font-semibold text-gray-900">2.4 Delete User</h4>
                        <p class="mt-2 text-gray-500">Deletes a user account.</p>
                        <pre><code class="language-http">
DELETE /api/v1/users/{user_id}

Response:
{
    "success": true,
    "message": "User deleted successfully"
}
                        </code></pre>

                        <h3 class="mt-6 text-xl font-semibold text-gray-900">3. Authentication</h3>

                        <h4 class="mt-4 text-lg font-semibold text-gray-900">3.1 User Login</h4>
                        <p class="mt-2 text-gray-500">Authenticates a user and returns a JWT token.</p>
                        <pre><code class="language-http">
POST /api/v1/auth/login

Request Body:
{
    "username": "johndoe",
    "password": "securepassword123"
}

Response:
{
    "success": true,
    "data": {
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
        "expires_at": "2023-07-01T00:00:00Z"
    }
}
                        </code></pre>

                        <h4 class="mt-4 text-lg font-semibold text-gray-900">3.2 Refresh Token</h4>
                        <p class="mt-2 text-gray-500">Refreshes an existing JWT token.</p>
                        <pre><code class="language-http">
POST /api/v1/auth/refresh

Request Body:
{
    "refresh_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."
}

Response:
{
    "success": true,
    "data": {
        "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
        "expires_at": "2023-08-01T00:00:00Z"
    }
}
                        </code></pre>

                        <h4 class="mt-4 text-lg font-semibold text-gray-900">3.3 Logout</h4>
                        <p class="mt-2 text-gray-500">Invalidates the current JWT token.</p>
                        <pre><code class="language-http">
POST /api/v1/auth/logout

Response:
{
    "success": true,
    "message": "Logged out successfully"
}
                        </code></pre>

                        <h3 class="mt-6 text-xl font-semibold text-gray-900">4. Webhooks</h3>

                        <h4 class="mt-4 text-lg font-semibold text-gray-900">4.1 Create Webhook</h4>
                        <p class="mt-2 text-gray-500">Creates a new webhook endpoint.</p>
                        <pre><code class="language-http">
POST /api/v1/webhooks

Request Body:
{
    "url": "https://example.com/webhook",
    "events": ["license.created", "license.revoked"]
}

Response:
{
    "success": true,
    "data": {
        "webhook_id": "webhook_123",
        "url": "https://example.com/webhook",
        "events": ["license.created", "license.revoked"],
        "created_at": "2023-06-01T00:00:00Z"
    }
}
                        </code></pre>

                        <h4 class="mt-4 text-lg font-semibold text-gray-900">4.2 List Webhooks</h4>
                        <p class="mt-2 text-gray-500">Retrieves a list of configured webhooks.</p>
                        <pre><code class="language-http">
GET /api/v1/webhooks

Response:
{
    "success": true,
    "data": {
        "webhooks": [
            {
                "webhook_id": "webhook_123",
                "url": "https://example.com/webhook",
                "events": ["license.created", "license.revoked"],
                "created_at": "2023-06-01T00:00:00Z"
            },
            // ... more webhooks
        ]
    }
}
                        </code></pre>

                        <h4 class="mt-4 text-lg font-semibold text-gray-900">4.3 Delete Webhook</h4>
                        <p class="mt-2 text-gray-500">Deletes a configured webhook.</p>
                        <pre><code class="language-http">
DELETE /api/v1/webhooks/{webhook_id}

Response:
{
    "success": true,
    "message": "Webhook deleted successfully"
}
                        </code></pre>
                    </div>

                    <div class="mt-10">
                        <h2 class="text-2xl font-bold text-gray-900">Error Handling</h2>
                        <p class="mt-4 text-gray-500">The API uses standard HTTP status codes to indicate the success or failure of requests. In case of an error, you'll receive a JSON response with an error message:</p>
                        <pre><code class="language-json">
{
    "success": false,
    "error": {
        "code": "ERROR_CODE",
        "message": "Detailed error message",
        "details": {
            // Additional error details if available
        }
    }
}
                        </code></pre>
                    </div>

                    <div class="mt-10">
                        <h2 class="text-2xl font-bold text-gray-900">Rate Limiting</h2>
                        <p class="mt-4 text-gray-500">To ensure fair usage, our API implements rate limiting. You can make up to 100 requests per minute. If you exceed this limit, you'll receive a 429 Too Many Requests response. The response will include the following headers:</p>
                        <pre><code class="language-http">
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1623456789
                        </code></pre>
                    </div>

                    <div class="mt-10">
                        <h2 class="text-2xl font-bold text-gray-900">Webhooks</h2>
                        <p class="mt-4 text-gray-500">BloxAuth supports webhooks for real-time notifications. You can configure webhooks in your account settings to receive updates on various events. Webhook payloads will be sent as POST requests to your specified URL with the following structure:</p>
                        <pre><code class="language-json">
{
    "event": "license.created",
    "timestamp": "2023-06-01T00:00:00Z",
    "data": {
        // Event-specific data
    }
}
                        </code></pre>
                    </div>

                    <div class="mt-10">
                        <h2 class="text-2xl font-bold text-gray-900">SDK and Code Samples</h2>
                        <p class="mt-4 text-gray-500">We provide SDKs and code samples for easy integration with popular programming languages and frameworks. Visit our <a href="https://github.com/bloxauth/sdk" class="text-blue-600 hover:underline">GitHub repository</a> for more information and examples in various languages including PHP, Python, JavaScript, and C#.</p>
                    </div>

                    <div class="mt-10">
                        <h2 class="text-2xl font-bold text-gray-900">Changelog</h2>
                        <p class="mt-4 text-gray-500">Stay updated with the latest changes and improvements to our API:</p>
                        <ul class="list-disc list-inside mt-2 text-gray-500">
                            <li>v1.2.0 (2023-06-01): Added support for custom fields in licenses</li>
                            <li>v1.1.0 (2023-05-15): Introduced webhook functionality</li>
                            <li>v1.0.0 (2023-05-01): Initial release of the BloxAuth API</li>
                        </ul>
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
