# BloxAuth License Management System

A robust PHP-based license management system designed specifically for Roblox game developers, featuring a modular structure with dedicated components for administration, API handling, and billing management.

## Project Structure

```
bloxauth/
├── .idea/
├── admin/
│   ├── index.php
│   └── issue_keys.php
├── api/
│   ├── .htaccess
│   ├── api.php
│   ├── error_log/
│   ├── generate_license.php
│   ├── log_usage.php
│   ├── lua.lua
│   ├── package-lock.json
│   ├── package.json
│   ├── proxy.js
│   ├── sellix_webhook.php
│   ├── validate_key.php
│   └── whitelist.php
├── app/
│   ├── dashboard.php
│   ├── error_log/
│   ├── index.php
│   ├── license.php
│   ├── link_discord.php
│   ├── mark_notification.php
│   ├── moderation.php
│   ├── process_application.php
│   ├── review_applications.php
│   ├── sellix_products.php
│   ├── settings.php
│   ├── setup_app.php
│   ├── staff.php
│   ├── update_passkey.php
│   ├── user_hub.php
│   └── verify_2fa.php
├── assets/
├── auth/
├── billing/
│   ├── confirm_payment.php
│   ├── error_log/
│   ├── index.php
│   ├── link_roblox.php
│   ├── payment.php
│   ├── process_payment.php
│   ├── request_gamepass.php
│   └── verify_payment.php
├── css/
├── delete_account/
├── includes/
│   ├── db.php
│   ├── footer.php
│   ├── functions.php
│   ├── header.php
│   ├── navbar.php
│   └── sellix_integration.php
├── not-approved/
└── obfuscate/
```

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Apache with mod_rewrite enabled
- PDO PHP Extension
- JSON PHP Extension

## Database Configuration

The system uses PDO for database connections. Configuration is stored in `includes/config.php`:

```php
<?php
$host = 'localhost';
$db = 'roblox_licensing';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    throw new \PDOException($e->getMessage(), (int)$e->getCode());
}
?>
```

## Core Components

### Administration Module (`/admin`)
- `index.php`: Main administration dashboard
- `issue_keys.php`: License key management interface

### API Module (`/api`)
- `validate_key.php`: License validation endpoint
- `generate_license.php`: License generation endpoint
- `log_usage.php`: Usage tracking
- `sellix_webhook.php`: Payment integration with Sellix
- `whitelist.php`: IP whitelisting management

### Application Module (`/app`)
- `dashboard.php`: User dashboard
- `license.php`: License management
- `user_hub.php`: User profile and settings
- `verify_2fa.php`: Two-factor authentication
- `settings.php`: Application settings

### Billing Module (`/billing`)
- `payment.php`: Payment processing
- `confirm_payment.php`: Payment confirmation
- `link_roblox.php`: Roblox account linking
- `verify_payment.php`: Payment verification

### Security Features

1. **API Security**
   - `.htaccess` configuration for API protection
   - Request validation
   - IP whitelisting

2. **User Authentication**
   - Two-factor authentication
   - Session management
   - Password security

3. **Payment Processing**
   - Secure Sellix.io integration
   - Payment verification
   - Webhook handling

## API Endpoints

### License Validation
```http
POST /api/validate_key.php
Content-Type: application/json

{
    "license_key": "XXXX-XXXX-XXXX-XXXX",
    "roblox_id": "12345678",
    "place_id": "87654321"
}
```

### Generate License
```http
POST /api/generate_license.php
Content-Type: application/json

{
    "user_id": "12345",
    "license_type": "premium",
    "duration": "30"
}
```

## Integration Components

### Sellix Integration
```php
// includes/sellix_integration.php
require_once 'config.php';

function process_sellix_webhook($payload) {
    global $pdo;
    // Webhook processing logic
}
```

### Discord Integration
- `link_discord.php`: Discord account linking
- OAuth2 authentication flow
- Role synchronization

## User Management

1. **Registration Flow**
   - Account creation
   - Email verification
   - Optional 2FA setup

2. **License Management**
   - License issuance
   - Usage tracking
   - Renewal handling

3. **Staff Management**
   - Moderation tools
   - Application review system
   - Staff permissions

## Development Guidelines

1. **Code Style**
   - Follow PSR-12 coding standards
   - Use prepared statements for database queries
   - Implement proper error handling

2. **Security Practices**
   - Input validation
   - Output sanitization
   - CSRF protection
   - XSS prevention

3. **Documentation**
   - Code comments
   - API documentation
   - Change log maintenance

## Error Handling

```php
try {
    // Operation code
} catch (\PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    // Handle error appropriately
} catch (\Exception $e) {
    error_log("General error: " . $e->getMessage());
    // Handle error appropriately
}
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Follow coding standards
4. Submit a pull request

## License

This project is licensed under the Apache 2.0 License. See LICENSE.md for details.
