<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
$pageTitle = 'GTU Circulars';

$type   = filter_input(INPUT_GET, 'type',   FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$search = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$page   = max(1, (int)(filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1));
$limit  = 9;
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
    $where  = ['c.is_active = 1'];
    $params = [];

    if ($type) {
        $where[]         = 'c.circular_type = :type';
        $params[':type'] = $type;
    }
    if ($search) {
        $where[]           = '(c.title LIKE :search OR c.description LIKE :search2)';
        $params[':search']  = "%$search%";
        $params[':search2'] = "%$search%";
    }
    $whereSQL = implode(' AND ', $where);

    $totalStmt = $pdo->prepare("SELECT COUNT(*) FROM circulars c WHERE $whereSQL");
    $totalStmt->execute($params);
    $total = (int)$totalStmt->fetchColumn();

    $params[':limit']  = $limit;
    $params[':offset'] = $offset;
    $stmt = $pdo->prepare("SELECT * FROM circulars c WHERE $whereSQL ORDER BY c.created_at DESC LIMIT :limit OFFSET :offset");
    foreach ($params as $k => $v) {
        $type_flag = ($k === ':limit' || $k === ':offset') ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($k, $v, $type_flag);
    }
    $stmt->execute();
    $circulars  = $stmt->fetchAll();
    $totalPages = (int)ceil($total / $limit);
    $dbError    = false;
} catch (Exception $e) {
    $circulars  = [];
    $total      = 0;
    $totalPages = 1;
    $dbError    = true;
}

include __DIR__ . '/includes/header.php';
?>
<main class="flex-1">

<!-- Hero -->
<section class="bg-gradient-to-r from-blue-800 to-blue-900 text-white py-16">
    <div class="max-w-7xl mx-auto px-4 text-center">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">
            GTU <span class="text-orange-400">Circulars</span>
        </h1>
        <p class="text-blue-200 text-lg mb-8">
            Stay updated with the latest notices from Gujarat Technological University
        </p>
        <form method="GET" class="max-w-2xl mx-auto flex flex-col sm:flex-row gap-2">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                   placeholder="Search circulars..."
                   class="flex-1 px-4 py-3 rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-orange-400">
            <select name="type" class="px-4 py-3 rounded-xl text-gray-800 focus:outline-none focus:ring-2 focus:ring-orange-400">
                <option value="">All Types</option>
                <?php foreach (array_keys($typeBadges) as $t): ?>
                    <option value="<?php echo $t; ?>" <?php echo $type === $t ? 'selected' : ''; ?>>
                        <?php echo ucfirst($t); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="bg-orange-600 hover:bg-orange-500 px-6 py-3 rounded-xl font-semibold transition-colors">
                Search
            </button>
        </form>
    </div>
</section>

<!-- Filter Pills -->
<section class="bg-white border-b shadow-sm">
    <div class="max-w-7xl mx-auto px-4 py-3 flex flex-wrap gap-2">
        <a href="<?php echo APP_URL; ?>?search=<?php echo urlencode($search); ?>"
           class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors <?php echo !$type ? 'bg-blue-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">
            All
        </a>
        <?php foreach ($typeBadges as $t => $cls): ?>
            <a href="<?php echo APP_URL; ?>?type=<?php echo $t; ?>&search=<?php echo urlencode($search); ?>"
               class="px-4 py-1.5 rounded-full text-sm font-medium transition-colors <?php echo $type === $t ? 'bg-blue-800 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'; ?>">
                <?php echo ucfirst($t); ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- Circulars Grid -->
