<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
$pageTitle = 'Manage Circulars';

// Handle toggle active / delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);
    try {
        if ($action === 'toggle' && $id) {
            db()->prepare('UPDATE circulars SET is_active = NOT is_active WHERE id = ?')->execute([$id]);
            setFlash('success', 'Circular status updated.');
        } elseif ($action === 'delete' && $id) {
            // Remove PDF if exists
            $row = db()->prepare('SELECT pdf_path FROM circulars WHERE id = ?');
            $row->execute([$id]);
            $r = $row->fetch();
            if ($r && $r['pdf_path'] && file_exists(UPLOAD_DIR . basename($r['pdf_path']))) {
                unlink(UPLOAD_DIR . basename($r['pdf_path']));
            }
            db()->prepare('DELETE FROM circulars WHERE id = ?')->execute([$id]);
            setFlash('success', 'Circular deleted successfully.');
        }
    } catch (Exception $e) {
        setFlash('error', 'Operation failed. Please try again.');
    }
    header('Location: ' . APP_URL . '/admin/circulars.php');
    exit;
}

$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$type   = filter_input(INPUT_GET, 'type',   FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$page   = max(1, (int)(filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1));
$limit  = 15;
$offset = ($page - 1) * $limit;

$typeBadges = [
    'academic'    => 'bg-blue-100 text-blue-800',
    'examination' => 'bg-red-100 text-red-800',
    'events'      => 'bg-green-100 text-green-800',
    'placement'   => 'bg-purple-100 text-purple-800',
    'timetable'   => 'bg-yellow-100 text-yellow-800',
    'general'     => 'bg-gray-100 text-gray-800',
];

try {
    $pdo    = db();
    $where  = ['1=1'];
    $params = [];
    if ($search) {
        $where[]           = '(title LIKE :s OR description LIKE :s2)';
        $params[':s']       = "%$search%";
        $params[':s2']      = "%$search%";
    }
    if ($type) {
        $where[]         = 'circular_type = :type';
        $params[':type'] = $type;
    }
    $whereSQL   = implode(' AND ', $where);
    $countStmt  = $pdo->prepare("SELECT COUNT(*) FROM circulars WHERE $whereSQL");
    $countStmt->execute($params);
    $total      = (int)$countStmt->fetchColumn();
    $totalPages = (int)ceil($total / $limit);

    $params[':lim'] = $limit;
    $params[':off'] = $offset;
    $stmt = $pdo->prepare("SELECT * FROM circulars WHERE $whereSQL ORDER BY created_at DESC LIMIT :lim OFFSET :off");
    foreach ($params as $k => $v) {
        $stmt->bindValue($k, $v, in_array($k, [':lim', ':off']) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    $circulars = $stmt->fetchAll();
    $dbOk = true;
} catch (Exception $e) {
    $circulars = [];
    $total = 0;
    $totalPages = 1;
    $dbOk = false;
}

include __DIR__ . '/../includes/admin_header.php';
?>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">Circulars</h1>
        <p class="text-gray-500 text-sm mt-1">Total: <?php echo $total; ?></p>
    </div>
    <a href="<?php echo APP_URL; ?>/admin/add_circular.php"
       class="bg-blue-800 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-semibold transition-colors">
        + Add Circular
    </a>
</div>

<!-- Filters -->
<form method="GET" class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-6 flex flex-wrap gap-3">
    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
           placeholder="Search title or description..."
           class="flex-1 min-w-48 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
    <select name="type" class="px-4 py-2 border border-gray-300 rounded-lg focus:outline-none text-sm">
        <option value="">All Types</option>
        <?php foreach (array_keys($typeBadges) as $t): ?>
            <option value="<?php echo $t; ?>" <?php echo $type === $t ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit" class="bg-blue-800 text-white px-5 py-2 rounded-lg text-sm font-medium">Filter</button>
    <a href="<?php echo APP_URL; ?>/admin/circulars.php" class="text-gray-500 hover:text-gray-700 px-3 py-2 text-sm">Reset</a>
</form>

<?php if (!$dbOk): ?>
    <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 p-4 rounded-xl mb-4">⚠️ Database not connected.</div>
<?php endif; ?>

<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 border-b border-gray-100">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Title</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Source</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($circulars)): ?>
                    <tr><td colspan="6" class="px-6 py-10 text-center text-gray-400">No circulars found.</td></tr>
                <?php else: ?>
                    <?php foreach ($circulars as $c): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900 max-w-xs truncate"><?php echo htmlspecialchars($c['title']); ?></div>
                                <?php if ($c['pdf_path']): ?>
                                    <a href="<?php echo htmlspecialchars(UPLOAD_URL . basename($c['pdf_path'])); ?>"
                                       class="text-blue-500 hover:underline text-xs mt-0.5 inline-block" target="_blank">📎 PDF</a>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 py-4">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium <?php echo $typeBadges[$c['circular_type']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo ucfirst($c['circular_type']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 text-gray-500 capitalize"><?php echo htmlspecialchars($c['source']); ?></td>
                            <td class="px-4 py-4">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium <?php echo $c['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <span class="w-1.5 h-1.5 rounded-full <?php echo $c['is_active'] ? 'bg-green-500' : 'bg-red-400'; ?>"></span>
                                    <?php echo $c['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td class="px-4 py-4 text-gray-500 text-xs"><?php echo date('M d, Y', strtotime($c['created_at'])); ?></td>
                            <td class="px-4 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="toggle">
                                        <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                        <button type="submit"
                                                class="text-xs px-3 py-1.5 rounded-lg border font-medium transition-colors <?php echo $c['is_active'] ? 'text-orange-600 border-orange-200 hover:bg-orange-50' : 'text-green-600 border-green-200 hover:bg-green-50'; ?>">
                                            <?php echo $c['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                        </button>
                                    </form>
                                    <form method="POST" class="inline" onsubmit="return confirm('Delete this circular?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                        <button type="submit" class="text-xs px-3 py-1.5 rounded-lg border text-red-600 border-red-200 hover:bg-red-50 font-medium transition-colors">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
        <div class="px-6 py-4 border-t border-gray-100 flex justify-center gap-2">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <a href="?page=<?php echo $p; ?>&type=<?php echo urlencode($type); ?>&search=<?php echo urlencode($search); ?>"
                   class="px-3 py-1.5 rounded-lg text-sm <?php echo $p === $page ? 'bg-blue-800 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                    <?php echo $p; ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
