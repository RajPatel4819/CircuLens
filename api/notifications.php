<?php
/**
 * API: Notifications
 * GET  /api/notifications.php         - list notifications for logged-in user
 * POST /api/notifications.php?mark_read=1  - mark all as read
 * POST /api/notifications.php?mark_read=N  - mark specific notification as read
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

$user = getCurrentUser();

try {
    $pdo = db();

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $markRead = filter_input(INPUT_GET, 'mark_read', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
        if ($markRead === 'all') {
            $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$user['id']]);
            jsonResponse(['success' => true, 'message' => 'All notifications marked as read.']);
        } elseif (is_numeric($markRead) && (int)$markRead > 0) {
            $pdo->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?')
                ->execute([(int)$markRead, $user['id']]);
            jsonResponse(['success' => true]);
        }
        jsonResponse(['error' => 'Bad request'], 400);
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $page   = max(1, (int)(filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1));
        $limit  = 20;
        $offset = ($page - 1) * $limit;

        $stmt = $pdo->prepare(
            'SELECT n.id, n.is_read, n.is_sent, n.sent_at, n.created_at,
                    c.id AS circular_id, c.title, c.circular_type, c.description
             FROM notifications n
             JOIN circulars c ON c.id = n.circular_id
             WHERE n.user_id = ?
             ORDER BY n.created_at DESC
             LIMIT ? OFFSET ?'
        );
        $stmt->execute([$user['id'], $limit, $offset]);
        $notifications = $stmt->fetchAll();

        $countStmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ?');
        $countStmt->execute([$user['id']]);
        $total = (int)$countStmt->fetchColumn();

        $unreadStmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
        $unreadStmt->execute([$user['id']]);
        $unread = (int)$unreadStmt->fetchColumn();

        jsonResponse([
            'data'   => $notifications,
            'total'  => $total,
            'unread' => $unread,
            'page'   => $page,
        ]);
    }

    jsonResponse(['error' => 'Method not allowed'], 405);
} catch (Exception $e) {
    jsonResponse(['error' => 'Internal server error'], 500);
}
