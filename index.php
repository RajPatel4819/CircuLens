<?php
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/database.php';
$pageTitle = 'Circulars';

$selYear  = filter_input(INPUT_GET, 'year',  FILTER_VALIDATE_INT);
$selMonth = filter_input(INPUT_GET, 'month', FILTER_VALIDATE_INT);
$search   = filter_input(INPUT_GET, 'search', FILTER_SANITIZE_SPECIAL_CHARS) ?? '';
$page     = max(1, (int)(filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) ?: 1));
$limit    = 15;
$offset   = ($page - 1) * $limit;

try {
    $pdo    = db();
    
    // Fetch available years for filter
    $years = $pdo->query("SELECT DISTINCT YEAR(created_at) as yr FROM circulars WHERE is_active = 1 ORDER BY yr DESC")->fetchAll(PDO::FETCH_COLUMN);
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 5 => 'May', 6 => 'June',
        7 => 'July', 8 => 'August', 9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];

    $where  = ['c.is_active = 1'];
    $params = [];

    if ($selYear) {
        $where[] = 'YEAR(c.created_at) = :year';
        $params[':year'] = $selYear;
    }
    if ($selMonth) {
        $where[] = 'MONTH(c.created_at) = :month';
        $params[':month'] = $selMonth;
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

<!-- GTU Style Banner -->
<div class="bg-gtublue text-white py-8 md:py-12 border-t-4 border-gtured">
    <div class="max-w-7xl mx-auto px-4">
        <h1 class="text-3xl md:text-5xl font-light tracking-wide text-center lg:text-left">Circulars</h1>
    </div>
</div>

<main class="max-w-7xl mx-auto px-4 py-10 flex-1">
    <div class="flex flex-col lg:flex-row gap-10">
        
        <!-- Sidebar (GTU Style) -->
        <aside class="lg:col-span-1">
            <div class="bg-[#EBF3FE] rounded-lg p-6 space-y-8 sticky top-24">
                
                <!-- Student Portal -->
                <section>
                    <h2 class="text-[#1D2951] font-bold text-xl mb-4">Student Portal</h2>
                    <a href="<?php echo APP_URL; ?>/user/login.php" class="inline-block bg-[#D32F2F] text-white px-8 py-2.5 rounded-full font-bold text-sm shadow-md hover:bg-red-700 transition-colors uppercase tracking-wider">
                        LOGIN NOW
                    </a>
                    <div class="mt-4 border-b-4 border-[#1D2951] w-full"></div>
                </section>

                <!-- News Corner -->
                <section>
                    <h2 class="text-[#1D2951] font-bold text-xl mb-1">News Corner</h2>
                    <p class="text-[#555] text-[15px] mb-4 leading-tight">Exam Schedule, Guidelines & Circulars</p>
                    <div class="mt-2 border-b-4 border-[#1D2951] w-full mb-6"></div>
                    
                    <div class="space-y-4">
                        <?php 
                        // Show top 2 recent circulars as 'News'
                        $newsItems = array_slice($circulars, 0, 2);
                        foreach ($newsItems as $item): 
                        ?>
                        <div class="bg-white p-4 rounded shadow-sm border border-blue-50">
                            <span class="text-[11px] text-gray-400 font-bold block mb-1"><?php echo date('d-M-Y', strtotime($item['created_at'])); ?></span>
                            <a href="<?php echo !empty($item['pdf_path']) ? htmlspecialchars(UPLOAD_URL . basename($item['pdf_path'])) : '#'; ?>" 
                               target="_blank" class="text-blue-800 hover:text-gtured text-xs font-semibold leading-relaxed block">
                                <?php echo htmlspecialchars($item['title']); ?>
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <a href="#" class="inline-block mt-4 text-[#D32F2F] text-xs font-bold hover:underline uppercase tracking-widest">VIEW ALL</a>
                </section>

                <!-- New Horizons -->
                <section>
                    <h2 class="text-[#1D2951] font-bold text-xl mb-2">New Horizons</h2>
                    <div class="flex gap-2 mb-4">
                        <span class="text-gtured">▲</span>
                        <span class="text-gtured">▼</span>
                    </div>
                    <div class="mt-2 border-b-4 border-[#1D2951] w-full mb-4"></div>
                    <a href="#" class="inline-block text-[#D32F2F] text-xs font-bold hover:underline uppercase tracking-widest">VIEW ALL</a>
                </section>
            </div>
        </aside>

        <!-- Circular List -->
        <div class="w-full lg:w-3/4">
            
            <?php if ($dbError): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                    <p class="text-red-700 text-sm font-medium">Database connection failed. Please ensure MySQL is running and configured.</p>
                </div>
            <?php endif; ?>

            <!-- Year/Month Filters Only -->
            <div class="bg-white p-5 rounded border border-gray-200 shadow-sm mb-8">
                <form method="GET" class="flex flex-col md:flex-row gap-4 items-center">
                    <select name="year" class="w-full md:w-1/3 px-3 py-2.5 border border-gray-300 rounded text-sm text-gtured font-medium outline-none focus:border-gtublue">
                        <option value="">-- Select Year --</option>
                        <?php foreach ($years as $yr): ?>
                            <option value="<?php echo $yr; ?>" <?php echo $selYear == $yr ? 'selected' : ''; ?>>
                                <?php echo $yr; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <select name="month" class="w-full md:w-1/3 px-3 py-2.5 border border-gray-300 rounded text-sm text-gtured font-medium outline-none focus:border-gtublue">
                        <option value="">-- Select Month --</option>
                        <?php foreach ($months as $num => $name): ?>
                            <option value="<?php echo $num; ?>" <?php echo $selMonth == $num ? 'selected' : ''; ?>>
                                <?php echo $name; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="w-full md:w-1/3 bg-gtured text-white px-8 py-2.5 rounded font-bold hover:bg-red-700 transition-colors shadow-sm">
                        FILTER
                    </button>
                </form>
            </div>

            <!-- Clean List (GTU Style) -->
            <div class="bg-white rounded border border-gray-200 shadow-sm overflow-hidden">
                <div class="divide-y divide-gray-100">
                    <?php if (empty($circulars)): ?>
                        <div class="p-20 text-center text-gray-400 italic">No matching circulars found.</div>
                    <?php else: ?>
                        <?php foreach ($circulars as $c): ?>
                            <div class="p-5 hover:bg-gray-50 transition-colors border-l-4 border-transparent hover:border-gtured">
                                <a href="<?php echo !empty($c['pdf_path']) ? htmlspecialchars(UPLOAD_URL . basename($c['pdf_path'])) : '#'; ?>" 
                                   target="_blank" rel="noopener"
                                   class="text-[#0056b3] hover:underline font-normal text-[15px] md:text-base block mb-1 leading-normal">
                                    <?php echo htmlspecialchars($c['description'] ?: $c['title']); ?>
                                </a>
                                <div class="text-[13px] text-gray-500">
                                    <?php echo date('d-M-Y', strtotime($c['created_at'])); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="mt-10 flex justify-center gap-2">
                    <?php 
                    $queryStr = http_build_query([
                        'year'   => $selYear,
                        'month'  => $selMonth,
                        'search' => $search
                    ]);
                    ?>
                    <?php if ($page > 1): ?>
                        <a href="?page=<?php echo $page-1; ?>&<?php echo $queryStr; ?>" class="px-4 py-2 border border-gray-300 rounded text-xs font-bold text-gray-600 hover:bg-gray-100 transition-colors">&laquo; PREV</a>
                    <?php endif; ?>
                    
                    <div class="flex gap-1">
                        <?php for ($p = max(1, $page-2); $p <= min($totalPages, $page+2); $p++): ?>
                            <a href="?page=<?php echo $p; ?>&<?php echo $queryStr; ?>" 
                               class="w-10 h-10 flex items-center justify-center border <?php echo $p === $page ? 'bg-gtublue text-white border-gtublue' : 'border-gray-200 text-gray-600 hover:bg-gray-100'; ?> rounded text-xs font-bold transition-all">
                                <?php echo $p; ?>
                            </a>
                        <?php endfor; ?>
                    </div>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?php echo $page+1; ?>&<?php echo $queryStr; ?>" class="px-4 py-2 border border-gray-300 rounded text-xs font-bold text-gray-600 hover:bg-gray-100 transition-colors">NEXT &raquo;</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</main>

<?php include __DIR__ . '/includes/footer.php'; ?>
