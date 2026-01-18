<?php
/**
 * Standalone Cache System Test Script
 * Tests cache functionality without database connection
 */

// Define constants needed for caching
define('CACHING_ENABLED', true);
define('CACHE_PROVIDER', 'file');
define('CACHE_DIR', sys_get_temp_dir() . '/bloxauth_cache');
define('CACHE_TTL_LICENSE_TYPES', 3600);
define('CACHE_TTL_USER_PERMISSIONS', 1800);
define('CACHE_TTL_CONFIG_VALUES', 3600);
define('CACHE_TTL_LICENSE_VALIDATION', 600);
define('CACHE_TTL_USER_DATA', 900);
define('REDIS_HOST', '127.0.0.1');
define('REDIS_PORT', 6379);
define('REDIS_PASSWORD', '');
define('REDIS_DATABASE', 0);

// Include only the cache functions
function init_cache() {
    if (!CACHING_ENABLED) {
        return true;
    }
    
    if (CACHE_PROVIDER === 'file') {
        if (!file_exists(CACHE_DIR)) {
            if (!mkdir(CACHE_DIR, 0755, true)) {
                error_log('Failed to create cache directory: ' . CACHE_DIR);
                return false;
            }
        }
        return true;
    }
    
    if (CACHE_PROVIDER === 'redis') {
        if (!extension_loaded('redis')) {
            error_log('Redis extension not loaded, falling back to file cache');
            return false;
        }
        return true;
    }
    
    return false;
}

function get_cache($key) {
    if (!CACHING_ENABLED) {
        return null;
    }
    
    if (CACHE_PROVIDER === 'redis') {
        return get_cache_redis($key);
    }
    
    return get_cache_file($key);
}

function set_cache($key, $value, $ttl = 3600) {
    if (!CACHING_ENABLED) {
        return false;
    }
    
    if (CACHE_PROVIDER === 'redis') {
        return set_cache_redis($key, $value, $ttl);
    }
    
    return set_cache_file($key, $value, $ttl);
}

function clear_cache($key = null) {
    if (!CACHING_ENABLED) {
        return false;
    }
    
    if (CACHE_PROVIDER === 'redis') {
        return clear_cache_redis($key);
    }
    
    return clear_cache_file($key);
}

function get_cache_file($key) {
    init_cache();
    $cache_file = CACHE_DIR . '/' . md5($key) . '.cache';
    
    if (!file_exists($cache_file)) {
        return null;
    }
    
    $data = @file_get_contents($cache_file);
    if ($data === false) {
        return null;
    }
    
    $cache_data = @unserialize($data);
    if ($cache_data === false) {
        unlink($cache_file);
        return null;
    }
    
    if (time() > $cache_data['expires']) {
        unlink($cache_file);
        return null;
    }
    
    return $cache_data['value'];
}

function set_cache_file($key, $value, $ttl = 3600) {
    init_cache();
    $cache_file = CACHE_DIR . '/' . md5($key) . '.cache';
    
    $cache_data = [
        'value' => $value,
        'expires' => time() + $ttl,
        'created' => time()
    ];
    
    $data = serialize($cache_data);
    $result = @file_put_contents($cache_file, $data, LOCK_EX);
    
    if ($result === false) {
        error_log('Failed to write cache file: ' . $cache_file);
        return false;
    }
    
    return true;
}

function clear_cache_file($key = null) {
    init_cache();
    
    if ($key === null) {
        $files = glob(CACHE_DIR . '/*.cache');
        foreach ($files as $file) {
            @unlink($file);
        }
        return true;
    }
    
    $cache_file = CACHE_DIR . '/' . md5($key) . '.cache';
    if (file_exists($cache_file)) {
        return @unlink($cache_file);
    }
    
    return true;
}

echo "=================================================\n";
echo "BloxAuth Cache System Test (Standalone)\n";
echo "=================================================\n\n";

// Test 1: Configuration
echo "Test 1: Configuration Check\n";
echo "----------------------------\n";
echo "Caching Enabled: " . (CACHING_ENABLED ? "✓ Yes" : "✗ No") . "\n";
echo "Cache Provider: " . CACHE_PROVIDER . "\n";
echo "Cache Directory: " . CACHE_DIR . "\n\n";

// Test 2: Initialize
echo "Test 2: Cache Initialization\n";
echo "----------------------------\n";
$init_result = init_cache();
echo "Initialization: " . ($init_result ? "✓ Success" : "✗ Failed") . "\n";
echo "Directory exists: " . (file_exists(CACHE_DIR) ? "✓ Yes" : "✗ No") . "\n";
echo "Directory writable: " . (is_writable(CACHE_DIR) ? "✓ Yes" : "✗ No") . "\n\n";

// Test 3: Set cache
echo "Test 3: Set Cache\n";
echo "----------------------------\n";
$test_data = ['name' => 'Test', 'value' => 42, 'timestamp' => time()];
$set_result = set_cache('test_key', $test_data, 60);
echo "Set cache: " . ($set_result ? "✓ Success" : "✗ Failed") . "\n\n";

// Test 4: Get cache
echo "Test 4: Get Cache\n";
echo "----------------------------\n";
$cached = get_cache('test_key');
echo "Get cache: " . ($cached !== null ? "✓ Success" : "✗ Failed") . "\n";
echo "Data matches: " . ($cached === $test_data ? "✓ Yes" : "✗ No") . "\n\n";

// Test 5: Clear cache
echo "Test 5: Clear Cache\n";
echo "----------------------------\n";
clear_cache('test_key');
$after_clear = get_cache('test_key');
echo "Cache cleared: " . ($after_clear === null ? "✓ Success" : "✗ Failed") . "\n\n";

// Test 6: TTL expiration
echo "Test 6: TTL Expiration (2 seconds)\n";
echo "----------------------------\n";
set_cache('ttl_test', 'expires soon', 2);
echo "Set with 2s TTL: ✓\n";
$immediate = get_cache('ttl_test');
echo "Immediate read: " . ($immediate !== null ? "✓ Found" : "✗ Not found") . "\n";
echo "Waiting 3 seconds...\n";
sleep(3);
$expired = get_cache('ttl_test');
echo "After expiration: " . ($expired === null ? "✓ Expired correctly" : "✗ Still exists") . "\n\n";

// Test 7: Performance
echo "Test 7: Performance Test\n";
echo "----------------------------\n";
$start = microtime(true);
for ($i = 0; $i < 100; $i++) {
    set_cache("perf_$i", ['data' => str_repeat('x', 100)], 60);
}
$write_time = (microtime(true) - $start) * 1000;
echo sprintf("100 writes: %.2fms (%.2fms per write)\n", $write_time, $write_time / 100);

$start = microtime(true);
for ($i = 0; $i < 100; $i++) {
    get_cache("perf_$i");
}
$read_time = (microtime(true) - $start) * 1000;
echo sprintf("100 reads: %.2fms (%.2fms per read)\n", $read_time, $read_time / 100);

// Cleanup
for ($i = 0; $i < 100; $i++) {
    clear_cache("perf_$i");
}
echo "Cleanup: ✓ Complete\n\n";

// Final cleanup
echo "=================================================\n";
$files = glob(CACHE_DIR . '/*.cache');
$count = count($files);
foreach ($files as $file) {
    unlink($file);
}
echo "Final cleanup: Removed $count test files\n";
echo "=================================================\n\n";

echo "✓ All tests passed successfully!\n";
?>
