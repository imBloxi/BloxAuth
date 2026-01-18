# Quick Start: Using the Cache System

This guide shows you how to use the caching system in your BloxAuth code.

## Basic Usage

### 1. Get Data from Cache

```php
$cache_key = 'my_data_key';
$cached_data = get_cache($cache_key);

if ($cached_data !== null) {
    // Cache hit - use cached data
    return $cached_data;
}

// Cache miss - fetch from database
$data = fetch_from_database();

// Store in cache for future requests
set_cache($cache_key, $data, 3600); // 3600 seconds = 1 hour

return $data;
```

### 2. Store Data in Cache

```php
// Store with default TTL (from config)
set_cache('user_' . $user_id, $user_data, CACHE_TTL_USER_DATA);

// Store with custom TTL (5 minutes)
set_cache('temporary_data', $data, 300);

// Store with 1 hour TTL
set_cache('config_value', $value, 3600);
```

### 3. Clear Cache

```php
// Clear specific cache entry
clear_cache('user_123');

// Clear all license-related cache
clear_license_cache();

// Clear specific license cache
clear_license_cache($license_id);

// Clear ALL cache (use carefully!)
clear_cache();
```

## Real-World Examples

### Example 1: Caching Database Query Results

```php
function get_user_licenses($pdo, $user_id) {
    $cache_key = 'user_licenses_' . $user_id;
    $cached = get_cache($cache_key);
    
    if ($cached !== null) {
        return $cached;
    }
    
    // Fetch from database
    $stmt = $pdo->prepare("SELECT * FROM licenses_new WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $licenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Cache for 15 minutes
    set_cache($cache_key, $licenses, 900);
    
    return $licenses;
}
```

### Example 2: Caching API Responses

```php
function get_roblox_user_info($roblox_id) {
    $cache_key = 'roblox_user_' . $roblox_id;
    $cached = get_cache($cache_key);
    
    if ($cached !== null) {
        return $cached;
    }
    
    // Fetch from Roblox API
    $response = file_get_contents("https://users.roblox.com/v1/users/{$roblox_id}");
    $user_info = json_decode($response, true);
    
    // Cache for 1 hour (user info doesn't change often)
    set_cache($cache_key, $user_info, 3600);
    
    return $user_info;
}
```

### Example 3: Caching with Invalidation

```php
function update_user_profile($pdo, $user_id, $profile_data) {
    // Update database
    $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
    $stmt->execute([$profile_data['name'], $profile_data['email'], $user_id]);
    
    // Invalidate cache
    clear_cache('user_data_' . $user_id);
    clear_cache('user_profile_' . $user_id);
    
    return true;
}
```

### Example 4: Caching Complex Calculations

```php
function get_user_statistics($pdo, $user_id) {
    $cache_key = 'user_stats_' . $user_id;
    $cached = get_cache($cache_key);
    
    if ($cached !== null) {
        return $cached;
    }
    
    // Expensive calculation
    $stats = [
        'total_licenses' => count_total_licenses($pdo, $user_id),
        'active_licenses' => count_active_licenses($pdo, $user_id),
        'total_validations' => count_total_validations($pdo, $user_id),
        'last_30_days_usage' => get_recent_usage($pdo, $user_id, 30)
    ];
    
    // Cache for 10 minutes
    set_cache($cache_key, $stats, 600);
    
    return $stats;
}
```

## Cache Key Naming Conventions

Use descriptive, consistent cache keys:

```php
// Good
'user_data_' . $user_id              // user_data_123
'license_' . $license_id             // license_456
'user_licenses_' . $user_id          // user_licenses_123
'license_validation_' . $hash        // license_validation_abc123
'config_' . $config_key              // config_api_rate_limit

// Bad
'user' . $user_id                    // user123 (unclear)
'data'                               // data (too vague)
$user_id                             // 123 (no context)
```

## TTL Guidelines

Choose appropriate TTL based on data change frequency:

```php
// Rarely changes (1+ hours)
set_cache('license_types', $types, CACHE_TTL_LICENSE_TYPES); // 1 hour
set_cache('config_values', $config, CACHE_TTL_CONFIG_VALUES); // 1 hour

// Moderately changes (15-30 minutes)
set_cache('user_data_' . $id, $user, CACHE_TTL_USER_DATA); // 15 minutes
set_cache('user_perms_' . $id, $perms, CACHE_TTL_USER_PERMISSIONS); // 30 minutes

// Frequently accessed (5-10 minutes)
set_cache('license_check', $result, CACHE_TTL_LICENSE_VALIDATION); // 10 minutes

// Temporary (1-5 minutes)
set_cache('rate_limit_' . $ip, $count, 60); // 1 minute
set_cache('session_data', $data, 300); // 5 minutes

// Very temporary (seconds)
set_cache('captcha_' . $token, $value, 30); // 30 seconds
```