<section class="max-w-7xl mx-auto px-4 py-10">
    <?php if ($dbError): ?>
        <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 p-4 rounded-xl mb-6 flex items-center gap-3">
            <span class="text-2xl">⚠️</span>
            <div>
                <strong>Database not connected.</strong>
                Please import <code>database.sql</code> and configure <code>/config/config.php</code>.
            </div>
        </div>
    <?php endif; ?>

    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            <?php echo $type ? ucfirst($type) . ' Circulars' : 'All Circulars'; ?>
            <span class="text-sm font-normal text-gray-500 ml-2">(<?php echo $total; ?> found)</span>
        </h2>
        <a href="https://www.gtu.ac.in" target="_blank" rel="noopener"
           class="flex items-center text-blue-600 hover:text-blue-800 text-sm font-medium">
            Visit GTU Website &rarr;
        </a>
    </div>

    <?php if (empty($circulars)): ?>
        <div class="text-center py-20 bg-white rounded-xl shadow-sm border border-gray-100">
            <div class="text-6xl mb-4">📋</div>
            <p class="text-gray-500 text-lg mb-2">No circulars found.</p>
            <?php if ($search || $type): ?>
                <a href="<?php echo APP_URL; ?>" class="mt-2 inline-block text-blue-600 hover:underline text-sm">
                    ← Clear filters
                </a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($circulars as $c): ?>
                <div class="bg-white rounded-xl shadow-md hover:shadow-xl transition-shadow duration-200 overflow-hidden border border-gray-100 flex flex-col">
                    <div class="p-6 flex-1 flex flex-col">
                        <div class="flex items-center justify-between mb-3">
                            <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $typeBadges[$c['circular_type']] ?? 'bg-gray-100 text-gray-800'; ?>">
                                <?php echo ucfirst($c['circular_type']); ?>
                            </span>
                            <span class="text-gray-400 text-xs">
                                <?php echo date('M d, Y', strtotime($c['created_at'])); ?>
                            </span>
                        </div>
                        <h3 class="text-gray-900 font-semibold text-base mb-2 line-clamp-2 flex-1">
                            <?php echo htmlspecialchars($c['title']); ?>
                        </h3>
                        <p class="text-gray-500 text-sm mb-4 line-clamp-3">
                            <?php echo htmlspecialchars($c['description'] ?? ''); ?>
                        </p>
                        <div class="flex items-center justify-between pt-2 border-t border-gray-50">
                            <?php if (!empty($c['pdf_path'])): ?>
                                <a href="<?php echo htmlspecialchars(UPLOAD_URL . basename($c['pdf_path'])); ?>"
                                   class="flex items-center gap-1 text-orange-600 hover:text-orange-500 text-sm font-medium"
                                   target="_blank" rel="noopener">
                                    📥 Download PDF
                                </a>
                            <?php else: ?>
                                <span class="text-gray-300 text-sm">No PDF attached</span>
                            <?php endif; ?>
                            <span class="text-gray-400 text-xs capitalize"><?php echo htmlspecialchars($c['source']); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="flex justify-center mt-10 gap-2 flex-wrap">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>&type=<?php echo urlencode($type); ?>&search=<?php echo urlencode($search); ?>"
                   class="px-4 py-2 rounded-lg text-sm font-medium bg-white text-gray-700 hover:bg-blue-50 border border-gray-200">
                    &laquo; Prev
                </a>
            <?php endif; ?>
            <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                <a href="?page=<?php echo $p; ?>&type=<?php echo urlencode($type); ?>&search=<?php echo urlencode($search); ?>"
                   class="px-4 py-2 rounded-lg text-sm font-medium <?php echo $p === $page ? 'bg-blue-800 text-white' : 'bg-white text-gray-700 hover:bg-blue-50 border border-gray-200'; ?>">
                    <?php echo $p; ?>
                </a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?>
                <a href="?page=<?php echo $page + 1; ?>&type=<?php echo urlencode($type); ?>&search=<?php echo urlencode($search); ?>"
                   class="px-4 py-2 rounded-lg text-sm font-medium bg-white text-gray-700 hover:bg-blue-50 border border-gray-200">
                    Next &raquo;
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<!-- CTA -->
<section class="bg-gradient-to-r from-orange-500 to-orange-600 text-white py-12">
    <div class="max-w-3xl mx-auto px-4 text-center">
        <h2 class="text-2xl md:text-3xl font-bold mb-3">Never Miss a Circular</h2>
        <p class="text-orange-100 mb-6">Register now to get personalized email alerts for GTU circulars relevant to your degree and department.</p>
        <div class="flex flex-col sm:flex-row gap-3 justify-center">
            <a href="<?php echo APP_URL; ?>/user/register.php"
               class="bg-white text-orange-600 hover:bg-orange-50 px-8 py-3 rounded-xl font-semibold transition-colors">
                Create Free Account
            </a>
            <a href="<?php echo APP_URL; ?>/user/login.php"
               class="border-2 border-white text-white hover:bg-orange-700 px-8 py-3 rounded-xl font-semibold transition-colors">
                Sign In
            </a>
        </div>
    </div>
</section>

</main>
<?php include __DIR__ . '/includes/footer.php'; ?>
