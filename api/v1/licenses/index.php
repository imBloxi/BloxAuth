<?php
// License management API. Auth: X-API-Key header, "Authorization: Bearer <key>" or ?api_token=<key>.
// Every action only touches licenses owned by the API key's user.
//   POST   ?type=create                     JSON license fields (see generate_license.php)
//   PUT    ?type=renew                      JSON {license_id, new_valid_until}
//   POST   ?type=ban                        JSON {license_id, ban_reason}
//   DELETE ?type=revoke&license_id=<id>
//   GET    ?type=list
require_once '../../../includes/config.php';

$user_id = validate_api_key(get_request_api_key() ?? ($_GET['api_token'] ?? null));
if (!$user_id) {
    json_response(['error' => 'Invalid or missing API token'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$type = $_GET['type'] ?? '';
$data = read_json_body() ?? [];

$require_method = function ($expected) use ($method) {
    if ($method !== $expected) {
        json_response(['error' => "$expected method required"], 405);
    }
};

switch ($type) {
    case 'create':
        $require_method('POST');
        $result = create_license($pdo, $user_id, $data);
        json_response($result, $result['success'] ? 201 : 400);

    case 'renew':
        $require_method('PUT');
        $ok = renew_license($pdo, $user_id, $data['license_id'] ?? 0, $data['new_valid_until'] ?? null);
        json_response(['success' => $ok], $ok ? 200 : 404);

    case 'ban':
        $require_method('POST');
        $ok = ban_license($pdo, $user_id, $data['license_id'] ?? 0, $data['ban_reason'] ?? '');
        json_response(['success' => $ok], $ok ? 200 : 404);

    case 'revoke':
        $require_method('DELETE');
        $ok = revoke_license($pdo, $user_id, $_GET['license_id'] ?? 0);
        json_response(['success' => $ok], $ok ? 200 : 404);

    case 'list':
        $require_method('GET');
        json_response(['success' => true, 'licenses' => fetch_licenses($pdo, $user_id, $_GET)]);

    default:
        json_response(['error' => 'Invalid request type'], 400);
}
