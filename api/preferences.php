<?php
/**
 * API: User Preferences
 * GET  /api/preferences.php  - get current user preferences (requires session)
 * POST /api/preferences.php  - save preferences (requires session)
 */
header('Content-Type: application/json');

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';

function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

startSession();
if (!isUserLoggedIn()) {
    jsonResponse(['error' => 'Unauthorized'], 401);
}

$user     = getCurrentUser();
$allTypes = ['academic', 'examination', 'events', 'placement', 'timetable', 'general'];

try {
    $pdo = db();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare('SELECT * FROM preferences WHERE user_id = ? LIMIT 1');
        $stmt->execute([$user['id']]);
        $prefs = $stmt->fetch();
        if ($prefs && isset($prefs['circular_types'])) {
            $prefs['circular_types'] = json_decode($prefs['circular_types'], true) ?? [];
        }
        jsonResponse(['data' => $prefs ?: null]);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input    = json_decode(file_get_contents('php://input'), true) ?? $_POST;
        $degree   = trim($input['degree']     ?? '');
        $dept     = trim($input['department'] ?? '');
        $semester = trim($input['semester']   ?? '');
        $types    = $input['circular_types']  ?? [];
        $types    = array_values(array_intersect((array)$types, $allTypes));

        $typesJson = json_encode($types);
        $stmt = $pdo->prepare('SELECT id FROM preferences WHERE user_id = ? LIMIT 1');
        $stmt->execute([$user['id']]);
        $exists = $stmt->fetch();

        if ($exists) {
            $pdo->prepare('UPDATE preferences SET degree=?, department=?, semester=?, circular_types=? WHERE user_id=?')
                ->execute([$degree ?: null, $dept ?: null, $semester ?: null, $typesJson, $user['id']]);
        } else {
            $pdo->prepare('INSERT INTO preferences (user_id, degree, department, semester, circular_types) VALUES (?,?,?,?,?)')
                ->execute([$user['id'], $degree ?: null, $dept ?: null, $semester ?: null, $typesJson]);
        }
        jsonResponse(['success' => true, 'message' => 'Preferences saved.']);
    }

    jsonResponse(['error' => 'Method not allowed'], 405);
} catch (Exception $e) {
    jsonResponse(['error' => 'Internal server error'], 500);
}
