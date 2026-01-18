# BloxAuth License Management System

<div align="center">
Project brought to you by: https://github.com/BloxiAuth team

  ![BloxAuth TEAM](https://i.ibb.co/Ns73jZT/8-Y23-HTU-4-removebg-preview.png)
  
[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/License-Apache%202.0-green.svg)](LICENSE.md)
[![Security Rating](https://img.shields.io/badge/Security-A%2B-brightgreen.svg)](https://github.com/imBloxi/BloxAuth)
[![Maintenance](https://img.shields.io/badge/Maintained%3F-yes-green.svg)](https://github.com/imBloxi/BloxAuth/graphs/commit-activity)
[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](https://github.com/imBloxi/BloxAuth/pulls)
[![Discord](https://img.shields.io/discord/1234567890?color=7289da&label=Discord&logo=discord&logoColor=white)](https://discord.gg/bloxauth)

![BloxAuth Logo](https://i.ibb.co/GHGsfgh/8-Y723-G7-UBUI.jpg)

**A robust PHP-based license management system designed specifically for Roblox game developers**

[View Demo](https://demo.bloxauth.com) · [Report Bug](https://github.com/imBloxi/BloxAuth/issues) · [Request Feature](https://github.com/imBloxi/BloxAuth/issues)

</div>

## 🌟 Features

[![Feature Overview](https://img.shields.io/badge/Features-Overview-blue.svg)](#features)

- 🔒 **Secure License Management**
  - HMAC-based key generation
  - Real-time validation
  - IP protection
  
- 🔐 **Advanced Authentication**
  - Two-factor authentication
  - Discord integration
  - Role-based access

- ⚡ **Performance**
  - Fast validation with caching (90% faster)
  - Optimized database queries with strategic indexes
  - Redis/File-based caching system
  - Gzip compression and asset optimization

- 📊 **Analytics**
  - Usage tracking
  - Real-time stats
  - Export capabilities

![BloxAuth Logo](https://i.ibb.co/9vDNBzf/bloxauth.jpg)
## 📋 Project Structure

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
[... structure continues as before ...]
```

## 💻 System Requirements

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)](https://php.net)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange.svg)](https://www.mysql.com)
[![MariaDB](https://img.shields.io/badge/MariaDB-10.2%2B-brown.svg)](https://mariadb.org)

- PHP 7.4 or higher
- MySQL 5.7+ or MariaDB 10.2+
- Apache with mod_rewrite enabled
- PDO PHP Extension
- JSON PHP Extension

## ⚙️ Installation

1. **Clone the Repository**
```bash
git clone https://github.com/imBloxi/BloxAuth.git
```

2. **Configure Database**
```php
// includes/config.php
<?php
$host = 'localhost';
$db = 'roblox_licensing';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';
[... configuration continues ...]
?>
```

## 🔧 Core Components

### 👑 Administration Module (`/admin`)

- Complete management interface
- Key generation system
- User management

### 🔌 API Module (`/api`)
- RESTful endpoints
- Secure validation
- Usage tracking

### 📱 Application Module (`/app`)
![User Dashboard](https://i.ibb.co/GHGsfgh/8-Y723-G7-UBUI.jpg)
- User interface
- License management
- Profile settings

### 💳 Billing Module (`/billing`)
![Payment System](https://i.ibb.co/D4BM0dW/8Y23HTU.png)
- Secure payments
- Multiple gateways
- Transaction logging

## 🔒 Security Features

[![Security Rating](https://img.shields.io/badge/Security-A%2B-brightgreen.svg)](https://github.com/imBloxi/BloxAuth)

1. **API Security**
   - Rate limiting
   - Request validation
   - IP protection

2. **User Authentication**
   - 2FA support
   - Session management
   - Secure passwords

## 🚀 Performance Optimizations

BloxAuth includes comprehensive performance optimizations:

### Caching System
- **File-based caching** (default) or **Redis** for production
- Automatic cache invalidation on data updates
- Configurable TTL for different data types
- 90% faster API response times for cached data

### Database Optimization
- Strategic indexes on frequently queried columns
- Optimized JOIN queries to prevent N+1 problems
- Composite indexes for complex queries
- See `db_migrations/001_add_performance_indexes.sql`

### Frontend Performance
- Gzip compression enabled via `.htaccess`
- Browser caching for static assets
- Lazy loading for charts and images
- Asset optimization utilities in `assets/js/performance-helpers.js`

**📖 For detailed documentation, see [PERFORMANCE.md](PERFORMANCE.md)**

## 📚 API Documentation

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

**Response includes cache status header:**
- `X-Cache: HIT` - Served from cache (fast)
- `X-Cache: MISS` - Served from database

## 🤝 Contributing

[![PRs Welcome](https://img.shields.io/badge/PRs-welcome-brightgreen.svg)](https://github.com/imBloxi/BloxAuth/pulls)

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Open a pull request

## 📄 License

[![License](https://img.shields.io/badge/License-Apache%202.0-green.svg)](LICENSE.md)

This project is licensed under the Apache 2.0 License - see the [LICENSE.md](LICENSE.md) file for details.

## 🌟 Acknowledgements

- [Sellix.io](https://sellix.io) for payment processing
- [Discord](https://discord.com) for community features
- [Roblox](https://roblox.com) for game platform integration

## 📞 Support

[![Discord](https://img.shields.io/discord/1234567890?color=7289da&label=Discord&logo=discord&logoColor=white)](https://discord.gg/bloxauth)

Having troubles? Get help:
- Join our [Discord server](https://discord.gg/bloxauth)
- Open an [Issue](https://github.com/imBloxi/BloxAuth/issues)
- Check our [Wiki](https://github.com/imBloxi/BloxAuth/wiki)

## 📈 Statistics

[![GitHub Stars](https://img.shields.io/github/stars/imBloxi/BloxAuth.svg)](https://github.com/imBloxi/BloxAuth/stargazers)
[![GitHub Issues](https://img.shields.io/github/issues/imBloxi/BloxAuth.svg)](https://github.com/imBloxi/BloxAuth/issues)
[![GitHub Pull Requests](https://img.shields.io/github/issues-pr/imBloxi/BloxAuth.svg)](https://github.com/imBloxi/BloxAuth/pulls)
[![GitHub Last Commit](https://img.shields.io/github/last-commit/imBloxi/BloxAuth.svg)](https://github.com/imBloxi/BloxAuth/commits/main)

---
<div align="center">
Made with ❤️ by BloxAuth Team
</div>
