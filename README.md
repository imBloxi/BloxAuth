# BloxAuth

An open-source license management and purchasing system for Roblox game developers, written in PHP and MySQL. Developers issue license keys, bind them to Roblox users or places, validate them from in-game scripts and track usage.

![License: Apache 2.0](https://img.shields.io/badge/license-Apache%202.0-green)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange)

## What it does

- **License keys:** generate keys through an authenticated API or the admin panel, with optional expiry, use limits, transferability and custom tiers
- **Validation API:** check a key against a Roblox user ID and place ID from a Lua script (`api/lua.lua` is an example client)
- **User dashboard (`/app`):** license management, settings, notifications, staff and moderation tools, application review
- **Accounts (`/auth`):** registration, login, Roblox account linking, two-factor authentication, passkey updates, Discord linking
- **Billing (`/billing`) and Sellix:** plan selection, payment verification, Sellix product sync and webhook handler
- **Script obfuscation (`/obfuscate`):** upload and process scripts from the dashboard
- **Logging:** validation attempts, logins, moderation actions and user activity are written to database tables
- **Roblox lookups:** a small Node proxy (`api/proxy.js`) for public place and group details

## Requirements

- PHP 7.4 or later with PDO and JSON
- MySQL 5.7+ or MariaDB 10.2+
- Apache with `mod_rewrite`
- Node.js, only for the optional proxy and obfuscation module

## Installation

```bash
git clone https://github.com/imBloxi/BloxAuth.git
cd BloxAuth
```

1. Create a database and import the schema: `mysql -u <user> -p <database> < db.sql`
2. Set the database connection with environment variables (`includes/db.php` reads `DB_HOST`, `DB_NAME`, `DB_USER` and `DB_PASS`, with local defaults).
3. Edit `includes/config.php`: set your domain, SMTP settings, captcha keys and API keys. The file ships with placeholder values. Replace all of them and never commit real secrets.
4. Point your web server at the project root and register the first account at `/auth/register.php`.
5. After logging in, `/app/setup_app.php` asks for your app name and category.
6. Optional: `cd api && npm install && node proxy.js` for the Roblox proxy.

## API

All responses are JSON.

### Generate a license

```http
POST /api/generate_license.php
X-API-Key: <your api key>
Content-Type: application/json

{
  "roblox_user_id": "12345678",
  "valid_until": "2027-01-01",
  "max_uses": 5,
  "description": "Pro tier"
}
```

Returns the new key. Requests without a valid `X-API-Key` get `401`.

### Validate a license

```http
POST /api/validate_key.php
Content-Type: application/x-www-form-urlencoded

license_key=XXXX-XXXX-XXXX-XXXX&roblox_id=12345678&place_id=87654321
```

Returns `{"status":"success"}` or `{"status":"failure","message":"..."}`. Each successful check is added to the usage log.

## Project structure

```
admin/     admin panel and key issuing
api/       license generation, validation, whitelist, usage, Sellix webhook, Lua client, Node proxy
app/       user dashboard, settings, 2FA, Discord linking, staff and moderation tools
auth/      register, login, logout, Roblox linking
billing/   plans, payments, verification
obfuscate/ script obfuscation
includes/  database, config, shared functions, layout
db.sql     database schema
```

## Security notes and known limitations

This project was built as a team learning project. Review it before using it in production:

- `api/validate_key.php` does not apply the rate limit settings defined in `includes/config.php`. Add throttling per IP and per key.
- The Sellix webhook handler picks the signing secret using a user ID taken from the request payload. Verify the signature against a server-side secret before trusting any field.
- Escape every session or user value that is printed into HTML (for example in `app/verify_2fa.php`).
- Keep `error_log` files, IDE folders and credentials out of version control.

To report a vulnerability, open a GitHub issue without exploit details and ask for a private contact.

## Credits

Built by the BloxiAuth team. Payment processing by [Sellix](https://sellix.io).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

Apache 2.0, see [LICENSE](LICENSE).
