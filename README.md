# BloxAuth

An open-source license management and purchasing system for Roblox game developers, written in PHP and MySQL. Developers issue license keys, bind them to Roblox users or places, validate them from in-game scripts and track usage.

![License: Apache 2.0](https://img.shields.io/badge/license-Apache%202.0-green)
![PHP](https://img.shields.io/badge/PHP-8.0%2B-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange)

## What it does

- **License keys:** generate keys through an authenticated API or the admin panel, with optional expiry, use limits, transferability and custom tiers
- **Validation API:** check a key against the game's owner (user or group) and place ID from a Lua script (`api/lua.lua` is a ready-made client)
- **User dashboard (`/app`):** license management, settings, notifications, staff and moderation tools, application review
- **Accounts (`/auth`):** registration, login with "remember me", Roblox and Discord OAuth linking, Discord sign-in
- **Billing (`/billing`) and Sellix:** plan selection, payment verification, Sellix product sync and webhook handler
- **Script obfuscation (`/obfuscate`):** upload and process scripts from the dashboard
- **Logging:** validation attempts, logins, moderation actions and user activity are written to database tables
- **Roblox lookups:** a small Node proxy (`api/proxy.js`) for public place and group details

## Requirements

- PHP 8.0 or later with PDO MySQL, cURL and JSON
- MySQL 5.7+ or MariaDB 10.2+
- Apache with `mod_rewrite`
- Node.js, only for the optional proxy and obfuscation module

## Installation

```bash
git clone https://github.com/imBloxi/BloxAuth.git
cd BloxAuth
```

1. Create a database and load the schema, then the migrations:
   ```bash
   mysql -u <user> -p <database> < db.sql
   mysql -u <user> -p <database> < migrations/001_schema_fixes.sql
   ```
   Existing installs only need the migration. Run it once.
2. Set the environment variables listed in `.env.example` (database, `APP_URL`, and the OAuth, captcha and Sellix keys you use). Secrets are read with `getenv()` and never live in the code.
3. Point your web server at the project root and register the first account at `/auth/register.php`. To make it an admin, set `is_staff = 1` on that user.
4. After signup, `/app/setup_app.php` asks for your app name and creates your API key.
5. Optional: `cd api && npm install && node proxy.js` for the Roblox proxy.

Quick local run: `DB_USER=... DB_PASS=... php -S 127.0.0.1:8099 -t .`

## API

All responses are JSON. The full reference is at `/documentation.php` on your install.

### Validate a license (from a game)

```http
POST /api/validate_key.php
Content-Type: application/x-www-form-urlencoded   (JSON also accepted)

license_key=XXXX&roblox_id=12345678&place_id=87654321
```

Returns `{"status":"success"}` or `{"status":"failure","message":"..."}`. A key passes when it isn't revoked, banned, expired or over `max_uses`, and its binding matches: user licenses match `roblox_id`, group licenses match the group ID sent as `roblox_id`, place licenses match `place_id`. Each check counts as one use and is logged; send `recheck=1` for periodic re-checks so they don't use up `max_uses`. Limited to `API_RATE_LIMIT` requests per minute per IP.

### Create a license

```http
POST /api/generate_license.php
X-API-Key: <your api key>
Content-Type: application/json

{
  "whitelist_type": "user",
  "roblox_user_id": "12345678",
  "valid_until": "2027-01-01",
  "max_uses": 5,
  "description": "Pro tier"
}
```

Returns `201` with the new key. Use `"whitelist_type": "place"` or `"group"` with `whitelist_id` for place or group licenses. Requests without a valid key get `401`.

### Manage licenses

`/api/v1/licenses/index.php?type=list|create|renew|ban|revoke`, authenticated with the same API key. Every action only touches the key owner's licenses.

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
migrations/ schema fixes to run after db.sql
tests/     end-to-end test script
```

## Testing

`tests/e2e.sh` drives a running install over HTTP: signup, app setup, license creation in the UI and API, every validation rule, the game and management APIs, access control between users, staff tools, bans, the Sellix webhook, rate limits and account deletion. It needs a server and the `mysql` client:

```bash
php -S 127.0.0.1:8099 -t . &
DB_HOST=127.0.0.1 DB_USER=... DB_PASS=... tests/e2e.sh
```

CI runs the same script against MariaDB on every push and pull request.

## Security notes and known limitations

This project started as a team learning project. Things to know before running it in production:

- Two-factor login is not implemented. Accounts with `two_factor_enabled` set can't sign in until it is.
- Forms rely on `SameSite=Lax` session cookies against cross-site requests; there are no per-form CSRF tokens yet.
- The Sellix product creation and luaobfuscator.com calls follow those services' public APIs but haven't been tested against the live services.
- Keep `error_log` files, IDE folders and credentials out of version control.

To report a vulnerability, open a GitHub issue without exploit details and ask for a private contact.

## Credits

Built by the BloxiAuth team. Payment processing by [Sellix](https://sellix.io).

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md).

## License

Apache 2.0, see [LICENSE](LICENSE).
