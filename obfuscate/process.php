<?php
// AJAX endpoint: POST script (text or uploaded file) and options (JSON) -> obfuscated file download.
require_once '../includes/config.php';
redirect_if_not_logged_in();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['error' => 'Method not allowed'], 405);
}

$script = $_POST['script'] ?? '';
if (isset($_FILES['script']) && is_uploaded_file($_FILES['script']['tmp_name'])) {
    $script = file_get_contents($_FILES['script']['tmp_name']);
}
if (trim((string) $script) === '') {
    json_response(['error' => 'No script provided'], 400);
}

$options = json_decode($_POST['options'] ?? '[]', true);
$result = obfuscate_lua_script($pdo, $_SESSION['user_id'], $script, is_array($options) ? $options : []);

if (isset($result['error'])) {
    json_response(['error' => $result['error']], 400);
}
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="obfuscated_script.lua"');
echo $result['code'];
