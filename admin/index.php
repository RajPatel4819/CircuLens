<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
$pageTitle = 'Dashboard';
include __DIR__ . '/../includes/admin_header.php';

try {
    $pdo = db();
    $totalCirculars = (int)$pdo->query('SELECT COUNT(*) FROM circulars')->fetchColumn();
    $activeCirculars = (int)$pdo->query('SELECT COUNT(*) FROM circulars WHERE is_active = 1')->fetchColumn();
    $totalUsers = (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $totalNotifications = (int)$pdo->query('SELECT COUNT(*) FROM notifications')->fetchColumn();
    $recentCirculars = $pdo->query('SELECT * FROM circulars ORDER BY created_at DESC, id DESC LIMIT 5')->fetchAll();
    $dbOk = true;
} catch (Exception $e) {
    $totalCirculars = $activeCirculars = $totalUsers = $totalNotifications = 0;
    $recentCirculars = [];
    $dbOk = false;
}

$typeBadges = [
    'academic'    => 'bg-blue-100 text-blue-800',
    'examination' => 'bg-red-100 text-red-800',
    'events'      => 'bg-green-100 text-green-800',
    'placement'   => 'bg-purple-100 text-purple-800',
    'timetable'   => 'bg-yellow-100 text-yellow-800',
    'general'     => 'bg-gray-100 text-gray-800',
];
?>

<?php if (!$dbOk): ?>
    <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 p-4 rounded-xl mb-6">
        ⚠️ Database not connected. Please configure <code>config/config.php</code> and import <code>database.sql</code>.
    </div>
<?php endif; ?>

<div class="mb-8">
    <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
    <p class="text-gray-500 text-sm mt-1">Overview of CircuLens system</p>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 font-medium">Total Circulars</p>
                <p class="text-3xl font-bold text-gray-900 mt-1"><?php echo $totalCirculars; ?></p>
            </div>
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center text-2xl">📋</div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 font-medium">Active Circulars</p>
                <p class="text-3xl font-bold text-green-600 mt-1"><?php echo $activeCirculars; ?></p>
            </div>
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center text-2xl">✅</div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 font-medium">Registered Users</p>
                <p class="text-3xl font-bold text-purple-600 mt-1"><?php echo $totalUsers; ?></p>
            </div>
            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center text-2xl">👥</div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-sm p-6 border border-gray-100">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-gray-500 font-medium">Notifications Sent</p>
                <p class="text-3xl font-bold text-orange-500 mt-1"><?php echo $totalNotifications; ?></p>
            </div>
            <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center text-2xl">🔔</div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
    <a href="<?php echo APP_URL; ?>/admin/add_circular.php"
       class="bg-blue-800 hover:bg-blue-700 text-white rounded-xl p-5 flex items-center gap-4 transition-colors shadow-sm">
        <span class="text-3xl">➕</span>
        <div>
            <p class="font-semibold">Add New Circular</p>
            <p class="text-blue-200 text-sm">Upload or create a circular</p>
        </div>
    </a>
    <a href="<?php echo APP_URL; ?>/admin/circulars.php"
       class="bg-white hover:bg-gray-50 text-gray-800 rounded-xl p-5 flex items-center gap-4 transition-colors shadow-sm border border-gray-200">
        <span class="text-3xl">📄</span>
        <div>
            <p class="font-semibold">Manage Circulars</p>
            <p class="text-gray-500 text-sm">View, edit, or delete circulars</p>
        </div>
    </a>
    <a href="<?php echo APP_URL; ?>"
       class="bg-orange-500 hover:bg-orange-600 text-white rounded-xl p-5 flex items-center gap-4 transition-colors shadow-sm">
        <span class="text-3xl">🌐</span>
        <div>
            <p class="font-semibold">View Public Site</p>
            <p class="text-orange-100 text-sm">See what users see</p>
        </div>
    </a>
</div>

<!-- Recent Circulars -->
<div class="bg-white rounded-xl shadow-sm border border-gray-100">
    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
        <h2 class="text-lg font-semibold text-gray-900">Recent Circulars</h2>
        <a href="<?php echo APP_URL; ?>/admin/circulars.php" class="text-blue-600 hover:text-blue-800 text-sm font-medium">View All →</a>
    </div>
    <?php if (empty($recentCirculars)): ?>
        <div class="px-6 py-10 text-center text-gray-400">No circulars yet. <a href="<?php echo APP_URL; ?>/admin/add_circular.php" class="text-blue-600 hover:underline">Add one</a>.</div>
    <?php else: ?>
        <div class="divide-y divide-gray-50">
            <?php foreach ($recentCirculars as $c): ?>
                <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <span class="text-gray-800 text-sm font-medium truncate">
                            <?php echo htmlspecialchars($c['description'] ?: $c['title']); ?>
                        </span>
                    </div>
                    <div class="flex items-center gap-4 ml-4 shrink-0">
                        <span class="text-gray-400 text-xs"><?php echo date('M d, Y', strtotime($c['created_at'])); ?></span>
                        <span class="w-2 h-2 rounded-full <?php echo $c['is_active'] ? 'bg-green-500' : 'bg-red-400'; ?>"></span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
