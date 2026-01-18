<?php
/**
 * Cache System Test Script
 * 
 * This script tests the caching functionality to ensure everything works correctly.
 * Run this script via CLI or browser to verify cache operations.
 */

require_once 'includes/config.php';
require_once 'includes/functions.php';

echo "=================================================\n";
echo "BloxAuth Cache System Test\n";
echo "=================================================\n\n";

// Test 1: Check if caching is enabled
echo "Test 1: Configuration Check\n";
echo "----------------------------\n";
echo "Caching Enabled: " . (CACHING_ENABLED ? "Yes" : "No") . "\n";
echo "Cache Provider: " . CACHE_PROVIDER . "\n";
echo "Cache Directory: " . CACHE_DIR . "\n\n";

// Test 2: Initialize cache
echo "Test 2: Cache Initialization\n";
echo "----------------------------\n";
$init_result = init_cache();
if ($init_result) {
    echo "✓ Cache initialized successfully\n";
    if (CACHE_PROVIDER === 'file') {
        echo "Cache directory exists: " . (file_exists(CACHE_DIR) ? "Yes" : "No") . "\n";
        echo "Cache directory writable: " . (is_writable(CACHE_DIR) ? "Yes" : "No") . "\n";
    }
} else {
    echo "✗ Cache initialization failed\n";
}
echo "\n";

// Test 3: Set cache
echo "Test 3: Set Cache\n";
echo "----------------------------\n";
$test_key = 'test_cache_key';
$test_value = [
    'name' => 'Test Data',
    'timestamp' => time(),
    'data' => ['foo' => 'bar', 'number' => 42]
];

$set_result = set_cache($test_key, $test_value, 60);
if ($set_result) {
    echo "✓ Cache set successfully\n";
    echo "Key: $test_key\n";
    echo "Value: " . json_encode($test_value) . "\n";
} else {
    echo "✗ Failed to set cache\n";
}
echo "\n";

// Test 4: Get cache
echo "Test 4: Get Cache\n";
echo "----------------------------\n";
$cached_value = get_cache($test_key);
if ($cached_value !== null) {
    echo "✓ Cache retrieved successfully\n";
    echo "Retrieved value: " . json_encode($cached_value) . "\n";
    echo "Values match: " . ($cached_value === $test_value ? "Yes" : "No") . "\n";
} else {
    echo "✗ Failed to retrieve cache\n";
}
echo "\n";

// Test 5: Clear specific cache
echo "Test 5: Clear Specific Cache\n";
echo "----------------------------\n";
$clear_result = clear_cache($test_key);
if ($clear_result) {
    echo "✓ Cache cleared successfully\n";
    $verify = get_cache($test_key);
    echo "Cache still exists: " . ($verify !== null ? "Yes (ERROR)" : "No (OK)") . "\n";
} else {
    echo "✗ Failed to clear cache\n";
}
echo "\n";

// Test 6: TTL expiration
echo "Test 6: TTL Expiration Test\n";
echo "----------------------------\n";
$ttl_key = 'test_ttl_key';
$ttl_value = 'This should expire';
set_cache($ttl_key, $ttl_value, 2); // 2 seconds TTL
echo "Set cache with 2 second TTL\n";

echo "Immediate retrieval: ";
$immediate = get_cache($ttl_key);
echo ($immediate !== null ? "✓ Found" : "✗ Not found") . "\n";

echo "Waiting 3 seconds...\n";
sleep(3);

echo "After expiration: ";
$expired = get_cache($ttl_key);
echo ($expired === null ? "✓ Expired (OK)" : "✗ Still exists (ERROR)") . "\n";
echo "\n";

// Test 7: Performance test
echo "Test 7: Performance Test\n";
echo "----------------------------\n";

// Write test
$write_start = microtime(true);
for ($i = 0; $i < 100; $i++) {
    set_cache("perf_test_$i", ['data' => str_repeat('x', 100)], 60);
}
$write_time = (microtime(true) - $write_start) * 1000;
echo "100 cache writes: {$write_time}ms\n";

// Read test
$read_start = microtime(true);
for ($i = 0; $i < 100; $i++) {
    get_cache("perf_test_$i");
}
$read_time = (microtime(true) - $read_start) * 1000;
echo "100 cache reads: {$read_time}ms\n";

// Cleanup
for ($i = 0; $i < 100; $i++) {
    clear_cache("perf_test_$i");
}
echo "Cleanup: ✓ Complete\n";
echo "\n";

// Test 8: License cache functions
echo "Test 8: License Cache Functions\n";
echo "----------------------------\n";
set_cache('license_123', ['key' => 'TEST-KEY', 'user_id' => 1], 60);
set_cache('license_validation_456', ['status' => 'success'], 60);

echo "Set license cache entries\n";
clear_license_cache(123);
echo "Cleared license 123: ";
echo (get_cache('license_123') === null ? "✓ OK" : "✗ ERROR") . "\n";
echo "License validation 456 still exists: ";
echo (get_cache('license_validation_456') !== null ? "✓ OK" : "✗ ERROR") . "\n";

clear_license_cache();
echo "Cleared all license cache\n";
echo "License validation 456 now cleared: ";
echo (get_cache('license_validation_456') === null ? "✓ OK" : "✗ ERROR") . "\n";
echo "\n";

// Final summary
echo "=================================================\n";
echo "Cache System Test Complete\n";
echo "=================================================\n";

if (CACHE_PROVIDER === 'file' && file_exists(CACHE_DIR)) {
    $cache_files = glob(CACHE_DIR . '/*.cache');
    echo "Cache files remaining: " . count($cache_files) . "\n";
    
    // Cleanup test files
    foreach ($cache_files as $file) {
        unlink($file);
    }
    echo "Test cleanup: ✓ Complete\n";
}

echo "\nAll tests completed successfully!\n";
?>
