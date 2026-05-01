<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
$pageTitle = 'Dashboard';

$typeBadges = [
    'academic'    => 'bg-blue-100 text-blue-800',
    'examination' => 'bg-red-100 text-red-800',
    'events'      => 'bg-green-100 text-green-800',
    'placement'   => 'bg-purple-100 text-purple-800',
    'timetable'   => 'bg-yellow-100 text-yellow-800',
    'general'     => 'bg-gray-100 text-gray-800',
];

$user = getCurrentUser();
$type   = filter_input(INPUT_GET, 'type',   FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$page   = max(1, (int)(filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1));
$limit  = 9;
$offset = ($page - 1) * $limit;

try {
    $pdo  = db();
    $prefs = $pdo->prepare('SELECT * FROM preferences WHERE user_id = ? LIMIT 1');
    $prefs->execute([$user['id']]);
    $preferences = $prefs->fetch();

    $unreadCount = (int)$pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0')
        ->execute([$user['id']]) ? $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0') : null;

    // Simpler approach
    $unreadStmt = $pdo->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $unreadStmt->execute([$user['id']]);
    $unreadCount = (int)$unreadStmt->fetchColumn();

    // Build circular query - show relevant circulars if preferences set
    $where  = ['c.is_active = 1'];
    $params = [];

    if ($type) {
        $where[]         = 'c.circular_type = :type';
        $params[':type'] = $type;
    } elseif ($preferences && !empty($preferences['circular_types'])) {
        $prefTypes = json_decode($preferences['circular_types'], true);
        if (is_array($prefTypes) && !empty($prefTypes)) {
            $placeholders = implode(',', array_fill(0, count($prefTypes), '?'));
            $where[]      = "c.circular_type IN ($placeholders)";
            foreach ($prefTypes as $pt) {
                $params[] = $pt;
            }
        }
    }

    if ($search) {
        $where[]           = '(c.title LIKE :s OR c.description LIKE :s2)';
        $params[':s']       = "%$search%";
        $params[':s2']      = "%$search%";
    }

    $whereSQL  = implode(' AND ', $where);
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM circulars c WHERE $whereSQL");
    $countStmt->execute(array_values($params));
    $total = (int)$countStmt->fetchColumn();

    $params[] = $limit;
    $params[] = $offset;
    $stmt     = $pdo->prepare("SELECT * FROM circulars c WHERE $whereSQL ORDER BY c.created_at DESC LIMIT ? OFFSET ?");
    $stmt->execute(array_values($params));
    $circulars  = $stmt->fetchAll();
    $totalPages = (int)ceil($total / $limit);
    $dbOk       = true;
} catch (Exception $e) {
    $circulars   = [];
    $total       = 0;
    $totalPages  = 1;
    $unreadCount = 0;
    $preferences = null;
    $dbOk        = false;
}

include __DIR__ . '/../includes/user_header.php';
?>

<div class="max-w-7xl mx-auto px-4 py-8">

    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-blue-800 to-blue-700 rounded-2xl text-white p-6 mb-8 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Welcome back, <?php echo htmlspecialchars($user['name']); ?>! 👋</h1>
            <p class="text-blue-200 mt-1">Here are circulars matching your preferences.</p>
        </div>
        <div class="flex items-center gap-3">
            <?php if ($unreadCount > 0): ?>
                <div class="bg-orange-500 text-white px-4 py-2 rounded-xl font-medium text-sm">
                    🔔 <?php echo $unreadCount; ?> new notification<?php echo $unreadCount > 1 ? 's' : ''; ?>
                </div>
            <?php endif; ?>
            <a href="<?php echo APP_URL; ?>/user/preferences.php"
               class="bg-white text-blue-800 px-4 py-2 rounded-xl font-medium text-sm hover:bg-blue-50 transition-colors">
                ⚙️ Preferences
            </a>
        </div>
    </div>

    <?php if (!$dbOk): ?>
        <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 p-4 rounded-xl mb-6">
            ⚠️ Database not connected. Please set up the database.
        </div>
    <?php endif; ?>

    <?php if (!$preferences && $dbOk): ?>
        <div class="bg-blue-50 border border-blue-200 text-blue-800 p-4 rounded-xl mb-6 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-xl">💡</span>
                <div>
                    <p class="font-medium">Set your preferences to get personalized circulars</p>
                    <p class="text-sm text-blue-600">Tell us your degree, department, and circular types you care about.</p>
                </div>
            </div>
            <a href="<?php echo APP_URL; ?>/user/preferences.php"
               class="bg-blue-800 text-white px-4 py-2 rounded-lg text-sm font-medium hover:bg-blue-700 shrink-0">
                Set Preferences
            </a>
        </div>
    <?php endif; ?>

    <!-- Filter Bar -->
    <form method="GET" class="flex flex-wrap gap-3 mb-6">
        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
               placeholder="Search circulars..."
               class="flex-1 min-w-48 px-4 py-2.5 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm bg-white">
        <select name="type" class="px-4 py-2.5 border border-gray-300 rounded-xl focus:outline-none text-sm bg-white">
            <option value="">All Types</option>
            <?php foreach (array_keys($typeBadges) as $t): ?>
                <option value="<?php echo $t; ?>" <?php echo $type === $t ? 'selected' : ''; ?>><?php echo ucfirst($t); ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="bg-blue-800 text-white px-5 py-2.5 rounded-xl text-sm font-medium hover:bg-blue-700">Search</button>
        <?php if ($search || $type): ?>
            <a href="<?php echo APP_URL; ?>/user/dashboard.php" class="px-4 py-2.5 text-gray-500 hover:text-gray-700 text-sm">Reset</a>
        <?php endif; ?>
    </form>

    <!-- Circulars -->
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-lg font-semibold text-gray-800">
            <?php echo $type ? ucfirst($type) . ' Circulars' : ($preferences ? 'Your Circulars' : 'All Circulars'); ?>
            <span class="text-sm font-normal text-gray-400 ml-1">(<?php echo $total; ?>)</span>
        </h2>
    </div>

    <?php if (empty($circulars)): ?>
        <div class="text-center py-20 bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="text-5xl mb-4">📭</div>
            <p class="text-gray-500 text-base">No circulars found.</p>
            <?php if ($type || $search): ?>
                <a href="<?php echo APP_URL; ?>/user/dashboard.php" class="text-blue-600 hover:underline text-sm mt-2 inline-block">Clear filters</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($circulars as $c): ?>
                <div class="bg-white rounded-xl shadow-sm hover:shadow-md transition-shadow duration-200 border border-gray-100 flex flex-col overflow-hidden">
                    <div class="p-5 flex-1 flex flex-col">
                        <div class="flex items-center justify-between mb-2">
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold <?php echo $typeBadges[$c['circular_type']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                <?php echo ucfirst($c['circular_type']); ?>
                            </span>
                            <span class="text-gray-400 text-xs"><?php echo date('M d, Y', strtotime($c['created_at'])); ?></span>
                        </div>
                        <h3 class="text-gray-900 font-semibold text-sm mb-2 line-clamp-2 flex-1">
                            <?php echo htmlspecialchars($c['title']); ?>
                        </h3>
                        <p class="text-gray-500 text-xs mb-3 line-clamp-2">
                            <?php echo htmlspecialchars($c['description'] ?? ''); ?>
                        </p>
                        <?php if (!empty($c['pdf_path'])): ?>
                            <a href="<?php echo htmlspecialchars(UPLOAD_URL . basename($c['pdf_path'])); ?>"
                               class="inline-flex items-center gap-1 text-orange-600 hover:text-orange-500 text-sm font-medium mt-auto"
                               target="_blank" rel="noopener">
                                📥 Download PDF
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <div class="flex justify-center mt-8 gap-2 flex-wrap">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page-1; ?>&type=<?php echo urlencode($type); ?>&search=<?php echo urlencode($search); ?>"
                       class="px-4 py-2 rounded-lg text-sm bg-white border border-gray-200 text-gray-700 hover:bg-blue-50">&laquo; Prev</a>
                <?php endif; ?>
                <?php for ($p = max(1, $page-2); $p <= min($totalPages, $page+2); $p++): ?>
                    <a href="?page=<?php echo $p; ?>&type=<?php echo urlencode($type); ?>&search=<?php echo urlencode($search); ?>"
                       class="px-4 py-2 rounded-lg text-sm <?php echo $p === $page ? 'bg-blue-800 text-white' : 'bg-white border border-gray-200 text-gray-700 hover:bg-blue-50'; ?>">
                        <?php echo $p; ?>
                    </a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?php echo $page+1; ?>&type=<?php echo urlencode($type); ?>&search=<?php echo urlencode($search); ?>"
                       class="px-4 py-2 rounded-lg text-sm bg-white border border-gray-200 text-gray-700 hover:bg-blue-50">Next &raquo;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/user_footer.php'; ?>
