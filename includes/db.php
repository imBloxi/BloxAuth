<?php
// Database connection. Settings come from environment variables.
$db_host = getenv('DB_HOST') ?: 'localhost';
$db_name = getenv('DB_NAME') ?: 'roblox_licensing';
$db_user = getenv('DB_USER') ?: 'root';
$db_pass = getenv('DB_PASS') ?: '';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Optional TLS to the database server: set DB_SSL_CA to the CA certificate path.
if (getenv('DB_SSL_CA')) {
    $options[PDO::MYSQL_ATTR_SSL_CA] = getenv('DB_SSL_CA');
}

try {
    $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, $options);
} catch (PDOException $e) {
    error_log('Database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Database connection failed.');
}
unset($db_host, $db_name, $db_user, $db_pass, $options);
