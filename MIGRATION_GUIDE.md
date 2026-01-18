# Performance Optimization Migration Guide

This guide helps you upgrade an existing BloxAuth installation to include the new performance optimizations.

## Prerequisites

- Access to your server (SSH or file manager)
- MySQL/MariaDB access
- Apache/Nginx web server
- PHP 7.4+

## Migration Steps

### Step 1: Backup Your System

**Critical: Always backup before making changes!**

```bash
# Backup database
mysqldump -u username -p database_name > bloxauth_backup_$(date +%Y%m%d).sql

# Backup files
tar -czf bloxauth_files_backup_$(date +%Y%m%d).tar.gz /path/to/bloxauth/

# Verify backups exist
ls -lh *.sql
ls -lh *.tar.gz
```

### Step 2: Update Configuration Files

#### 2.1 Update `includes/config.php`

Add these lines after the security settings (around line 22):

```php
// Caching settings
define('CACHING_ENABLED', true);
define('CACHE_PROVIDER', 'file'); // Options: 'file', 'redis'
define('CACHE_DIR', sys_get_temp_dir() . '/bloxauth_cache');

// Cache TTL (Time To Live) in seconds
define('CACHE_TTL_LICENSE_TYPES', 3600); // 1 hour - rarely changes
define('CACHE_TTL_USER_PERMISSIONS', 1800); // 30 minutes
define('CACHE_TTL_CONFIG_VALUES', 3600); // 1 hour
define('CACHE_TTL_LICENSE_VALIDATION', 600); // 10 minutes - for API validation
define('CACHE_TTL_USER_DATA', 900); // 15 minutes

// Redis settings (if using Redis as cache provider)
define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', 6379);
define('REDIS_PASSWORD', '');
define('REDIS_DATABASE', 0);
```

#### 2.2 Update `includes/functions.php`

Add the cache helper functions at the end of the file (before the closing `?>`). See the complete implementation in the updated `includes/functions.php` file.

The key functions to add:
- `init_cache()`
- `get_cache($key)`
- `set_cache($key, $value, $ttl)`
- `clear_cache($key)`
- `get_cache_file($key)`
- `set_cache_file($key, $value, $ttl)`
- `clear_cache_file($key)`
- `get_cache_redis($key)`
- `set_cache_redis($key, $value, $ttl)`
- `clear_cache_redis($key)`
- `get_redis_connection()`
- `clear_license_cache($license_id)`

#### 2.3 Update `api/validate_key.php`

Replace the entire file with the new cached version. The new version includes:
- Cache checking before database queries
- X-Cache headers
- Enhanced validation logic
- Automatic cache invalidation

### Step 3: Apply Database Indexes

```bash
# Apply the index migration
mysql -u username -p database_name < db_migrations/001_add_performance_indexes.sql

# Verify indexes were created
mysql -u username -p database_name -e "SHOW INDEX FROM licenses_new;"
mysql -u username -p database_name -e "SHOW INDEX FROM licenses;"
mysql -u username -p database_name -e "SHOW INDEX FROM api_keys;"
```

**Expected output:** You should see new indexes like `idx_licenses_new_key`, `idx_api_keys_key`, etc.

### Step 4: Configure Web Server

#### 4.1 Apache (Most Common)

Replace or merge the `.htaccess` file in your BloxAuth root directory:

```bash
# Backup existing .htaccess
cp .htaccess .htaccess.backup

# Copy new .htaccess (or merge manually)
# The new .htaccess includes gzip compression and caching rules
```

Verify Apache modules are enabled:
```bash
sudo a2enmod deflate
sudo a2enmod expires
sudo a2enmod headers
sudo systemctl restart apache2
```

#### 4.2 Nginx (Alternative)

Add to your Nginx configuration:

```nginx
# Gzip compression
gzip on;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml text/javascript;
gzip_min_length 1000;

# Browser caching
location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|otf)$ {
    expires 1y;
    add_header Cache-Control "public, immutable";
}

location ~* \.(html|htm)$ {
    expires -1;
    add_header Cache-Control "no-cache, no-store, must-revalidate";
}
```

### Step 5: Set Up Cache Directory

```bash
# Create cache directory
sudo mkdir -p /tmp/bloxauth_cache

# Set permissions (replace www-data with your web server user)
sudo chown www-data:www-data /tmp/bloxauth_cache
sudo chmod 755 /tmp/bloxauth_cache

# Verify
ls -ld /tmp/bloxauth_cache
```

### Step 6: Test the Installation

#### 6.1 Test Cache System

```bash
php test_cache_standalone.php
```

**Expected output:**
```
✓ All tests passed successfully!
```

#### 6.2 Test API Caching

```bash
# First request (should be MISS)
curl -X POST https://your-domain.com/api/validate_key.php \
  -d "license_key=TEST&roblox_id=123" \
  -v 2>&1 | grep "X-Cache"

# Second request (should be HIT)
curl -X POST https://your-domain.com/api/validate_key.php \
  -d "license_key=TEST&roblox_id=123" \
  -v 2>&1 | grep "X-Cache"
```

#### 6.3 Verify Database Indexes

```sql
-- Check indexes were created
EXPLAIN SELECT * FROM licenses_new WHERE `key` = 'test-key';
-- Should show "possible_keys: idx_licenses_new_key"

EXPLAIN SELECT * FROM api_keys WHERE api_key = 'test-api-key';
-- Should show "possible_keys: idx_api_keys_key"
```

### Step 7: Update Existing Code (Optional)

Update your existing functions to use caching:

