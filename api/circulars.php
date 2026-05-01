<?php
/**
 * API: Circulars
 * GET /api/circulars.php          - list circulars (paginated)
 * GET /api/circulars.php?id=N     - get single circular
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

require_once __DIR__ . '/../config/database.php';

function jsonResponse(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    jsonResponse(['error' => 'Method not allowed'], 405);
}

try {
    $pdo = db();

    // Single circular
    $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($id) {
        $stmt = $pdo->prepare('SELECT id, title, description, circular_type, source, source_url, pdf_path, is_active, created_at FROM circulars WHERE id = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$id]);
        $circular = $stmt->fetch();
        if (!$circular) {
            jsonResponse(['error' => 'Circular not found'], 404);
        }
        jsonResponse(['data' => $circular]);
    }

    // List circulars
    $type   = filter_input(INPUT_GET, 'type',   FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
    $page   = max(1, (int)(filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1));
    $limit  = min(50, max(1, (int)(filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 10)));
    $offset = ($page - 1) * $limit;

    $validTypes = ['academic', 'examination', 'events', 'placement', 'timetable', 'general'];
    $where  = ['is_active = 1'];
    $params = [];

    if ($type && in_array($type, $validTypes)) {
        $where[]         = 'circular_type = :type';
        $params[':type'] = $type;
    }
    if ($search) {
        $where[]           = '(title LIKE :s OR description LIKE :s2)';
        $params[':s']       = "%$search%";
        $params[':s2']      = "%$search%";
    }

    $whereSQL = implode(' AND ', $where);

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM circulars WHERE $whereSQL");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    $params[':lim'] = $limit;
    $params[':off'] = $offset;
    $stmt = $pdo->prepare("SELECT id, title, description, circular_type, source, source_url, pdf_path, created_at FROM circulars WHERE $whereSQL ORDER BY created_at DESC LIMIT :lim OFFSET :off");
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, in_array($k, [':lim', ':off']) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    $circulars = $stmt->fetchAll();

    jsonResponse([
        'data'       => $circulars,
        'pagination' => [
            'total'        => $total,
            'page'         => $page,
            'limit'        => $limit,
            'total_pages'  => (int)ceil($total / $limit),
        ],
    ]);
} catch (Exception $e) {
    jsonResponse(['error' => 'Internal server error'], 500);
}
