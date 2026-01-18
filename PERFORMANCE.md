# BloxAuth Performance Optimization Guide

## Overview
This document describes the caching and performance optimizations implemented in BloxAuth.

## Table of Contents
1. [Caching System](#caching-system)
2. [Database Optimization](#database-optimization)
3. [Frontend Performance](#frontend-performance)
4. [Configuration](#configuration)
5. [Cache Invalidation](#cache-invalidation)
6. [Monitoring](#monitoring)

## Caching System

### Architecture
BloxAuth implements a flexible caching system that supports both file-based and Redis caching:

- **File-based caching**: Default option, requires no additional setup
- **Redis caching**: Recommended for production, requires Redis server

### Cache Helper Functions

The following cache helper functions are available in `includes/functions.php`:

#### `get_cache($key)`
Retrieves a value from the cache.

```php
$data = get_cache('license_types_all');
if ($data === null) {
    // Cache miss - fetch from database
}
```

#### `set_cache($key, $value, $ttl = 3600)`
Stores a value in the cache with a specified TTL (time-to-live) in seconds.

```php
set_cache('license_types_all', $license_types, CACHE_TTL_LICENSE_TYPES);
```

#### `clear_cache($key = null)`
Clears a specific cache key or all cache if no key is provided.

```php
clear_cache('license_types_all'); // Clear specific key
clear_cache(); // Clear all cache
```

#### `clear_license_cache($license_id = null)`
Specialized function to clear license-related cache.

```php
clear_license_cache(123); // Clear cache for license ID 123
clear_license_cache(); // Clear all license cache
```

### Cached Data Types

The following data types are cached with their respective TTLs:

| Data Type | TTL | Cache Key Pattern | Reason |
|-----------|-----|-------------------|--------|
| License Types | 1 hour | `license_types_all` | Rarely changes |
| User Data | 15 minutes | `user_data_{user_id}` | Moderately dynamic |
| License Validation | 10 minutes | `license_validation_{hash}` | Frequently accessed, reduces DB load |
| User Permissions | 30 minutes | `user_permissions_{user_id}` | Security-related, moderate refresh |
| Config Values | 1 hour | `config_{key}` | Rarely changes |

### Cache Configuration

Configure caching in `includes/config.php`:

```php
// Enable/disable caching
define('CACHING_ENABLED', true);

// Cache provider: 'file' or 'redis'
define('CACHE_PROVIDER', 'file');

// File cache directory
define('CACHE_DIR', sys_get_temp_dir() . '/bloxauth_cache');

// TTL values
define('CACHE_TTL_LICENSE_TYPES', 3600);
define('CACHE_TTL_USER_PERMISSIONS', 1800);
define('CACHE_TTL_CONFIG_VALUES', 3600);
define('CACHE_TTL_LICENSE_VALIDATION', 600);
define('CACHE_TTL_USER_DATA', 900);
```

### Switching to Redis

To use Redis instead of file-based caching:

1. Install Redis server:
```bash
sudo apt-get install redis-server
sudo systemctl start redis
```

2. Install PHP Redis extension:
```bash
sudo apt-get install php-redis
sudo systemctl restart apache2
```

3. Update `includes/config.php`:
```php
define('CACHE_PROVIDER', 'redis');
define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', 6379);
define('REDIS_PASSWORD', ''); // Set if authentication is required
define('REDIS_DATABASE', 0);
```

## Database Optimization

### Indexes Added

The migration file `db_migrations/001_add_performance_indexes.sql` adds the following indexes:

#### Primary Indexes
- `licenses.key` - Speeds up license lookups
- `licenses_new.key` - Speeds up license lookups (new schema)
- `licenses_new.roblox_user_id` - User-based license queries
- `api_keys.api_key` - API authentication
- `users.email` - Login queries

#### Composite Indexes
- `licenses_new(key, is_revoked, is_banned)` - License validation queries
- `usage_logs(created_at, license_id)` - Temporal and license-based queries
- `notifications(user_id, is_read, created_at)` - User notification queries
- `login_logs(user_id, created_at)` - User activity tracking

#### Temporal Indexes
- `usage_logs.created_at` - Activity reports
- `license_logs.timestamp` - License activity tracking
- `login_logs.created_at` - Login history queries

### Running the Migration

To apply the database indexes:

```bash
mysql -u username -p database_name < db_migrations/001_add_performance_indexes.sql
```

Or via PHP:

```php
$sql = file_get_contents('db_migrations/001_add_performance_indexes.sql');
$pdo->exec($sql);
```

### Query Optimization Best Practices

1. **Use indexed columns in WHERE clauses**
   ```php
   // Good - uses index on licenses_new.key
   $stmt = $pdo->prepare('SELECT * FROM licenses_new WHERE `key` = ?');
   
   // Bad - function on indexed column prevents index usage
   $stmt = $pdo->prepare('SELECT * FROM licenses_new WHERE UPPER(`key`) = ?');
   ```

2. **Avoid N+1 queries**
   ```php
   // Bad - N+1 query
   $licenses = $pdo->query('SELECT * FROM licenses_new')->fetchAll();
   foreach ($licenses as $license) {
       $user = $pdo->prepare('SELECT * FROM users WHERE id = ?');
       $user->execute([$license['user_id']]);
   }
   
   // Good - single JOIN query
   $stmt = $pdo->query('
       SELECT l.*, u.username, u.email 
       FROM licenses_new l 
       JOIN users u ON l.user_id = u.id
   ');
   ```

3. **Use LIMIT for large result sets**
   ```php
   $stmt = $pdo->prepare('SELECT * FROM usage_logs ORDER BY created_at DESC LIMIT 100');
   ```

4. **Monitor query performance**
   ```sql
   EXPLAIN SELECT * FROM licenses_new WHERE `key` = 'abc123';
   ```

## Frontend Performance

### Gzip Compression

Gzip compression is enabled in `.htaccess` for:
- HTML, CSS, JavaScript
- JSON, XML
- Fonts (TTF, OTF, WOFF)
- SVG images

This reduces bandwidth usage by 60-80% for text-based assets.

### Browser Caching

The `.htaccess` file configures browser caching:
- **Static assets** (images, fonts, CSS, JS): 1 year
- **HTML/PHP**: No cache (always fresh)
- **JSON/XML**: No cache

### Lazy Loading for Charts

Implement lazy loading for analytics charts to improve initial page load:

```javascript
// Load Chart.js only when needed
function loadCharts() {
    if (typeof Chart === 'undefined') {
        const script = document.createElement('script');
        script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
        script.onload = () => initializeCharts();
        document.head.appendChild(script);
    } else {
        initializeCharts();
    }
}

// Initialize on intersection (when chart container is visible)
const observer = new IntersectionObserver((entries) => {
    entries.forEach(entry => {
        if (entry.isIntersecting) {
            loadCharts();
            observer.disconnect();
        }
    });
});

const chartContainer = document.querySelector('.chart-container');
if (chartContainer) {
    observer.observe(chartContainer);
}
```

### Asset Optimization

1. **Minify CSS/JS**
   ```bash
   # Using npm
   npm run build
   
   # Or manually with terser/cssnano
   terser input.js -o output.min.js
   cssnano input.css output.min.css
   ```

2. **Optimize images**
   - Use WebP format where possible
   - Compress images with tools like ImageOptim or TinyPNG
   - Use appropriate image dimensions (don't serve 4K images for thumbnails)

3. **Use CDN for external libraries**
   - Chart.js, Alpine.js, Tailwind CSS can be loaded from CDN
   - Reduces server load and improves caching

## Configuration

All caching configuration is centralized in `includes/config.php`:

```php
// Enable/disable caching globally
define('CACHING_ENABLED', true);

// Choose cache provider
define('CACHE_PROVIDER', 'file'); // or 'redis'

// File cache directory (ensure it's writable)
define('CACHE_DIR', sys_get_temp_dir() . '/bloxauth_cache');

// Customize TTL values based on your needs
define('CACHE_TTL_LICENSE_TYPES', 3600);        // 1 hour
define('CACHE_TTL_USER_PERMISSIONS', 1800);     // 30 minutes
define('CACHE_TTL_CONFIG_VALUES', 3600);        // 1 hour
define('CACHE_TTL_LICENSE_VALIDATION', 600);    // 10 minutes
define('CACHE_TTL_USER_DATA', 900);             // 15 minutes
```

## Cache Invalidation

### Automatic Invalidation

Cache is automatically invalidated in the following scenarios:

1. **License Updates**: When a license is created, updated, or deleted
   ```php
   // After updating a license
   clear_license_cache($license_id);
   ```

2. **User Data Changes**: When user information is modified
   ```php
   // After updating user data
   clear_cache('user_data_' . $user_id);
   ```

### Manual Invalidation

Clear cache manually when needed:

```php
// Clear specific cache
clear_cache('license_types_all');

// Clear all license cache
clear_license_cache();

// Clear all cache
clear_cache();
```

### API Cache Headers

The API returns cache status in headers:
- `X-Cache: HIT` - Response served from cache
- `X-Cache: MISS` - Response served from database

Monitor these headers to measure cache effectiveness:

```bash
curl -I https://your-domain.com/api/validate_key.php
```

## Monitoring

### Cache Hit Rate

Monitor cache effectiveness by tracking hit/miss rates:

```php
// Add this to your monitoring/logging
if ($cached_result !== null) {
    log_metric('cache.hit', 1);
} else {
    log_metric('cache.miss', 1);
}
```

Aim for a cache hit rate of 70%+ for frequently accessed data.

### Database Query Performance

Use MySQL's slow query log to identify slow queries:

```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1; -- Log queries taking > 1 second

-- Check slow query log
SHOW VARIABLES LIKE 'slow_query_log_file';
```

### Index Usage

Verify indexes are being used:

```sql
-- Check index usage for a query
EXPLAIN SELECT * FROM licenses_new WHERE `key` = 'test123';

-- View all indexes on a table
SHOW INDEX FROM licenses_new;

-- Check index statistics
SELECT * FROM information_schema.STATISTICS 
WHERE table_schema = 'your_database' 
AND table_name = 'licenses_new';
```

### Performance Benchmarks

Expected performance improvements:

| Operation | Before | After | Improvement |
|-----------|--------|-------|-------------|
| License validation (cached) | 50-100ms | 5-10ms | 90% faster |
| License type lookup (cached) | 20-30ms | 1-2ms | 95% faster |
| User data fetch (cached) | 30-50ms | 2-5ms | 90% faster |
| API response time (cached) | 100-200ms | 10-20ms | 90% faster |

## Best Practices

1. **Cache Appropriately**
   - Cache data that changes infrequently
   - Don't cache sensitive data (passwords, API keys)
   - Use appropriate TTL values

2. **Invalidate Wisely**
   - Clear cache when data changes
   - Use specific cache keys instead of clearing all cache
   - Avoid cache stampede (many requests hitting DB simultaneously)

3. **Monitor Performance**
   - Track cache hit rates
   - Monitor database query times
   - Use APM tools (New Relic, Datadog) in production

4. **Test Thoroughly**
   - Test cache invalidation logic
   - Verify no stale data issues
   - Test both cache hit and miss scenarios

## Troubleshooting

### Cache Directory Not Writable

```bash
# Fix permissions
sudo chmod 755 /tmp/bloxauth_cache
sudo chown www-data:www-data /tmp/bloxauth_cache
```

### Redis Connection Issues

```bash
# Check Redis is running
sudo systemctl status redis

# Test Redis connection
redis-cli ping
# Should return: PONG

# Check PHP Redis extension
php -m | grep redis
```

### Stale Data in Cache

```php
// Temporarily disable cache for debugging
define('CACHING_ENABLED', false);

// Or clear all cache
clear_cache();
```

### High Memory Usage (File Cache)

```bash
# Check cache directory size
du -sh /tmp/bloxauth_cache

# Clear old cache files (older than 24 hours)
find /tmp/bloxauth_cache -type f -mtime +1 -delete
```

## Production Recommendations

1. **Use Redis** for better performance and scalability
2. **Enable OpCache** for PHP to cache compiled code
3. **Use a CDN** for static assets
4. **Enable query caching** in MySQL
5. **Monitor cache metrics** with APM tools
6. **Implement cache warming** for critical data on application start
7. **Use database read replicas** for read-heavy workloads

## Additional Resources

- [Redis Documentation](https://redis.io/documentation)
- [MySQL Performance Tuning](https://dev.mysql.com/doc/refman/8.0/en/optimization.html)
- [PHP OPcache](https://www.php.net/manual/en/book.opcache.php)
- [Apache Performance Tuning](https://httpd.apache.org/docs/2.4/misc/perf-tuning.html)
