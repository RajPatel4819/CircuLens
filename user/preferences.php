<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
$pageTitle = 'My Preferences';

$user = getCurrentUser();

$degrees = ['BE', 'BTech', 'ME', 'MTech', 'MBA', 'MCA', 'Diploma', 'PhD'];
$semesters = ['1', '2', '3', '4', '5', '6', '7', '8'];
$departments = [
    'Computer Engineering', 'Information Technology', 'Electronics & Communication',
    'Mechanical Engineering', 'Civil Engineering', 'Electrical Engineering',
    'Chemical Engineering', 'Biomedical Engineering', 'Others',
];
$allTypes = ['academic', 'examination', 'events', 'placement', 'timetable', 'general'];

try {
    $stmt = db()->prepare('SELECT * FROM preferences WHERE user_id = ? LIMIT 1');
    $stmt->execute([$user['id']]);
    $prefs = $stmt->fetch();
    $dbOk  = true;
} catch (Exception $e) {
    $prefs = null;
    $dbOk  = false;
}

$savedTypes = [];
if ($prefs && !empty($prefs['circular_types'])) {
    $savedTypes = json_decode($prefs['circular_types'], true) ?? [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $degree     = trim($_POST['degree']     ?? '');
    $dept       = trim($_POST['department'] ?? '');
    $semester   = trim($_POST['semester']   ?? '');
    $selTypes   = $_POST['circular_types']  ?? [];
    $selTypes   = array_intersect($selTypes, $allTypes); // whitelist

    try {
        $typesJson = json_encode(array_values($selTypes));
        if ($prefs) {
            db()->prepare('UPDATE preferences SET degree=?, department=?, semester=?, circular_types=? WHERE user_id=?')
               ->execute([$degree ?: null, $dept ?: null, $semester ?: null, $typesJson, $user['id']]);
        } else {
            db()->prepare('INSERT INTO preferences (user_id, degree, department, semester, circular_types) VALUES (?,?,?,?,?)')
               ->execute([$user['id'], $degree ?: null, $dept ?: null, $semester ?: null, $typesJson]);
        }
        setFlash('success', 'Preferences saved successfully!');
        header('Location: ' . APP_URL . '/user/preferences.php');
        exit;
    } catch (Exception $e) {
        setFlash('error', 'Failed to save preferences. Please try again.');
        header('Location: ' . APP_URL . '/user/preferences.php');
        exit;
    }
}

include __DIR__ . '/../includes/user_header.php';
?>

<div class="max-w-2xl mx-auto px-4 py-8">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900">My Preferences</h1>
        <p class="text-gray-500 text-sm mt-1">Customize which circulars you see and receive notifications for.</p>
    </div>

    <?php if (!$dbOk): ?>
        <div class="bg-yellow-50 border border-yellow-300 text-yellow-800 p-4 rounded-xl mb-6">⚠️ Database not connected.</div>
    <?php endif; ?>

    <form method="POST" class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-6">
        <!-- Academic Info -->
        <div>
            <h2 class="text-base font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">Academic Information</h2>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Degree</label>
                    <select name="degree" class="w-full px-3 py-2.5 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">Select Degree</option>
                        <?php foreach ($degrees as $d): ?>
                            <option value="<?php echo $d; ?>" <?php echo ($prefs['degree'] ?? '') === $d ? 'selected' : ''; ?>>
                                <?php echo $d; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Semester</label>
                    <select name="semester" class="w-full px-3 py-2.5 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">Select Semester</option>
                        <?php foreach ($semesters as $s): ?>
                            <option value="<?php echo $s; ?>" <?php echo ($prefs['semester'] ?? '') === $s ? 'selected' : ''; ?>>
                                Semester <?php echo $s; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Department</label>
                    <select name="department" class="w-full px-3 py-2.5 border border-gray-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                        <option value="">Select Department</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>"
                                    <?php echo ($prefs['department'] ?? '') === $dept ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>

        <!-- Circular Types -->
        <div>
            <h2 class="text-base font-semibold text-gray-900 mb-4 pb-2 border-b border-gray-100">Circular Types to Follow</h2>
            <p class="text-gray-500 text-sm mb-3">Select the types you want to see in your dashboard and receive email alerts for.</p>
            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                <?php
                $typeIcons = [
                    'academic'    => '🎓',
                    'examination' => '📝',
                    'events'      => '🎉',
                    'placement'   => '💼',
                    'timetable'   => '📅',
                    'general'     => '📋',
                ];
                foreach ($allTypes as $t):
                    $checked = in_array($t, $savedTypes);
                ?>
                    <label class="flex items-center gap-3 p-3 border-2 rounded-xl cursor-pointer transition-colors <?php echo $checked ? 'border-blue-500 bg-blue-50' : 'border-gray-200 hover:border-gray-300'; ?>">
                        <input type="checkbox" name="circular_types[]" value="<?php echo $t; ?>"
                               <?php echo $checked ? 'checked' : ''; ?>
                               class="w-4 h-4 text-blue-600 rounded border-gray-300 focus:ring-blue-500">
                        <span class="text-sm font-medium text-gray-700">
                            <?php echo $typeIcons[$t]; ?> <?php echo ucfirst($t); ?>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <p class="text-gray-400 text-xs mt-2">Leave all unchecked to see all circular types.</p>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="bg-blue-800 hover:bg-blue-700 text-white px-8 py-2.5 rounded-xl font-semibold text-sm transition-colors">
                Save Preferences
            </button>
            <a href="<?php echo APP_URL; ?>/user/dashboard.php"
               class="px-6 py-2.5 border border-gray-300 text-gray-600 rounded-xl text-sm font-medium hover:bg-gray-50 transition-colors">
                Cancel
            </a>
        </div>
    </form>
</div>

<?php include __DIR__ . '/../includes/user_footer.php'; ?>