```php
// Before
function fetch_license_types($pdo) {
    $stmt = $pdo->prepare("SELECT * FROM license_types ORDER BY name");
    $stmt->execute();
    return $stmt->fetchAll();
}

// After
function fetch_license_types($pdo) {
    $cache_key = 'license_types_all';
    $cached = get_cache($cache_key);
    
    if ($cached !== null) {
        return $cached;
    }
    
    $stmt = $pdo->prepare("SELECT * FROM license_types ORDER BY name");
    $stmt->execute();
    $result = $stmt->fetchAll();
    
    set_cache($cache_key, $result, CACHE_TTL_LICENSE_TYPES);
    
    return $result;
}
```

### Step 8: Frontend Updates (Optional)

1. Add performance helpers JavaScript:
```html
<script src="/assets/js/performance-helpers.js"></script>
```

2. Enable lazy loading for charts:
```html
<div class="chart-container" data-chart-config='{"type":"line",...}'>
    <canvas id="myChart"></canvas>
</div>
```

3. Enable lazy loading for images:
```html
<img data-src="/path/to/image.jpg" alt="Description" class="lazy-load">
```

## Optional: Redis Installation (Recommended for Production)

### Install Redis

#### Ubuntu/Debian
```bash
sudo apt-get update
sudo apt-get install redis-server php-redis
sudo systemctl start redis
sudo systemctl enable redis
```

#### CentOS/RHEL
```bash
sudo yum install redis php-redis
sudo systemctl start redis
sudo systemctl enable redis
```

### Configure Redis

Edit `includes/config.php`:
```php
define('CACHE_PROVIDER', 'redis');
define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', 6379);
define('REDIS_PASSWORD', ''); // Set if you configured Redis authentication
```

### Test Redis Connection

```bash
redis-cli ping
# Should return: PONG

php -r "echo extension_loaded('redis') ? 'Redis available' : 'Redis not available';"
# Should return: Redis available
```

## Rollback Procedure

If you encounter issues, follow these steps to rollback:

### 1. Restore Files
```bash
# Stop web server
sudo systemctl stop apache2

# Restore from backup
cd /path/to/bloxauth/
tar -xzf bloxauth_files_backup_YYYYMMDD.tar.gz

# Restart web server
sudo systemctl start apache2
```

### 2. Restore Database
```bash
# Only if you applied indexes and need to remove them
mysql -u username -p database_name < bloxauth_backup_YYYYMMDD.sql
```

### 3. Remove Cache Directory
```bash
sudo rm -rf /tmp/bloxauth_cache
```

## Post-Migration Checklist

- [ ] Configuration files updated
- [ ] Database indexes applied and verified
- [ ] Cache directory created with proper permissions
- [ ] Web server configuration updated (gzip, caching)
- [ ] Cache system tested successfully
- [ ] API caching verified (X-Cache headers)
- [ ] Database query performance improved (EXPLAIN shows index usage)
- [ ] No errors in PHP error log
- [ ] No errors in web server error log
- [ ] Application functions normally
- [ ] License validation works
- [ ] User login/registration works
- [ ] Dashboard loads correctly

## Performance Monitoring

After migration, monitor these metrics:

### Cache Hit Rate
```bash
# Monitor API responses
tail -f /var/log/apache2/access.log | grep "X-Cache"
```

### Database Performance
```sql
-- Enable slow query log
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 1;

-- Monitor slow queries
SHOW VARIABLES LIKE 'slow_query_log_file';
tail -f /var/log/mysql/slow-query.log
```

### Cache Size (File-based)
```bash
# Check cache directory size
du -sh /tmp/bloxauth_cache

# Count cache files
ls -1 /tmp/bloxauth_cache | wc -l
```

### Redis Memory (Redis-based)
```bash
redis-cli INFO memory
```

## Troubleshooting

### Issue: Cache directory not writable
```bash
sudo chown -R www-data:www-data /tmp/bloxauth_cache
sudo chmod -R 755 /tmp/bloxauth_cache
```

### Issue: X-Cache header not showing
- Check if `require '../includes/functions.php'` is in `api/validate_key.php`
- Verify `CACHING_ENABLED` is `true` in config
- Check PHP error log for cache-related errors

### Issue: Indexes not improving performance
```sql
-- Verify indexes exist
SHOW INDEX FROM licenses_new;

-- Check if index is being used
EXPLAIN SELECT * FROM licenses_new WHERE `key` = 'test';

-- Force rebuild indexes
ALTER TABLE licenses_new ENGINE=InnoDB;
```

### Issue: Redis connection failed
```bash
# Check Redis is running
sudo systemctl status redis

# Test connection
redis-cli ping

# Check PHP extension
php -m | grep redis
```

## Support

For issues during migration:

1. Check the [PERFORMANCE.md](PERFORMANCE.md) documentation
2. Review the [PERFORMANCE_SUMMARY.md](PERFORMANCE_SUMMARY.md) quick reference
3. Run test scripts to isolate the issue
4. Check PHP and web server error logs
5. Open a GitHub issue with:
   - Error messages
   - Test script output
   - Server environment details

## Additional Resources

- [PERFORMANCE.md](PERFORMANCE.md) - Complete performance guide
- [PERFORMANCE_SUMMARY.md](PERFORMANCE_SUMMARY.md) - Quick reference
- [CHANGELOG_PERFORMANCE.md](CHANGELOG_PERFORMANCE.md) - What changed
- [test_cache_standalone.php](test_cache_standalone.php) - Test script

---

**Migration completed?** Run the post-migration checklist and monitor performance for 24-48 hours to ensure everything is working correctly.