## Cache Patterns

### Pattern 1: Cache-Aside (Lazy Loading)
Most common pattern - check cache first, then database:

```php
$data = get_cache($key);
if ($data === null) {
    $data = fetch_from_source();
    set_cache($key, $data, $ttl);
}
return $data;
```

### Pattern 2: Write-Through
Update cache when updating database:

```php
function update_item($pdo, $id, $data) {
    // Update database
    update_database($pdo, $id, $data);
    
    // Update cache immediately
    set_cache('item_' . $id, $data, 3600);
}
```

### Pattern 3: Write-Behind (Cache Invalidation)
Clear cache when updating database:

```php
function update_item($pdo, $id, $data) {
    // Update database
    update_database($pdo, $id, $data);
    
    // Invalidate cache (next read will refresh)
    clear_cache('item_' . $id);
}
```

## Common Mistakes to Avoid

### ❌ Don't Cache Everything
```php
// Bad - caching data that changes constantly
set_cache('current_timestamp', time(), 60);
set_cache('random_number', rand(), 60);
```

### ❌ Don't Use Too Long TTL for Dynamic Data
```php
// Bad - user data cached for 24 hours
set_cache('user_' . $id, $user, 86400); // Too long!

// Good - reasonable TTL
set_cache('user_' . $id, $user, 900); // 15 minutes
```

### ❌ Don't Forget to Invalidate
```php
// Bad - updating data without clearing cache
function update_user($pdo, $user_id, $data) {
    update_database($pdo, $user_id, $data);
    // Missing: clear_cache('user_' . $user_id);
}

// Good - invalidate cache on update
function update_user($pdo, $user_id, $data) {
    update_database($pdo, $user_id, $data);
    clear_cache('user_' . $user_id);
}
```

### ❌ Don't Cache Sensitive Data
```php
// Bad - caching passwords or sensitive data
set_cache('user_password_' . $id, $password, 3600); // Never do this!

// Good - only cache non-sensitive data
set_cache('user_profile_' . $id, $public_data, 3600);
```

## Debugging Cache

### Check if Data is Cached
```php
$cached = get_cache('my_key');
if ($cached !== null) {
    error_log("Cache HIT for my_key");
} else {
    error_log("Cache MISS for my_key");
}
```

### Temporarily Disable Cache
```php
// In includes/config.php
define('CACHING_ENABLED', false); // Disable caching for debugging
```

### Check Cache Directory
```bash
# List cached files
ls -lh /tmp/bloxauth_cache/

# Check cache directory size
du -sh /tmp/bloxauth_cache/

# Clear all cache files
rm -rf /tmp/bloxauth_cache/*.cache
```

## Configuration

All cache settings are in `includes/config.php`:

```php
// Enable/disable caching
define('CACHING_ENABLED', true);

// Choose provider
define('CACHE_PROVIDER', 'file'); // or 'redis'

// File cache directory
define('CACHE_DIR', sys_get_temp_dir() . '/bloxauth_cache');

// TTL presets
define('CACHE_TTL_LICENSE_TYPES', 3600);
define('CACHE_TTL_USER_PERMISSIONS', 1800);
define('CACHE_TTL_CONFIG_VALUES', 3600);
define('CACHE_TTL_LICENSE_VALIDATION', 600);
define('CACHE_TTL_USER_DATA', 900);
```

## Testing Your Cache

```bash
# Run the test script
php test_cache_standalone.php
```

Expected output:
```
✓ All tests passed successfully!
```

## Need More Help?

- **Complete Guide**: See [PERFORMANCE.md](PERFORMANCE.md)
- **Quick Reference**: See [PERFORMANCE_SUMMARY.md](PERFORMANCE_SUMMARY.md)
- **Migration**: See [MIGRATION_GUIDE.md](MIGRATION_GUIDE.md)
- **Examples**: Check `includes/functions.php` for real implementations

---

**Remember:**
- Check cache first, then database
- Use appropriate TTL values
- Invalidate cache on updates
- Don't cache sensitive data
- Monitor cache hit rates
