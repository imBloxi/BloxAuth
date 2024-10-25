<?php
require_once '../../../includes/config.php';
require_once '../../../includes/functions.php';

header('Content-Type: application/json');

// Middleware to check API token
if (!isset($_GET['api_token']) || !validate_api_token($pdo, $_GET['api_token'])) {
    http_response_code(401); // Unauthorized
    echo json_encode(['error' => 'Invalid or missing API token']);
    exit;
}

$type = $_GET['type'] ?? '';

switch ($type) {
    case 'create':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            $response = create_license($pdo, $data, $_GET['user_id']);
            echo json_encode($response);
        } else {
            http_response_code(405); // Method Not Allowed
            echo json_encode(['error' => 'POST method required']);
        }
        break;
    case 'renew':
        if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
            $data = json_decode(file_get_contents('php://input'), true);
            $response = renew_license($pdo, $data['user_id'], $data['license_id'], $data['new_valid_until']);
            echo json_encode($response);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'PUT method required']);
        }
        break;
    case 'revoke':
        if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
            $license_id = $_GET['license_id'];
            $response = revoke_license($pdo, $license_id);
            echo json_encode($response);
        } else {
            http_response_code(405);
            echo json_encode(['error' => 'DELETE method required']);
        }
        break;
    default:
        http_response_code(400); // Bad Request
        echo json_encode(['error' => 'Invalid request type']);
        break;
}

function validate_api_token($pdo, $token) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM api_keys WHERE token = ?");
    $stmt->execute([$token]);
    return $stmt->fetchColumn() > 0;
}

?>
